# Order Cart Bundle 技术设计

## 1. 技术概览

### 1.1 架构模式
- **扁平化 Service 层架构**：所有业务逻辑集中在 Service 层，不使用 DDD 分层
- **贫血模型**：Entity 只包含数据和 getter/setter，不包含业务逻辑
- **依赖注入**：使用 Symfony 的依赖注入容器管理服务
- **事件驱动**：通过 Symfony EventDispatcher 实现松耦合

### 1.2 核心设计原则
- **KISS 原则**：保持简单直接，避免过度抽象
- **YAGNI 原则**：只实现当前需要的功能
- **单一职责**：每个 Service 专注于一个业务领域
- **配置简化**：通过 $_ENV 读取配置，不创建专门的 Configuration 类

### 1.3 技术决策理由
- 选择扁平化架构以降低复杂度，提高可维护性
- 使用贫血模型符合 Symfony 最佳实践，便于测试
- 依赖事件系统实现模块间解耦，提供扩展点

## 2. 公共 API 设计

### 2.1 核心接口定义

#### 2.1.1 CartManagerInterface - 购物车管理接口
```php
namespace OrderCartBundle\Service;

use OrderCartBundle\Entity\CartItem;
use Symfony\Component\Security\Core\User\UserInterface;

interface CartManagerInterface
{
    /**
     * 添加商品到购物车
     * 
     * @param UserInterface $user 用户对象
     * @param string $sku 商品SKU
     * @param int $quantity 数量
     * @param array $metadata 额外元数据
     * @return CartItem 创建或更新的购物车项
     * @throws InvalidSkuException 当SKU无效时
     * @throws CartLimitExceededException 当超出购物车限制时
     */
    public function addItem(UserInterface $user, string $sku, int $quantity, array $metadata = []): CartItem;
    
    /**
     * 更新购物车商品数量
     * 
     * @param UserInterface $user 用户对象
     * @param int $cartItemId 购物车项ID
     * @param int $quantity 新数量
     * @return CartItem 更新后的购物车项
     * @throws CartItemNotFoundException 当购物车项不存在时
     * @throws InvalidQuantityException 当数量无效时
     */
    public function updateQuantity(UserInterface $user, int $cartItemId, int $quantity): CartItem;
    
    /**
     * 从购物车移除商品
     * 
     * @param UserInterface $user 用户对象
     * @param int $cartItemId 购物车项ID
     * @throws CartItemNotFoundException 当购物车项不存在时
     */
    public function removeItem(UserInterface $user, int $cartItemId): void;
    
    /**
     * 清空用户购物车
     * 
     * @param UserInterface $user 用户对象
     */
    public function clearCart(UserInterface $user): void;
    
    /**
     * 获取用户购物车
     * 
     * @param UserInterface $user 用户对象
     * @return Cart 购物车对象（包含所有项）
     */
    public function getCart(UserInterface $user): Cart;
    
    /**
     * 切换商品选中状态
     * 
     * @param UserInterface $user 用户对象
     * @param int $cartItemId 购物车项ID
     * @return CartItem 更新后的购物车项
     * @throws CartItemNotFoundException 当购物车项不存在时
     */
    public function toggleSelection(UserInterface $user, int $cartItemId): CartItem;
    
    /**
     * 设置所有商品的选中状态
     * 
     * @param UserInterface $user 用户对象
     * @param bool $selected 选中状态
     */
    public function setSelectionForAll(UserInterface $user, bool $selected): void;
}
```

#### 2.1.2 CartDataProviderInterface - 数据提供接口
```php
namespace OrderCartBundle\Service;

use OrderCartBundle\DTO\CartSummaryDTO;
use Symfony\Component\Security\Core\User\UserInterface;

interface CartDataProviderInterface
{
    /**
     * 获取用户选中的购物车商品
     * 
     * @param UserInterface $user 用户对象
     * @return CartItem[] 选中的购物车项数组
     */
    public function getSelectedItems(UserInterface $user): array;
    
    /**
     * 根据ID批量获取购物车项
     * 
     * @param array $cartItemIds 购物车项ID数组
     * @return CartItem[] 购物车项数组
     */
    public function getItemsByIds(array $cartItemIds): array;
    
    /**
     * 获取购物车摘要信息
     * 
     * @param UserInterface $user 用户对象
     * @return CartSummaryDTO 购物车摘要
     */
    public function getCartSummary(UserInterface $user): CartSummaryDTO;
    
    /**
     * 验证购物车项的可用性
     * 
     * @param array $cartItemIds 购物车项ID数组
     * @return array 验证结果 [id => ['available' => bool, 'reason' => string]]
     */
    public function validateItemsAvailability(array $cartItemIds): array;
}
```

