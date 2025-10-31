# Order Cart Bundle 需求规范

## 1. 概述

### 1.1 包的目的
Order Cart Bundle 是一个独立的购物车管理组件，负责处理电商系统中的购物车相关功能。该包从原有的 order-checkout-bundle 中解耦出来，使购物车功能可以被多个模块独立使用，同时支持多种结算入口（购物车结算、直接商品结算）。

### 1.2 价值主张
- **独立性**：购物车功能完全独立，不依赖任何结算逻辑
- **可复用性**：可被多个业务模块（结算、收藏夹、快速购买等）使用
- **灵活性**：支持多种集成方式和扩展点
- **可维护性**：清晰的职责边界，易于测试和维护

### 1.3 主要使用者
- **直接使用者**：其他 Bundle 开发者（order-checkout-bundle、wishlist-bundle 等）
- **集成场景**：电商项目、培训系统课程购买、酒店预订房间选择
- **API 消费者**：前端应用通过 JSON-RPC API 调用购物车功能

## 2. 功能需求

### 2.1 核心功能（EARS 格式）

#### 2.1.1 购物车管理
- **U1**: Package 必须提供添加商品到购物车的功能，包括 SKU 和数量信息
- **E1**: 当商品添加到购物车时，Package 必须触发 CartItemAddedEvent 事件
- **E2**: 当用户更新购物车商品数量时，Package 必须验证新数量的有效性（大于0）
- **E3**: 当用户删除购物车商品时，Package 必须触发 CartItemRemovedEvent 事件
- **E4**: 当用户清空购物车时，Package 必须触发 CartClearedEvent 事件
- **S1**: 当购物车为空时，Package 必须返回空的购物车对象而非 null

#### 2.1.2 数据查询服务
- **U2**: Package 必须提供 CartDataProviderInterface 服务供其他模块获取购物车数据
- **U3**: Package 必须支持按用户获取完整购物车信息
- **U4**: Package 必须支持获取用户选中的购物车商品
- **U5**: Package 必须支持根据购物车项 ID 列表批量获取商品信息
- **O1**: 在查询购物车数据时，Package 必须实时获取商品的最新信息（价格、库存、状态）

#### 2.1.3 选中状态管理
- **U6**: Package 必须支持单个商品的选中/取消选中操作
- **U7**: Package 必须支持全选/全不选操作
- **S2**: 当获取结算数据时，Package 必须只返回选中且有效的商品

#### 2.1.4 数据持久化
- **U8**: Package 必须将购物车数据持久化到数据库
- **U9**: Package 必须支持多用户隔离的购物车数据管理
- **U10**: Package 必须确保购物车操作的事务一致性

### 2.2 API 接口设计

#### 2.2.1 购物车管理接口
```php
interface CartManagerInterface
{
    public function addItem(UserInterface $user, string $sku, int $quantity, array $metadata = []): CartItemInterface;
    public function updateQuantity(UserInterface $user, int $cartItemId, int $quantity): CartItemInterface;
    public function removeItem(UserInterface $user, int $cartItemId): void;
    public function clearCart(UserInterface $user): void;
    public function getCart(UserInterface $user): CartInterface;
    public function toggleSelection(UserInterface $user, int $cartItemId): CartItemInterface;
    public function setSelectionForAll(UserInterface $user, bool $selected): void;
}
```

#### 2.2.2 数据提供接口（供 order-checkout-bundle 使用）
```php
interface CartDataProviderInterface
{
    public function getSelectedItems(UserInterface $user): array;
    public function getItemsByIds(array $cartItemIds): array;
    public function getCartSummary(UserInterface $user): CartSummaryDTO;
    public function validateItemsAvailability(array $cartItemIds): array;
}
```

#### 2.2.3 商品信息提供接口（需要外部实现）
```php
interface ProductProviderInterface
{
    public function getProductBySku(string $sku): ?ProductDTO;
    public function getProductsBySkus(array $skus): array;
    public function checkAvailability(string $sku, int $quantity): bool;
}
```

### 2.3 事件系统

#### 2.3.1 事件定义
- `CartItemAddedEvent`: 商品添加到购物车
- `CartItemUpdatedEvent`: 商品数量或状态更新
- `CartItemRemovedEvent`: 商品从购物车移除
- `CartClearedEvent`: 购物车清空
- `CartSelectionChangedEvent`: 选中状态变更

#### 2.3.2 事件数据结构
```php
class CartItemAddedEvent
{
    public function __construct(
        private UserInterface $user,
        private CartItemInterface $cartItem,
        private array $context = []
    ) {}
}
```

## 3. 非功能需求

### 3.1 性能要求
- **P1**: Package 必须支持单用户购物车包含最多 100 个不同商品
- **P2**: Package 必须在 100ms 内完成购物车查询操作
- **P3**: Package 必须使用数据库索引优化查询性能
- **C1**: 如果购物车商品数超过配置限制，Package 必须拒绝添加新商品并返回明确错误

