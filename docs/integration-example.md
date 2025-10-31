# Integration Example

This guide demonstrates how to integrate the Order Cart Bundle with your application.

## 1. Implement ProductProviderInterface

First, create a service that implements the `ProductProviderInterface`:

```php
<?php

namespace App\Service;

use Tourze\OrderCartBundle\Interface\ProductProviderInterface;
use Tourze\OrderCartBundle\DTO\ProductDTO;
use Tourze\ProductCoreBundle\Entity\Sku;
use App\Repository\ProductRepository;
use App\Repository\InventoryRepository;

class ProductProvider implements ProductProviderInterface
{
    public function __construct(
        private ProductRepository $productRepository,
        private InventoryRepository $inventoryRepository,
    ) {}

    public function getProductBySku(Sku $sku): ?ProductDTO
    {
        $product = $this->productRepository->findBySku($sku);
        
        if (!$product) {
            return null;
        }
        
        return new ProductDTO(
            $sku->getId(),
            $sku->getCode(),
            $product->getName(),
            $product->getPrice(),
            $product->getImageUrl(),
            $product->isActive(),
            $this->inventoryRepository->getAvailableStock($sku)
        );
    }

    public function getProductsBySkus(array $skus): array
    {
        $products = [];
        
        foreach ($skus as $sku) {
            $product = $this->getProductBySku($sku);
            if ($product) {
                $products[$sku->getId()] = $product;
            }
        }
        
        return $products;
    }

    public function isAvailable(Sku $sku, int $quantity): bool
    {
        $availableStock = $this->inventoryRepository->getAvailableStock($sku);
        return $availableStock >= $quantity;
    }

    public function getAvailableQuantity(Sku $sku): int
    {
        return $this->inventoryRepository->getAvailableStock($sku);
    }

    public function validateSku(Sku $sku): bool
    {
        $product = $this->productRepository->findBySku($sku);
        return $product && $product->isActive();
    }
}
```

## 2. Complete Cart Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\OrderCartBundle\Interface\CartManagerInterface;
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;
use Tourze\OrderCartBundle\Exception\CartException;
use Tourze\ProductCoreBundle\Entity\Sku;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartManagerInterface $cartManager,
        private CartDataProviderInterface $cartDataProvider,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'cart_view', methods: ['GET'])]
    public function view(): Response
    {
        $user = $this->getUser();
        
        $summary = $this->cartDataProvider->getCartSummary($user);
        $items = $this->cartDataProvider->getCartItems($user);
        
        return $this->render('cart/view.html.twig', [
            'summary' => $summary,
            'items' => $items,
        ]);
    }

    #[Route('/add', name: 'cart_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $user = $this->getUser();
        $skuId = $request->request->getInt('sku_id');
        $quantity = $request->request->getInt('quantity', 1);
        
        $sku = $this->entityManager->getRepository(Sku::class)->find($skuId);
        
        if (!$sku) {
            $this->addFlash('error', 'Product not found');
            return $this->redirectToRoute('cart_view');
        }
        
        try {
            $this->cartManager->addItem($user, $sku, $quantity);
            $this->addFlash('success', 'Item added to cart');
        } catch (CartException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('cart_view');
    }

    #[Route('/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $quantity = $request->request->getInt('quantity');
        
        try {
            $this->cartManager->updateQuantity($user, $id, $quantity);
            $this->addFlash('success', 'Cart updated');
        } catch (CartException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('cart_view');
    }

    #[Route('/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        $user = $this->getUser();
        
        try {
            $this->cartManager->removeItem($user, $id);
            $this->addFlash('success', 'Item removed from cart');
        } catch (CartException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('cart_view');
    }

    #[Route('/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $user = $this->getUser();
        
        $count = $this->cartManager->clearCart($user);
        $this->addFlash('success', sprintf('Removed %d items from cart', $count));
        
        return $this->redirectToRoute('cart_view');
    }

    #[Route('/select', name: 'cart_select', methods: ['POST'])]
    public function updateSelection(Request $request): Response
    {
        $user = $this->getUser();
        $selectedIds = $request->request->all('selected') ?: [];
        
        // Get all user's cart items
        $allItems = $this->cartDataProvider->getCartItems($user);
        $allIds = array_keys($allItems);
        
        // Mark selected items
        if (!empty($selectedIds)) {
            $this->cartManager->batchUpdateSelection($user, $selectedIds, true);
        }
        
        // Mark unselected items
        $unselectedIds = array_diff($allIds, $selectedIds);
        if (!empty($unselectedIds)) {
            $this->cartManager->batchUpdateSelection($user, $unselectedIds, false);
        }
        
        return $this->redirectToRoute('cart_view');
    }
}
```

## 3. Event Listener for Stock Updates

```php
<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\OrderCartBundle\Event\CartItemAddedEvent;
use Tourze\OrderCartBundle\Event\CartItemUpdatedEvent;
use App\Service\InventoryService;
use Psr\Log\LoggerInterface;