#### 2.1.3 ProductProviderInterface - 商品信息提供接口（需外部实现）
```php
namespace OrderCartBundle\Contract;

use OrderCartBundle\DTO\ProductDTO;

interface ProductProviderInterface
{
    /**
     * 根据SKU获取商品信息
     * 
     * @param string $sku 商品SKU
     * @return ProductDTO|null 商品信息
     */
    public function getProductBySku(string $sku): ?ProductDTO;
    
    /**
     * 批量获取商品信息
     * 
     * @param array $skus SKU数组
     * @return ProductDTO[] 商品信息数组 [sku => ProductDTO]
     */
    public function getProductsBySkus(array $skus): array;
    
    /**
     * 检查商品可用性
     * 
     * @param string $sku 商品SKU
     * @param int $quantity 需要的数量
     * @return bool 是否可用
     */
    public function checkAvailability(string $sku, int $quantity): bool;
}
```

### 2.2 使用示例代码

```php
// 添加商品到购物车
$cartItem = $cartManager->addItem($user, 'SKU-001', 2, ['source' => 'product_page']);

// 更新数量
$updatedItem = $cartManager->updateQuantity($user, $cartItem->getId(), 3);

// 获取选中商品用于结算
$selectedItems = $cartDataProvider->getSelectedItems($user);

// 验证商品可用性
$validation = $cartDataProvider->validateItemsAvailability([1, 2, 3]);
```

### 2.3 错误处理策略

- 使用具体的异常类表示不同错误场景
- 异常包含错误码便于前端处理
- 提供友好的错误信息
- 记录异常日志用于调试

## 3. 内部架构

### 3.1 核心组件划分

```
packages/order-cart-bundle/
├── src/
│   ├── Entity/           # 贫血模型实体
│   │   ├── CartItem.php
│   │   └── Cart.php     # 虚拟实体，用于聚合
│   ├── Repository/       # 数据访问层
│   │   └── CartItemRepository.php
│   ├── Service/          # 业务逻辑层（扁平化）
│   │   ├── CartManager.php
│   │   ├── CartDataProvider.php
│   │   ├── CartValidator.php
│   │   └── CartCleaner.php
│   ├── Event/           # 事件类
│   │   ├── CartItemAddedEvent.php
│   │   ├── CartItemUpdatedEvent.php
│   │   ├── CartItemRemovedEvent.php
│   │   ├── CartClearedEvent.php
│   │   └── CartSelectionChangedEvent.php
│   ├── DTO/             # 数据传输对象
│   │   ├── CartSummaryDTO.php
│   │   ├── ProductDTO.php
│   │   └── CartItemDTO.php
│   ├── Exception/       # 自定义异常
│   │   ├── CartException.php
│   │   ├── CartItemNotFoundException.php
│   │   ├── CartLimitExceededException.php
│   │   ├── InvalidQuantityException.php
│   │   └── InvalidSkuException.php
│   ├── Command/         # CLI命令
│   │   └── CleanExpiredCartItemsCommand.php
│   └── OrderCartBundle.php
├── config/
│   └── services.php     # 服务配置（PHP格式）
└── tests/
```

### 3.2 数据流设计

```
用户请求 → CartManager → CartValidator → Repository → Entity
                ↓                              ↓
         EventDispatcher                   Database
                ↓
          Event Listeners
```

### 3.3 组件职责说明

#### Service 层组件

1. **CartManager**
   - 处理所有购物车 CRUD 操作
   - 协调验证和事件触发
   - 确保事务一致性

2. **CartDataProvider**
   - 提供购物车数据查询服务
   - 整合商品实时信息
   - 计算购物车摘要数据

3. **CartValidator**
   - 验证商品有效性
   - 检查数量限制
   - 执行自定义验证规则

4. **CartCleaner**
   - 清理过期购物车项
   - 执行定期维护任务

#### Repository 层

**CartItemRepository**
- 继承 ServiceEntityRepository
- 提供优化的查询方法
- 处理批量操作

#### Entity 层（贫血模型）