### 3.2 兼容性要求
- **U11**: Package 必须支持 PHP 8.1 及以上版本
- **U12**: Package 必须兼容 Symfony 6.4 及以上版本
- **U13**: Package 必须支持 MySQL 5.7+ 和 PostgreSQL 10+ 数据库
- **U14**: Package 必须遵循 PSR-4 自动加载标准

### 3.3 扩展性要求
- **U15**: Package 必须提供商品验证器扩展点，允许自定义添加商品前的验证逻辑
- **U16**: Package 必须提供数量限制器扩展点，允许自定义商品数量限制规则
- **O2**: 在配置了自定义验证器的情况下，Package 必须在添加商品前执行验证
- **O3**: 在配置了数量限制器的情况下，Package 必须强制执行数量限制

### 3.4 安全性要求
- **U17**: Package 必须确保用户只能操作自己的购物车
- **U18**: Package 必须对所有输入进行验证和清理
- **U19**: Package 必须防止 SQL 注入攻击

## 4. 集成需求

### 4.1 与 order-checkout-bundle 的集成
- **U20**: Package 必须提供 CartDataProviderInterface 服务供结算模块调用
- **E5**: 当结算模块请求购物车数据时，Package 必须返回实时的商品信息
- **U21**: Package 必须支持结算模块绕过购物车进行直接商品结算（不创建临时购物车）

### 4.2 配置选项
```yaml
order_cart:
    # 购物车容量限制
    max_items_per_cart: 100
    
    # 商品在购物车中的保留天数
    item_retention_days: 30
    
    # 是否自动清理过期商品
    auto_clean_expired: true
    
    # 清理任务执行时间（cron 表达式）
    cleanup_schedule: '0 2 * * *'
    
    # 是否启用购物车事件
    enable_events: true
    
    # 数据库表前缀
    table_prefix: 'cart_'
```

### 4.3 服务注册
- **U22**: Package 必须自动注册所有必要的服务到 Symfony 容器
- **U23**: Package 必须使用自动装配减少配置复杂度
- **U24**: Package 必须提供 Symfony Flex recipe 简化安装

## 5. 数据模型需求

### 5.1 实体设计
```php
class CartItem
{
    private int $id;
    private UserInterface $user;
    private string $sku;
    private int $quantity;
    private bool $selected = true;
    private array $metadata = [];
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;
}
```

### 5.2 数据库设计
- **U25**: Package 必须使用 Doctrine ORM 管理数据持久化
- **U26**: Package 必须提供数据库迁移脚本
- **U27**: Package 必须在 user_id 和 sku 字段上创建复合唯一索引
- **U28**: Package 必须在 user_id 字段上创建索引以优化查询

## 6. 验收标准

### 6.1 功能验收
- ✅ 所有 CRUD 操作正常工作
- ✅ 选中状态管理功能完整
- ✅ 数据查询服务返回正确数据
- ✅ 事件系统正常触发
- ✅ 与 order-checkout-bundle 成功解耦

### 6.2 质量验收
- ✅ 单元测试覆盖率 ≥ 90%
- ✅ PHPStan Level 8 零错误
- ✅ 遵循 PSR-12 编码规范
- ✅ 所有公共 API 包含 PHPDoc 注释

### 6.3 性能验收
- ✅ 100 商品购物车查询 < 100ms
- ✅ 添加商品操作 < 50ms
- ✅ 批量操作支持事务

### 6.4 文档验收
- ✅ README 包含安装和基础使用说明
- ✅ 所有配置选项有详细说明
- ✅ 提供集成示例代码
- ✅ 包含升级指南（从耦合版本迁移）

## 7. 风险与约束

### 7.1 技术风险
- **数据迁移复杂性**：从现有耦合结构迁移数据可能需要复杂的迁移脚本
- **性能影响**：实时获取商品信息可能影响性能，需要合理的缓存策略

### 7.2 约束条件
- 必须保持与现有 monorepo 技术栈一致
- 不能破坏现有 order-checkout-bundle 的功能
- 需要提供平滑的迁移路径

## 8. 里程碑计划

### Phase 1: 基础架构（第1周）
- 创建 Bundle 结构
- 实现核心实体和仓储
- 实现基础 CRUD 操作

### Phase 2: 服务层实现（第2周）
- 实现 CartManagerInterface
- 实现 CartDataProviderInterface
- 集成事件系统

### Phase 3: API 层（第3周）
- 实现 JSON-RPC API
- 添加验证和错误处理
- 实现扩展点

### Phase 4: 集成与测试（第4周）
- 与 order-checkout-bundle 集成
- 编写完整测试套件
- 性能优化和调试

### Phase 5: 文档与发布（第5周）
- 编写用户文档
- 创建迁移指南
- 准备发布

## 9. 成功指标

- **解耦成功**：order-checkout-bundle 可以不依赖购物车进行直接商品结算
- **复用性提升**：至少 2 个其他模块成功集成购物车功能
- **性能达标**：所有性能指标满足要求
- **质量保证**：通过所有质量验收标准
- **开发者满意度**：API 设计获得团队认可，集成简单直观