# order-cart-bundle

[English](README.md) | [中文](README.zh-CN.md)

购物车管理 Bundle，为电商系统提供完整的购物车功能

## 功能特性

- 🛒 完整的购物车管理功能
- 👤 多用户购物车支持
- 📦 商品SKU关联管理
- 🔢 数量和选中状态管理
- 📊 EasyAdmin 后台管理界面
- 🎯 灵活的事件系统
- 🔍 高级搜索和筛选
- ⚡ 性能优化的查询

## 安装

```bash
composer require tourze/order-cart-bundle
```

## EasyAdmin 后台管理

本 Bundle 提供了完整的 EasyAdmin 后台管理功能：

### 功能特性

- **购物车项目管理**: 查看、编辑、删除购物车项目
- **用户关联**: 显示购物车所属用户信息
- **商品信息**: 显示关联的SKU详情
- **自定义操作**: 
  - 切换选中状态
  - 清空用户购物车
- **高级筛选**: 按用户、SKU、数量、状态、时间筛选
- **搜索功能**: 支持ID、用户名、SKU名称搜索

### 访问方式

后台管理界面位于：`/admin/order-cart/cart-item`

菜单路径：**订单管理** → **购物车管理**

### 详细文档

更多后台管理功能请参考：[EasyAdmin 管理指南](docs/admin-guide.md)

## 快速开始

### 1. 添加购物车项目

```php
use Tourze\OrderCartBundle\Service\CartManager;

// 注入服务
public function __construct(private CartManager $cartManager) {}

// 添加商品到购物车
$this->cartManager->addItem($user, $sku, $quantity);
```

### 2. 获取购物车数据

```php
use Tourze\OrderCartBundle\Service\CartDataProvider;

// 获取用户购物车
$cartData = $this->cartDataProvider->getCartData($user);

// 获取购物车摘要
$summary = $this->cartDataProvider->getCartSummary($user);
```

## 贡献

详情请参阅 [CONTRIBUTING.md](CONTRIBUTING.md)。

## 许可

MIT 许可证 (MIT)。详情请参阅 [许可文件](LICENSE)。