**CartItem**
```php
namespace OrderCartBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'cart_items')]
#[ORM\UniqueConstraint(columns: ['user_id', 'sku'])]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['created_at'])]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $user;
    
    #[ORM\Column(length: 255)]
    private string $sku;
    
    #[ORM\Column]
    private int $quantity;
    
    #[ORM\Column]
    private bool $selected = true;
    
    #[ORM\Column(type: 'json')]
    private array $metadata = [];
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;
    
    // 只包含 getter 和 setter 方法
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getUser(): UserInterface
    {
        return $this->user;
    }
    
    public function setUser(UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }
    
    public function getSku(): \Tourze\ProductCoreBundle\Entity\Sku
    {
        return $this->sku;
    }
    
    public function setSku(\Tourze\ProductCoreBundle\Entity\Sku $sku): self
    {
        $this->sku = $sku;
        return $this;
    }
    
    // ... 其他 getter/setter
}
```

## 4. 扩展机制

### 4.1 扩展点定义

#### 4.1.1 商品验证器扩展
```php
namespace OrderCartBundle\Validator;

interface CartItemValidatorInterface
{
    public function validate(string $sku, int $quantity, UserInterface $user): ValidationResult;
    public function getPriority(): int;
}
```

#### 4.1.2 数量限制器扩展
```php
namespace OrderCartBundle\Limiter;

interface QuantityLimiterInterface
{
    public function getMaxQuantity(string $sku, UserInterface $user): int;
    public function getMaxItemsPerCart(UserInterface $user): int;
}
```

### 4.2 事件系统设计

所有关键操作都触发相应事件，允许外部模块监听和扩展：

```php
// 监听购物车事件的示例
class CartEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CartItemAddedEvent::class => 'onItemAdded',
            CartItemRemovedEvent::class => 'onItemRemoved',
        ];
    }
    
    public function onItemAdded(CartItemAddedEvent $event): void
    {
        // 自定义逻辑，如发送通知、更新统计等
    }
}
```

### 4.3 配置架构

通过环境变量配置，不创建专门的 Configuration 类：

```php
// Service 中直接读取配置
class CartManager implements CartManagerInterface
{
    private int $maxItemsPerCart;
    private int $itemRetentionDays;
    
    public function __construct(
        private readonly CartItemRepository $repository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
        $this->maxItemsPerCart = (int)($_ENV['CART_MAX_ITEMS'] ?? 100);
        $this->itemRetentionDays = (int)($_ENV['CART_RETENTION_DAYS'] ?? 30);
    }
}
```

## 5. 集成设计

### 5.1 Symfony 集成（Bundle）

#### 5.1.1 Bundle 类
```php
namespace OrderCartBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OrderCartBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        // 注册编译器传递等
    }
}
```

#### 5.1.2 服务配置（services.php）
```php
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();
    
    $services->load('OrderCartBundle\\', '../src/')
        ->exclude('../src/{Entity,DTO,Event,Exception}');
    
    // 注册接口别名
    $services->alias(CartManagerInterface::class, CartManager::class);
    $services->alias(CartDataProviderInterface::class, CartDataProvider::class);
};
```

### 5.2 Laravel 集成（ServiceProvider）

```php
namespace OrderCartBundle\Laravel;

use Illuminate\Support\ServiceProvider;

class OrderCartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartManagerInterface::class, CartManager::class);
        $this->app->singleton(CartDataProviderInterface::class, CartDataProvider::class);
    }
    
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/order-cart.php' => config_path('order-cart.php'),
        ], 'config');
        
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
```

### 5.3 独立使用支持

```php
// 独立使用示例
use OrderCartBundle\Service\CartManager;
use OrderCartBundle\Repository\CartItemRepository;

$entityManager = // ... 配置 Doctrine
$eventDispatcher = new EventDispatcher();
$repository = new CartItemRepository($entityManager);

$cartManager = new CartManager($repository, $eventDispatcher);

// 使用购物车
$cartManager->addItem($user, 'SKU-001', 1);
```

## 6. 测试策略

### 6.1 单元测试方案

- **Service 测试**：使用 Mock 对象测试业务逻辑
- **Entity 测试**：验证 getter/setter 的正确性
- **Event 测试**：验证事件数据结构

