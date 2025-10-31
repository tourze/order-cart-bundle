# Order Cart Bundle

[English](README.md) | [中文](README.zh-CN.md)

A flexible and extensible shopping cart management bundle for Symfony applications.

## Features

- **Complete Cart Management**: Add, update, remove items and manage cart selection states
- **Event-Driven Architecture**: All state changes trigger events for easy integration
- **Extensible Design**: Multiple extension points for custom business logic
- **SKU Entity Integration**: Direct integration with `Tourze\ProductCoreBundle\Entity\Sku`
- **Anemic Model Pattern**: Clean separation of entities and business logic
- **High Quality Standards**: PHPStan Level 8, comprehensive test coverage

## Installation

```bash
composer require tourze/order-cart-bundle
```

## Configuration

### 1. Register the Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\OrderCartBundle\OrderCartBundle::class => ['all' => true],
];
```

### 2. Configure Services

The bundle automatically configures all services. You can override them in your application:

```yaml
# config/packages/order_cart.yaml
services:
    # Implement the ProductProviderInterface for your application
    Tourze\OrderCartBundle\Interface\ProductProviderInterface:
        class: App\Service\ProductProvider
```

### 3. Update Database Schema

```bash
php bin/console doctrine:schema:update --force
# Or use migrations
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## Basic Usage

### Adding Items to Cart

```php
use Tourze\OrderCartBundle\Interface\CartManagerInterface;
use Tourze\ProductCoreBundle\Entity\Sku;

class CartController
{
    public function __construct(
        private CartManagerInterface $cartManager
    ) {}

    public function addItem(Sku $sku, int $quantity): Response
    {
        $user = $this->getUser();
        
        try {
            $cartItem = $this->cartManager->addItem(
                $user, 
                $sku, 
                $quantity,
                ['note' => 'Gift wrapping requested']
            );
            
            return $this->json(['success' => true]);
        } catch (InvalidSkuException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Retrieving Cart Data

```php
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;

class CartViewController
{
    public function __construct(
        private CartDataProviderInterface $cartDataProvider
    ) {}

    public function view(): Response
    {
        $user = $this->getUser();
        
        // Get cart summary
        $summary = $this->cartDataProvider->getCartSummary($user);
        
        // Get all cart items
        $items = $this->cartDataProvider->getCartItems($user);
        
        // Get only selected items
        $selectedItems = $this->cartDataProvider->getSelectedItems($user);
        
        return $this->render('cart/view.html.twig', [
            'summary' => $summary,
            'items' => $items,
        ]);
    }
}
```

### Managing Cart Items

```php
// Update quantity
$this->cartManager->updateQuantity($user, $cartItemId, 5);

// Remove item
$this->cartManager->removeItem($user, $cartItemId);

// Clear entire cart
$deletedCount = $this->cartManager->clearCart($user);

// Update selection state
$this->cartManager->updateSelection($user, $cartItemId, true);

// Batch update selection
$this->cartManager->batchUpdateSelection($user, [1, 2, 3], false);
```

## Events

The bundle dispatches the following events:

- `CartItemAddedEvent` - When an item is added to cart
- `CartItemUpdatedEvent` - When item quantity is updated
- `CartItemRemovedEvent` - When an item is removed
- `CartClearedEvent` - When the entire cart is cleared
- `CartSelectionChangedEvent` - When item selection state changes

### Listening to Events

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\OrderCartBundle\Event\CartItemAddedEvent;

class CartEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CartItemAddedEvent::class => 'onItemAdded',
        ];
    }

    public function onItemAdded(CartItemAddedEvent $event): void
    {
        $user = $event->getUser();
        $cartItem = $event->getCartItem();
        
        // Send notification, update analytics, etc.
    }
}
```

## Extension Points

### Custom Validators

Create custom validation logic for cart items:

```php
use Tourze\OrderCartBundle\Validator\CartItemValidatorInterface;

class MinimumOrderValidator implements CartItemValidatorInterface
{
    public function validate(UserInterface $user, Sku $sku, int $quantity): void
    {
        if ($quantity < 10 && $sku->getCategory() === 'wholesale') {
            throw new InvalidQuantityException('Minimum order is 10 for wholesale items');
        }
    }

    public function supports(Sku $sku): bool
    {
        return $sku->getCategory() === 'wholesale';
    }

    public function getPriority(): int
    {
        return 50;
    }
}
```

### Custom Price Calculator

Implement custom pricing logic:

```php
use Tourze\OrderCartBundle\PriceCalculator\PriceCalculatorInterface;

class PromotionPriceCalculator implements PriceCalculatorInterface
{
    public function calculateItemPrice(CartItemDTO $item): int
    {
        $basePrice = $item->getProduct()->getPrice() * $item->getQuantity();
        
        // Apply buy 2 get 1 free
        if ($item->getQuantity() >= 3) {
            $freeItems = intdiv($item->getQuantity(), 3);
            $paidItems = $item->getQuantity() - $freeItems;
            return $item->getProduct()->getPrice() * $paidItems;
        }
        
        return $basePrice;
    }
    
    // ... other methods
}
```

### Cart Decorators

Add additional data to cart summaries:

```php
use Tourze\OrderCartBundle\Decorator\CartDecoratorInterface;

class ShippingCostDecorator implements CartDecoratorInterface
{
    public function decorate(CartSummaryDTO $summary, UserInterface $user): CartSummaryDTO
    {
        // Add shipping cost based on total amount
        $shippingCost = $summary->getTotalAmount() >= 10000 ? 0 : 500;
        
        // Return new DTO with additional data
        return new CartSummaryDTO(
            $summary->getTotalItems(),
            $summary->getSelectedItems(),
            $summary->getSelectedAmount() + $shippingCost,
            $summary->getTotalAmount() + $shippingCost
        );
    }

    public function getPriority(): int
    {
        return 10;
    }
}
```

## CLI Commands

### Clean Expired Cart Items

```bash
# Remove cart items older than 30 days
php bin/console order-cart:clean-expired

# Specify retention period
php bin/console order-cart:clean-expired --days=7

# Dry run mode
php bin/console order-cart:clean-expired --dry-run

# Custom batch size
php bin/console order-cart:clean-expired --batch-size=500
```

## Integration with Order Checkout Bundle

```php
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;
use Tourze\OrderCheckoutBundle\Service\CheckoutService;

class CheckoutController
{
    public function __construct(
        private CartDataProviderInterface $cartDataProvider,
        private CheckoutService $checkoutService
    ) {}

    public function checkout(): Response
    {
        $user = $this->getUser();
        
        // Get selected items for checkout
        $selectedItems = $this->cartDataProvider->getSelectedItems($user);
        
        if (empty($selectedItems)) {
            throw new \LogicException('No items selected for checkout');
        }
        
        // Create order from cart items
        $order = $this->checkoutService->createOrderFromCart($selectedItems);
        
        return $this->redirectToRoute('payment', ['order' => $order->getId()]);
    }
}
```

## Configuration Reference

```yaml
# config/packages/order_cart.yaml
order_cart:
    # Maximum items per cart (default: 100)
    max_cart_items: 100
    
    # Maximum quantity per item (default: 999)
    max_quantity_per_item: 999
    
    # Cart expiration days for cleanup command (default: 30)
    cart_expiration_days: 30
```

## Testing

Run the test suite:

```bash
# Unit tests
./vendor/bin/phpunit packages/order-cart-bundle/tests/Unit

# Integration tests
./vendor/bin/phpunit packages/order-cart-bundle/tests/Integration

# Static analysis
./vendor/bin/phpstan analyse packages/order-cart-bundle -l 8
```

## License

Proprietary

## Support

For issues and questions, please contact the development team.