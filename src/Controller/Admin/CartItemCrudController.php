<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Controller\Admin;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Repository\CartItemRepository;

/**
 * 购物车管理控制器
 *
 * @extends AbstractCrudController<CartItem>
 */
#[AdminCrud(routePath: '/order-cart/cart-item', routeName: 'order_cart_cart_item')]
final class CartItemCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CartItemRepository $cartItemRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CartItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('购物车项目')
            ->setEntityLabelInPlural('购物车管理')
            ->setPageTitle('index', '购物车管理')
            ->setPageTitle('detail', '购物车详情')
            ->setPageTitle('new', '新增购物车项目')
            ->setPageTitle('edit', '编辑购物车项目')
            ->setHelp('index', '管理用户购物车中的商品项目，可以查看、编辑数量和选中状态')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'user.username', 'sku.name', 'sku.code'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // ID字段
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->onlyOnIndex()
        ;

        // 用户关联字段
        yield AssociationField::new('user', '用户')
            ->setRequired(true)
            ->formatValue(fn ($value) => $this->formatUserDisplay(is_object($value) ? $value : null))
        ;

        // SKU关联字段
        yield AssociationField::new('sku', '商品SKU')
            ->setRequired(true)
            ->formatValue(fn ($value) => $this->formatSkuDisplay(is_object($value) ? $value : null))
        ;

        // 数量字段
        yield IntegerField::new('quantity', '数量')
            ->setRequired(true)
            ->setHelp('购物车中该商品的数量')
            ->formatValue(function ($value): string {
                $quantity = is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);

                return number_format((float) $quantity) . ' 件';
            })
        ;

        // 选中状态字段
        yield BooleanField::new('selected', '选中状态')
            ->setHelp('是否选中参与结算')
            ->renderAsSwitch(true)
        ;

        // 元数据字段
        yield CodeEditorField::new('metadata', '元数据')
            ->setLanguage('javascript')
            ->setHelp('存储额外的购物车项目信息')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
        ;

        // 创建时间字段
        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        // 更新时间字段
        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // 切换选中状态操作
        $toggleSelectedAction = Action::new('toggleSelected', '切换选中')
            ->linkToCrudAction('toggleSelected')
            ->setCssClass('btn btn-sm btn-warning')
            ->setIcon('fa fa-check-square')
        ;

        // 清空用户购物车操作
        $clearUserCartAction = Action::new('clearUserCart', '清空用户购物车')
            ->linkToCrudAction('clearUserCart')
            ->setCssClass('btn btn-sm btn-danger')
            ->setIcon('fa fa-trash')
        ;

        // 添加详情操作
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        // 添加自定义操作
        $actions->add(Crud::PAGE_INDEX, $toggleSelectedAction);
        $actions->add(Crud::PAGE_INDEX, $clearUserCartAction);
        $actions->add(Crud::PAGE_DETAIL, $toggleSelectedAction);

        // 重新排序操作按钮 (只包含INDEX页面可用的动作)
        $actions->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'toggleSelected', 'clearUserCart']);

        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // 用户筛选
            ->add(EntityFilter::new('user', '用户'))

            // SKU筛选
            ->add(EntityFilter::new('sku', '商品SKU'))

            // 数量筛选
            ->add(NumericFilter::new('quantity', '数量'))

            // 选中状态筛选
            ->add(BooleanFilter::new('selected', '选中状态'))

            // 创建时间筛选
            ->add(DateTimeFilter::new('createTime', '创建时间'))

            // 更新时间筛选
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    /**
     * 切换购物车项目选中状态
     */
    #[AdminAction(routePath: '{entityId}/toggleSelected', routeName: 'toggle_selected')]
    public function toggleSelected(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity();
        if (null === $entity->getInstance()) {
            throw $this->createNotFoundException('Entity not found');
        }

        $cartItem = $entity->getInstance();
        assert($cartItem instanceof CartItem);

        $cartItem->setSelected(!$cartItem->isSelected());

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        $entityManager->flush();

        $status = $cartItem->isSelected() ? '选中' : '取消选中';
        $this->addFlash('success', sprintf('购物车项目已%s', $status));

        $referer = $context->getRequest()->headers->get('referer');
        if (null === $referer) {
            return $this->redirectToRoute('admin');
        }

        return $this->redirect($referer);
    }

    /**
     * 清空指定用户的购物车
     */
    #[AdminAction(routePath: '{entityId}/clearUserCart', routeName: 'clear_user_cart')]
    public function clearUserCart(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity();
        if (null === $entity->getInstance()) {
            throw $this->createNotFoundException('Entity not found');
        }

        $cartItem = $entity->getInstance();
        assert($cartItem instanceof CartItem);

        $user = $cartItem->getUser();

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();

        // 获取用户的所有购物车项目
        $userCartItems = $this->cartItemRepository->findBy(['user' => $user]);

        $count = count($userCartItems);

        // 批量删除
        foreach ($userCartItems as $item) {
            $entityManager->remove($item);
        }
        $entityManager->flush();

        $userIdentifier = $this->getUserIdentifier($user);

        $this->addFlash('success', sprintf('已清空用户 %s 的购物车，共删除 %d 个项目', $userIdentifier, $count));

        $referer = $context->getRequest()->headers->get('referer');
        if (null === $referer) {
            return $this->redirectToRoute('admin');
        }

        return $this->redirect($referer);
    }

    /**
     * 自定义查询构建器，优化关联查询性能
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.user', 'user')
            ->leftJoin('entity.sku', 'sku')
            ->addSelect('user', 'sku')
            ->orderBy('entity.id', 'DESC')
        ;
    }

    /**
     * 格式化用户显示名称
     *
     * 由于需要兼容多种用户实现且无共同接口，此方法认知复杂度略高但逻辑清晰
     */
    private function formatUserDisplay(?object $user): string
    {
        if (null === $user) {
            return '-';
        }

        if (method_exists($user, 'getUsername') && is_string($username = $user->getUsername())) {
            return $username;
        }

        if (method_exists($user, 'getName') && is_string($name = $user->getName())) {
            return $name;
        }

        if (method_exists($user, 'getEmail') && is_string($email = $user->getEmail())) {
            return $email;
        }

        if (method_exists($user, '__toString')) {
            return (string) $user;
        }

        return get_class($user);
    }

    /**
     * 格式化SKU显示名称
     */
    private function formatSkuDisplay(?object $sku): string
    {
        if (null === $sku) {
            return '-';
        }

        $name = method_exists($sku, 'getName') ? $sku->getName() : '';
        $name = is_string($name) ? $name : '';

        $code = method_exists($sku, 'getCode') ? $sku->getCode() : '';
        $code = is_string($code) ? $code : '';

        return $name . ('' !== $code ? " ({$code})" : '');
    }

    /**
     * 获取用户标识符 - 使用卫语句消除深度嵌套
     */
    private function getUserIdentifier(?object $user): string
    {
        if (null === $user) {
            return '用户';
        }

        if (method_exists($user, 'getUsername')) {
            $username = $user->getUsername();
            if (is_string($username)) {
                return $username;
            }
        }

        if (method_exists($user, 'getName')) {
            $name = $user->getName();
            if (is_string($name)) {
                return $name;
            }
        }

        if (method_exists($user, 'getEmail')) {
            $email = $user->getEmail();
            if (is_string($email)) {
                return $email;
            }
        }

        return '用户';
    }
}