class CartStockReservationListener implements EventSubscriberInterface
{
    public function __construct(
        private InventoryService $inventoryService,
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CartItemAddedEvent::class => 'onItemAdded',
            CartItemUpdatedEvent::class => 'onItemUpdated',
        ];
    }

    public function onItemAdded(CartItemAddedEvent $event): void
    {
        $cartItem = $event->getCartItem();
        $sku = $cartItem->getSku();
        $quantity = $cartItem->getQuantity();
        
        try {
            // Reserve stock for the cart item
            $this->inventoryService->reserveStock($sku, $quantity);
            
            $this->logger->info('Stock reserved for cart item', [
                'user' => $event->getUser()->getUserIdentifier(),
                'sku' => $sku->getCode(),
                'quantity' => $quantity,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to reserve stock', [
                'error' => $e->getMessage(),
                'sku' => $sku->getCode(),
            ]);
        }
    }

    public function onItemUpdated(CartItemUpdatedEvent $event): void
    {
        $cartItem = $event->getCartItem();
        $sku = $cartItem->getSku();
        $oldQuantity = $event->getOldQuantity();
        $newQuantity = $event->getNewQuantity();
        $difference = $newQuantity - $oldQuantity;
        
        if ($difference > 0) {
            // Reserve additional stock
            $this->inventoryService->reserveStock($sku, $difference);
        } elseif ($difference < 0) {
            // Release stock
            $this->inventoryService->releaseStock($sku, abs($difference));
        }
    }
}
```

## 4. Custom Validator for VIP Products

```php
<?php

namespace App\Validator;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Validator\CartItemValidatorInterface;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\ProductCoreBundle\Entity\Sku;
use App\Service\UserService;

class VipProductValidator implements CartItemValidatorInterface
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function validate(UserInterface $user, Sku $sku, int $quantity): void
    {
        // Check if product requires VIP status
        if ($this->isVipProduct($sku) && !$this->userService->isVip($user)) {
            throw new InvalidSkuException('This product is only available for VIP members');
        }
    }

    public function supports(Sku $sku): bool
    {
        return $this->isVipProduct($sku);
    }

    public function getPriority(): int
    {
        return 80; // Run before standard validators
    }

    private function isVipProduct(Sku $sku): bool
    {
        // Check if SKU has VIP tag or category
        return str_starts_with($sku->getCode(), 'VIP-');
    }
}
```

## 5. Integration with Checkout

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;
use Tourze\OrderCartBundle\Interface\CartManagerInterface;
use App\Service\OrderService;
use App\Service\PaymentService;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartDataProviderInterface $cartDataProvider,
        private CartManagerInterface $cartManager,
        private OrderService $orderService,
        private PaymentService $paymentService,
    ) {}

    #[Route('/review', name: 'checkout_review')]
    public function review(): Response
    {
        $user = $this->getUser();
        
        $selectedItems = $this->cartDataProvider->getSelectedItems($user);
        
        if (empty($selectedItems)) {
            $this->addFlash('error', 'Please select items for checkout');
            return $this->redirectToRoute('cart_view');
        }
        
        $summary = $this->cartDataProvider->getCartSummary($user);
        
        return $this->render('checkout/review.html.twig', [
            'items' => $selectedItems,
            'summary' => $summary,
        ]);
    }

    #[Route('/confirm', name: 'checkout_confirm', methods: ['POST'])]
    public function confirm(): Response
    {
        $user = $this->getUser();
        
        $selectedItems = $this->cartDataProvider->getSelectedItems($user);
        
        if (empty($selectedItems)) {
            return $this->redirectToRoute('cart_view');
        }
        
        // Create order from selected cart items
        $order = $this->orderService->createOrder($user, $selectedItems);
        
        // Clear selected items from cart
        foreach ($selectedItems as $item) {
            $this->cartManager->removeItem($user, $item->getId());
        }
        
        // Redirect to payment
        return $this->redirectToRoute('payment_process', [
            'orderId' => $order->getId(),
        ]);
    }

    #[Route('/direct/{skuId}', name: 'checkout_direct')]
    public function directCheckout(int $skuId): Response
    {
        $user = $this->getUser();
        
        // Implementation for direct checkout without adding to cart
        // Used for "Buy Now" functionality
        
        $sku = $this->entityManager->getRepository(Sku::class)->find($skuId);
        
        if (!$sku) {
            throw $this->createNotFoundException('Product not found');
        }
        
        // Create temporary cart item for direct checkout
        $order = $this->orderService->createDirectOrder($user, $sku, 1);
        
        return $this->redirectToRoute('payment_process', [
            'orderId' => $order->getId(),
        ]);
    }
}
```