```php
class CartManagerTest extends TestCase
{
    private CartManager $cartManager;
    private CartItemRepository $repository;
    private EventDispatcherInterface $eventDispatcher;
    
    protected function setUp(): void
    {
        $this->repository = $this->createMock(CartItemRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->cartManager = new CartManager($this->repository, $this->eventDispatcher);
    }
    
    public function testAddItem(): void
    {
        // 配置 mock 期望
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($expectedCartItem);
            
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CartItemAddedEvent::class));
        
        // 执行测试
        $result = $this->cartManager->addItem($user, 'SKU-001', 2);
        
        // 断言
        $this->assertEquals('SKU-001', $result->getSku());
        $this->assertEquals(2, $result->getQuantity());
    }
}
```

### 6.2 集成测试方案

- **Repository 测试**：使用测试数据库验证查询
- **端到端测试**：测试完整的用户场景
- **性能测试**：验证查询优化效果

### 6.3 性能基准测试

```php
class CartPerformanceTest extends KernelTestCase
{
    public function testQueryPerformance(): void
    {
        // 准备100个商品的购物车
        $this->prepareCartWithItems(100);
        
        $start = microtime(true);
        $cart = $this->cartManager->getCart($this->user);
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $duration, 'Query should complete within 100ms');
    }
}
```

## 7. 性能优化策略

### 7.1 数据库优化
- 在 `user_id` 字段创建索引
- 在 `(user_id, sku)` 创建复合唯一索引
- 使用批量查询减少数据库访问

### 7.2 查询优化
```php
class CartItemRepository extends ServiceEntityRepository
{
    public function findByUserWithProducts(UserInterface $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.sku', 's')
            ->addSelect('s')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function batchUpdate(array $items): void
    {
        // 使用批量更新减少数据库操作
        $this->_em->transactional(function() use ($items) {
            foreach ($items as $item) {
                $this->_em->persist($item);
            }
        });
    }
}
```

### 7.3 缓存策略
- 不缓存购物车数据本身（需要实时性）
- 可以缓存商品基础信息
- 使用 Redis 存储临时数据

## 8. 安全设计

### 8.1 权限控制
```php
class CartManager implements CartManagerInterface
{
    public function updateQuantity(UserInterface $user, int $cartItemId, int $quantity): CartItem
    {
        $cartItem = $this->repository->find($cartItemId);
        
        // 验证用户权限
        if ($cartItem->getUser() !== $user) {
            throw new AccessDeniedException('You can only modify your own cart items');
        }
        
        // ... 继续处理
    }
}
```

### 8.2 输入验证
```php
class CartValidator
{
    public function validateQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidQuantityException('Quantity must be greater than 0');
        }
        
        if ($quantity > 9999) {
            throw new InvalidQuantityException('Quantity exceeds maximum limit');
        }
    }
    
    public function validateSku(string $sku): void
    {
        if (!preg_match('/^[A-Z0-9\-]+$/i', $sku)) {
            throw new InvalidSkuException('Invalid SKU format');
        }
    }
}
```

### 8.3 SQL 注入防护
- 使用 Doctrine ORM 的参数绑定
- 避免直接拼接 SQL
- 对所有用户输入进行验证

## 9. 错误处理

### 9.1 异常层次结构
```php
// 基础异常
class CartException extends \Exception
{
    protected string $errorCode;
    
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

// 具体异常
class CartItemNotFoundException extends CartException
{
    protected string $errorCode = 'CART_ITEM_NOT_FOUND';
}

class CartLimitExceededException extends CartException
{
    protected string $errorCode = 'CART_LIMIT_EXCEEDED';
}
```

### 9.2 错误处理流程
```php
class CartManager implements CartManagerInterface
{
    public function addItem(UserInterface $user, string $sku, int $quantity, array $metadata = []): CartItem
    {
        try {
            // 验证
            $this->validator->validateSku($sku);
            $this->validator->validateQuantity($quantity);
            
            // 检查限制
            if ($this->getCartItemCount($user) >= $this->maxItemsPerCart) {
                throw new CartLimitExceededException(
                    sprintf('Cart limit of %d items exceeded', $this->maxItemsPerCart)
                );
            }
            
            // 业务逻辑...
            
        } catch (CartException $e) {
            // 记录业务异常
            $this->logger->warning('Cart operation failed', [
                'user' => $user->getId(),
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            throw $e;
            
        } catch (\Exception $e) {
            // 记录系统异常
            $this->logger->error('Unexpected error in cart operation', [
                'exception' => $e
            ]);
            throw new CartException('An unexpected error occurred', 0, $e);
        }
    }
}
```

## 10. 与 order-checkout-bundle 的集成

