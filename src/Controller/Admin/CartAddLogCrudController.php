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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\OrderCartBundle\Repository\CartAddLogRepository;

/**
 * 购物车加购记录管理控制器
 *
 * @extends AbstractCrudController<CartAddLog>
 */
#[AdminCrud(routePath: '/order-cart/cart-add-log', routeName: 'order_cart_cart_add_log')]
final class CartAddLogCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CartAddLogRepository $cartAddLogRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CartAddLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('购物车加购记录')
            ->setEntityLabelInPlural('购物车加购记录管理')
            ->setPageTitle('index', '购物车加购记录管理')
            ->setPageTitle('detail', '购物车加购记录详情')
            ->setPageTitle('new', '新增购物车加购记录')
            ->setPageTitle('edit', '编辑购物车加购记录')
            ->setHelp('index', '管理用户的购物车加购行为记录，包括添加、更新、删除等操作')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'user.username', 'sku.name', 'sku.code', 'cartItemId', 'action'])
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
            ->setHelp('执行此次加购操作的用户')
        ;

        // SKU关联字段
        yield AssociationField::new('sku', '商品SKU')
            ->setRequired(true)
            ->formatValue(fn ($value) => $this->formatSkuDisplay(is_object($value) ? $value : null))
            ->setHelp('被加购的商品SKU')
        ;

        // 购物车项ID字段
        yield TextField::new('cartItemId', '购物车项ID')
            ->setHelp('关联的购物车项ID')
            ->hideOnIndex()
        ;

        // 数量字段
        yield IntegerField::new('quantity', '加购数量')
            ->setRequired(true)
            ->setHelp('此次加购的商品数量')
            ->formatValue(function ($value): string {
                $quantity = is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);

                return number_format((float) $quantity) . ' 件';
            })
        ;

        // 操作类型字段
        yield ChoiceField::new('action', '操作类型')
            ->setChoices([
                '添加' => 'add',
                '更新' => 'update',
                '恢复' => 'restore',
            ])
            ->setRequired(true)
            ->setHelp('此次操作的类型')
            ->formatValue(function ($value): string {
                $labels = [
                    'add' => '添加',
                    'update' => '更新',
                    'restore' => '恢复',
                ];

                $action = is_string($value) ? $value : '';

                return $labels[$action] ?? $action;
            })
        ;

        // 是否已删除字段
        yield BooleanField::new('isDeleted', '是否已删除')
            ->setHelp('购物车项是否已被删除')
            ->renderAsSwitch(false)
            ->formatValue(function ($value) {
                return $value ? '是' : '否';
            })
        ;

        // 删除时间字段
        yield DateTimeField::new('deleteTime', '删除时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('购物车项被删除的时间')
            ->hideOnIndex()
        ;

        // 商品快照字段
        yield CodeEditorField::new('skuSnapshot', '商品快照')
            ->setLanguage('javascript')
            ->setHelp('保存的商品信息快照')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
        ;

        // 价格快照字段
        yield CodeEditorField::new('priceSnapshot', '价格快照')
            ->setLanguage('javascript')
            ->setHelp('保存的价格信息快照')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
        ;

        // 元数据字段
        yield CodeEditorField::new('metadata', '元数据')
            ->setLanguage('javascript')
            ->setHelp('存储的附加元数据信息')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
        ;

        // 创建时间字段
        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('记录创建时间')
        ;

        // 更新时间字段
        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('记录最后更新时间')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // 恢复删除标记操作
        $restoreAction = Action::new('restore', '恢复删除标记')
            ->linkToCrudAction('restore')
            ->setCssClass('btn btn-sm btn-success')
            ->setIcon('fa fa-undo')
            ->displayIf(fn (CartAddLog $entity): bool => $entity->isDeleted())
        ;

        // 标记为已删除操作
        $markDeletedAction = Action::new('markDeleted', '标记为已删除')
            ->linkToCrudAction('markDeleted')
            ->setCssClass('btn btn-sm btn-warning')
            ->setIcon('fa fa-trash')
            ->displayIf(fn (CartAddLog $entity): bool => !$entity->isDeleted())
        ;

        // 清理历史记录操作
        $cleanHistoryAction = Action::new('cleanHistory', '清理历史记录')
            ->linkToCrudAction('cleanHistory')
            ->setCssClass('btn btn-sm btn-danger')
            ->setIcon('fa fa-broom')
        ;

        // 添加详情操作
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        // 添加自定义操作
        $actions->add(Crud::PAGE_INDEX, $restoreAction);
        $actions->add(Crud::PAGE_INDEX, $markDeletedAction);
        $actions->add(Crud::PAGE_INDEX, $cleanHistoryAction);
        $actions->add(Crud::PAGE_DETAIL, $restoreAction);
        $actions->add(Crud::PAGE_DETAIL, $markDeletedAction);

        // 重新排序操作按钮 (只包含INDEX页面上可用的动作)
        $actions->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'restore', 'markDeleted', 'cleanHistory']);

        // 禁用新增操作，因为这些记录是系统自动生成的
        $actions->disable(Action::NEW);

        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // 用户筛选
            ->add(EntityFilter::new('user', '用户'))

            // SKU筛选
            ->add(EntityFilter::new('sku', '商品SKU'))

            // 购物车项ID筛选
            ->add(TextFilter::new('cartItemId', '购物车项ID'))

            // 数量筛选
            ->add(NumericFilter::new('quantity', '数量'))

            // 操作类型筛选
            ->add(ChoiceFilter::new('action', '操作类型')
                ->setChoices([
                    '添加' => 'add',
                    '更新' => 'update',
                    '恢复' => 'restore',
                ]))

            // 删除状态筛选
            ->add(BooleanFilter::new('isDeleted', '是否已删除'))

            // 删除时间筛选
            ->add(DateTimeFilter::new('deleteTime', '删除时间'))

            // 创建时间筛选
            ->add(DateTimeFilter::new('createTime', '创建时间'))

            // 更新时间筛选
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    /**
     * 恢复删除标记
     */
    #[AdminAction(routePath: '{entityId}/restore', routeName: 'restore')]
    public function restore(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity();
        if (null === $entity->getInstance()) {
            throw $this->createNotFoundException('Entity not found');
        }

        $cartAddLog = $entity->getInstance();
        assert($cartAddLog instanceof CartAddLog);

        $cartAddLog->unmarkDeleted();

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        $entityManager->flush();

        $this->addFlash('success', '购物车加购记录删除标记已恢复');

        $referer = $context->getRequest()->headers->get('referer');
        if (null === $referer) {
            return $this->redirectToRoute('admin');
        }

        return $this->redirect($referer);
    }

    /**
     * 标记为已删除
     */
    #[AdminAction(routePath: '{entityId}/markDeleted', routeName: 'mark_deleted')]
    public function markDeleted(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity();
        if (null === $entity->getInstance()) {
            throw $this->createNotFoundException('Entity not found');
        }

        $cartAddLog = $entity->getInstance();
        assert($cartAddLog instanceof CartAddLog);

        $cartAddLog->markAsDeleted();

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        $entityManager->flush();

        $this->addFlash('success', '购物车加购记录已标记为删除');

        $referer = $context->getRequest()->headers->get('referer');
        if (null === $referer) {
            return $this->redirectToRoute('admin');
        }

        return $this->redirect($referer);
    }

    /**
     * 清理历史记录
     */
    #[AdminAction(routePath: '{entityId}/cleanHistory', routeName: 'clean_history')]
    public function cleanHistory(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity();
        if (null === $entity->getInstance()) {
            throw $this->createNotFoundException('Entity not found');
        }

        $cartAddLog = $entity->getInstance();
        assert($cartAddLog instanceof CartAddLog);

        $user = $cartAddLog->getUser();

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();

        // 获取用户的所有已删除记录
        $deletedRecords = $this->cartAddLogRepository->findBy([
            'user' => $user,
            'isDeleted' => true,
        ]);

        $count = count($deletedRecords);

        // 批量删除
        foreach ($deletedRecords as $record) {
            $entityManager->remove($record);
        }
        $entityManager->flush();

        $userIdentifier = $this->getUserIdentifier($user);

        $this->addFlash('success', sprintf('已清理用户 %s 的历史记录，共删除 %d 条记录', $userIdentifier, $count));

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