## 6. Twig Templates

### cart/view.html.twig

```twig
{% extends 'base.html.twig' %}

{% block title %}Shopping Cart{% endblock %}

{% block body %}
<div class="container">
    <h1>Shopping Cart</h1>
    
    {% for message in app.flashes('success') %}
        <div class="alert alert-success">{{ message }}</div>
    {% endfor %}
    
    {% for message in app.flashes('error') %}
        <div class="alert alert-danger">{{ message }}</div>
    {% endfor %}
    
    {% if items is empty %}
        <p>Your cart is empty.</p>
        <a href="{{ path('product_list') }}" class="btn btn-primary">Continue Shopping</a>
    {% else %}
        <form action="{{ path('cart_select') }}" method="post">
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {% for item in items %}
                    <tr>
                        <td>
                            <input type="checkbox" 
                                   name="selected[]" 
                                   value="{{ item.id }}"
                                   {% if item.selected %}checked{% endif %}>
                        </td>
                        <td>
                            <img src="{{ item.product.imageUrl }}" width="50">
                            {{ item.product.name }}
                        </td>
                        <td>${{ item.product.price / 100 }}</td>
                        <td>
                            <form action="{{ path('cart_update', {id: item.id}) }}" method="post" class="d-inline">
                                <input type="number" 
                                       name="quantity" 
                                       value="{{ item.quantity }}" 
                                       min="1" 
                                       max="999"
                                       class="form-control" 
                                       style="width: 80px">
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                        <td>${{ (item.product.price * item.quantity) / 100 }}</td>
                        <td>
                            <form action="{{ path('cart_remove', {id: item.id}) }}" method="post" class="d-inline">
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    {% endfor %}
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4">Total ({{ summary.totalItems }} items)</th>
                        <th>${{ summary.totalAmount / 100 }}</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="4">Selected ({{ summary.selectedItems }} items)</th>
                        <th>${{ summary.selectedAmount / 100 }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-secondary">Update Selection</button>
                <a href="{{ path('checkout_review') }}" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        </form>
        
        <form action="{{ path('cart_clear') }}" method="post" class="mt-3">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Clear entire cart?')">
                Clear Cart
            </button>
        </form>
    {% endif %}
</div>
{% endblock %}
```

## 7. Service Configuration

```yaml
# config/services.yaml
services:
    App\Service\ProductProvider:
        tags:
            - { name: 'order_cart.product_provider' }
    
    App\Validator\VipProductValidator:
        tags:
            - { name: 'order_cart.validator' }
    
    App\EventListener\CartStockReservationListener:
        tags:
            - { name: 'kernel.event_subscriber' }
```

This complete integration example demonstrates:
- Product provider implementation
- Full cart controller with all operations
- Event listeners for stock management
- Custom validators
- Integration with checkout process
- Twig templates for UI
- Service configuration