### 10.1 接口调用示例
```php
// 在 order-checkout-bundle 中使用
namespace OrderCheckoutBundle\Service;

use OrderCartBundle\Service\CartDataProviderInterface;

class CheckoutService
{
    public function __construct(
        private readonly CartDataProviderInterface $cartDataProvider
    ) {}
    
    public function createOrderFromCart(UserInterface $user): Order
    {
        // 获取选中的购物车项
        $selectedItems = $this->cartDataProvider->getSelectedItems($user);
        
        if (empty($selectedItems)) {
            throw new NoItemsSelectedException();
        }
        
        // 验证商品可用性
        $itemIds = array_map(fn($item) => $item->getId(), $selectedItems);
        $validation = $this->cartDataProvider->validateItemsAvailability($itemIds);
        
        // 创建订单...
    }
    
    public function createDirectOrder(array $products): Order
    {
        // 直接结算，不经过购物车
        // 业务逻辑...
    }
}
```

### 10.2 事件监听集成
```php
// 在 order-checkout-bundle 中监听购物车事件
namespace OrderCheckoutBundle\EventListener;

use OrderCartBundle\Event\CartItemAddedEvent;

class CartEventListener
{
    public function onCartItemAdded(CartItemAddedEvent $event): void
    {
        // 可以在这里预计算优惠信息等
        $cartItem = $event->getCartItem();
        $this->promotionCalculator->preCalculate($cartItem);
    }
}
```

## 11. 部署注意事项

### 11.1 数据库迁移
```php
// Migration 文件
namespace OrderCartBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cart_items (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            sku VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            selected TINYINT(1) DEFAULT 1,
            metadata JSON NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX uniq_user_sku (user_id, sku),
            INDEX idx_user (user_id),
            INDEX idx_created_at (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
}
```

### 11.2 环境变量配置
```bash
# .env 文件
CART_MAX_ITEMS=100
CART_RETENTION_DAYS=30
CART_AUTO_CLEAN_ENABLED=true
CART_TABLE_PREFIX=cart_
```

### 11.3 Cron 任务配置
```bash
# 清理过期购物车项的定时任务
0 2 * * * php bin/console cart:clean-expired --env=prod
```

## 12. 设计验证检查清单

### 架构合规性 ✅
- [x] 不使用 DDD 分层架构
- [x] 不创建值对象目录
- [x] 不使用富领域模型
- [x] 使用扁平化的 Service 层
- [x] 遵循 `.claude/standards/symfony-bundle-standards.md` 的目录结构
- [x] 不创建 Configuration 类
- [x] 不主动创建 HTTP API 端点

### Service 设计 ✅
- [x] 每个 Service 有明确的单一职责
- [x] 业务逻辑都在 Service 中
- [x] 使用构造函数注入和 readonly 属性
- [x] 不创建门面服务或上帝服务

### Entity 设计 ✅
- [x] 实体是贫血模型
- [x] 只包含 getter/setter 方法
- [x] 不包含业务逻辑
- [x] 不调用其他服务或触发事件

### Repository 设计 ✅
- [x] 继承 ServiceEntityRepository
- [x] 只负责数据访问
- [x] 不包含业务逻辑
- [x] 方法命名遵循约定

### 抽象层级 ✅
- [x] 不创建不必要的接口
- [x] 不创建抽象基类（除非有 3+ 个实现）
- [x] 不创建工厂类（除非真的需要）
- [x] 遵循 YAGNI 原则

### 需求覆盖 ✅
- [x] 满足所有 EARS 需求
- [x] 包含安全考虑
- [x] 定义清晰的组件边界
- [x] 指定数据模型和关系
- [x] 涵盖错误处理和边缘案例
- [x] 包含性能考虑
- [x] 可以用所选技术栈实现

## 13. 总结

本设计方案采用扁平化 Service 层架构，配合贫血模型和事件驱动机制，实现了一个独立、可复用、易维护的购物车管理组件。设计遵循 KISS 和 YAGNI 原则，避免过度抽象，同时提供了充分的扩展点以满足未来需求。

关键设计亮点：
1. **简单直接**：扁平化架构降低了理解和维护成本
2. **松耦合**：通过接口和事件系统实现模块解耦
3. **高性能**：优化的数据库查询和索引设计
4. **易测试**：贫血模型和依赖注入便于单元测试
5. **可扩展**：提供验证器和限制器扩展点