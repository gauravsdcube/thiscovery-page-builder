<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\content\models\Content;
use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageComment;
use humhub\modules\thiscoveryPageBuilder\models\PageFollow;
use humhub\modules\thiscoveryPageBuilder\permissions\CreateGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManageGlobalPage;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;
use humhub\modules\thiscoveryPageBuilder\services\PageFolderService;
use humhub\modules\thiscoveryForms\models\CustomForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Network-level engagement pages (primary admin path).
 * Uses the Administration layout (left admin menu), same as Thiscovery Forms.
 * No Space required — pair with global Thiscovery Forms.
 */
class GlobalController extends Controller
{
    use SectionPostParserTrait;
    use HelpTrait;
    use ThemeAdminTrait;
    use VersioningTrait;
    use PageStudioSaveTrait;
    use FolderTrait;

    /**
     * @inheritdoc
     */
    public $adminOnly = false;

    protected function getAccessRules()
    {
        return [
            ['login'],
        ];
    }

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'save-template' => ['POST'],
                    'moderate-comment' => ['POST'],
                    'delete-subscription' => ['POST'],
                    'theme-delete' => ['POST'],
                    'publish-version' => ['POST'],
                    'restore-version' => ['POST'],
                    'delete-revision' => ['POST'],
                    'delete-edition' => ['POST'],
                    'folder-delete' => ['POST'],
                ],
            ],
        ]);
    }

    public function actionIndex()
    {
        ThiscoveryPageBuilderAsset::register($this->view);

        $canManage = Yii::$app->user->can(ManageGlobalPage::class)
            || Yii::$app->user->can(CreateGlobalPage::class)
            || Yii::$app->user->isAdmin();

        if (!$canManage) {
            throw new ForbiddenHttpException();
        }

        // Make sure the editable public homepage exists for admins.
        EngagementPage::ensureDirectoryPage();

        $pages = EngagementPage::find()
            ->alias('p')
            ->joinWith('content')
            ->andWhere(['content.contentcontainer_id' => null])
            ->andWhere(['p.is_template' => false])
            ->orderBy([
                'p.is_collection' => SORT_DESC,
                'p.is_directory' => SORT_DESC,
                'p.parent_id' => SORT_ASC,
                'p.title' => SORT_ASC,
                'p.id' => SORT_DESC,
            ])
            ->all();

        $templates = EngagementPage::findTemplates(null);

        return $this->render('@thiscovery-page-builder/views/page/index', [
            'pages' => $pages,
            'templates' => $templates,
            'pendingComments' => PageComment::countPending(),
            'subscriptionCount' => PageFollow::countGlobal(),
            'canCreate' => Yii::$app->user->can(CreateGlobalPage::class) || Yii::$app->user->isAdmin(),
            'contentContainer' => null,
            'canViewHelp' => $this->canViewHelp(),
            'foldersReady' => PageFolderService::tablesReady(),
            'canManageFolders' => $canManage,
        ]);
    }

    public function actionComments($page_id = null, $status = 'all')
    {
        $this->requireManage();
        ThiscoveryPageBuilderAsset::register($this->view);

        $statusMap = [
            'pending' => PageComment::STATUS_PENDING,
            'approved' => PageComment::STATUS_APPROVED,
            'rejected' => PageComment::STATUS_REJECTED,
            'all' => null,
        ];
        $statusKey = array_key_exists((string) $status, $statusMap) ? (string) $status : 'all';
        $statusValue = $statusMap[$statusKey];
        $pageId = $page_id ? (int) $page_id : null;

        $query = PageComment::find()
            ->alias('c')
            ->joinWith(['page p'])
            ->orderBy(['c.created_at' => SORT_DESC, 'c.id' => SORT_DESC]);

        if ($statusValue !== null) {
            $query->andWhere(['c.status' => $statusValue]);
        }
        if ($pageId) {
            $query->andWhere(['c.page_id' => $pageId]);
        }

        $comments = $query->limit(200)->all();

        return $this->render('comments', [
            'comments' => $comments,
            'status' => $statusKey,
            'pageId' => $pageId,
            'pendingCount' => PageComment::countPending($pageId),
            'approvedCount' => PageComment::countByStatus(PageComment::STATUS_APPROVED, $pageId),
            'rejectedCount' => PageComment::countByStatus(PageComment::STATUS_REJECTED, $pageId),
            'allCount' => PageComment::countByStatus(null, $pageId),
            'statusOptions' => PageComment::statusOptions(),
        ]);
    }

    public function actionModerateComment($id)
    {
        $this->requireManage();
        $comment = PageComment::findOne((int) $id);
        if ($comment === null) {
            throw new NotFoundHttpException();
        }

        $action = (string) Yii::$app->request->post('moderate_action', '');
        $moderatorId = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;
        $filterPageId = Yii::$app->request->post('filter_page_id');
        $filterPageId = ($filterPageId === '' || $filterPageId === null)
            ? null
            : (int) $filterPageId;
        $filterStatus = (string) Yii::$app->request->post('filter_status', 'all');

        if ($action === 'approve') {
            $comment->approve($moderatorId);
            Yii::$app->session->setFlash('success', Yii::t('ThiscoveryPageBuilderModule.base', 'Comment approved.'));
            // Stay on history (All) so moderated comments remain visible.
            return $this->redirect(Url::toGlobalComments('all', $filterPageId));
        }
        if ($action === 'reject') {
            $comment->reject($moderatorId);
            Yii::$app->session->setFlash('success', Yii::t('ThiscoveryPageBuilderModule.base', 'Comment rejected.'));
            return $this->redirect(Url::toGlobalComments('all', $filterPageId));
        }
        if ($action === 'delete') {
            $comment->delete();
            Yii::$app->session->setFlash('success', Yii::t('ThiscoveryPageBuilderModule.base', 'Comment deleted.'));
        }

        $return = Yii::$app->request->post('return_url');
        if (is_string($return) && $return !== '') {
            return $this->redirect($return);
        }
        return $this->redirect(Url::toGlobalComments($filterStatus ?: 'all', $filterPageId));
    }

    public function actionSubscriptions($page_id = null)
    {
        $this->requireManage();
        ThiscoveryPageBuilderAsset::register($this->view);

        $pageId = $page_id ? (int) $page_id : null;
        $follows = PageFollow::findGlobalQuery($pageId)->all();

        return $this->render('subscriptions', [
            'follows' => $follows,
            'pageId' => $pageId,
            'totalCount' => PageFollow::countGlobal($pageId),
            'pageOptions' => PageFollow::globalPageFilterOptions(),
        ]);
    }

    public function actionExportSubscriptions($page_id = null)
    {
        $this->requireManage();

        $pageId = $page_id ? (int) $page_id : null;
        $follows = PageFollow::findGlobalQuery($pageId)->all();

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, [
            Yii::t('ThiscoveryPageBuilderModule.base', 'Email'),
            Yii::t('ThiscoveryPageBuilderModule.base', 'Page'),
            Yii::t('ThiscoveryPageBuilderModule.base', 'Public URL'),
            Yii::t('ThiscoveryPageBuilderModule.base', 'Subscribed'),
        ]);
        foreach ($follows as $follow) {
            $page = $follow->page;
            $publicUrl = '';
            $title = '';
            if ($page !== null) {
                $title = (string) $page->title;
                $publicUrl = Url::toPublic($page, true);
            }
            fputcsv($handle, [
                $follow->email,
                $title,
                $publicUrl,
                (string) ($follow->created_at ?? ''),
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'page-subscriptions-' . date('Y-m-d') . '.csv';
        if ($pageId && $follows !== []) {
            $slug = $follows[0]->page->slug ?? null;
            if ($slug) {
                $filename = 'page-subscriptions-' . $slug . '-' . date('Y-m-d') . '.csv';
            }
        }

        return Yii::$app->response->sendContentAsFile($csv, $filename, [
            'mimeType' => 'text/csv',
            'inline' => false,
        ]);
    }

    public function actionDeleteSubscription($id)
    {
        $this->requireManage();
        $follow = PageFollow::findOne((int) $id);
        if ($follow === null) {
            throw new NotFoundHttpException();
        }
        $page = $follow->page;
        if ($page === null || !$page->isGlobal()) {
            throw new ForbiddenHttpException();
        }

        $follow->delete();
        Yii::$app->session->setFlash('success', Yii::t('ThiscoveryPageBuilderModule.base', 'Subscription removed.'));

        $filterPageId = Yii::$app->request->post('filter_page_id');
        $filterPageId = ($filterPageId === '' || $filterPageId === null)
            ? null
            : (int) $filterPageId;

        return $this->redirect(Url::toGlobalSubscriptions($filterPageId));
    }

    protected function requireManage(): void
    {
        $canManage = Yii::$app->user->can(ManageGlobalPage::class)
            || Yii::$app->user->can(CreateGlobalPage::class)
            || Yii::$app->user->isAdmin();
        if (!$canManage) {
            throw new ForbiddenHttpException();
        }
    }

    public function actionCreate($template_id = null, $parent_id = null, $collection = null)
    {
        if (!Yii::$app->user->can(CreateGlobalPage::class) && !Yii::$app->user->isAdmin()) {
            throw new ForbiddenHttpException();
        }

        $page = new EngagementPage();
        $page->status = EngagementPage::STATUS_DRAFT;
        $page->sections = [];
        $page->content->visibility = Content::VISIBILITY_PUBLIC;

        $asCollection = (int) ($collection ?: Yii::$app->request->get('collection', 0)) === 1;
        if ($asCollection && $page->hasAttribute('is_collection')) {
            $page->is_collection = true;
            $page->listed = false;
        }

        $parentId = (int) ($parent_id ?: Yii::$app->request->get('parent_id', 0));
        if (!$asCollection && $parentId > 0 && $page->hasAttribute('parent_id')) {
            $parent = EngagementPage::findOne($parentId);
            if ($parent !== null && $parent->isCollection() && $parent->isGlobal()) {
                $page->parent_id = $parent->id;
            }
        }

        $templateId = (int) ($template_id ?: Yii::$app->request->get('template_id', 0));
        if ($templateId > 0) {
            $template = EngagementPage::findOne(['id' => $templateId]);
            if ($template !== null && $template->isTemplate() && $template->isGlobal()) {
                $template->applyTemplateTo($page);
            }
        }

        $this->applyFolderFromRequest($page);

        return $this->handleEdit($page, true);
    }

    public function actionSaveTemplate($id)
    {
        $page = $this->findPage($id);
        if (!$page->canManage()) {
            throw new ForbiddenHttpException();
        }
        if ($page->isDirectoryHome()) {
            Yii::$app->session->setFlash(
                'error',
                Yii::t('ThiscoveryPageBuilderModule.base', 'The public homepage cannot be saved as a template.')
            );
            return $this->redirect(Url::toGlobalEdit($page));
        }

        $title = trim((string) Yii::$app->request->post('template_title', ''));
        try {
            $tpl = $page->saveAsTemplate($title !== '' ? $title : null);
            Yii::$app->session->setFlash(
                'success',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Template “{title}” saved. You can create new pages from it.', [
                    'title' => $tpl->title,
                ])
            );
            return $this->redirect(Url::toGlobalEdit($tpl));
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(Url::toGlobalEdit($page));
        }
    }

    public function actionEdit($id)
    {
        $page = $this->findPage($id);
        if (!$page->canManage()) {
            throw new ForbiddenHttpException();
        }

        return $this->handleEdit($page, false);
    }

    public function actionView($id)
    {
        ThiscoveryPageBuilderAsset::register($this->view);

        $page = $this->findPage($id);
        if (!$page->isPublished() && !$page->canManage()) {
            throw new ForbiddenHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'This page is not published.'));
        }

        return $this->render('@thiscovery-page-builder/views/page/view', [
            'contentContainer' => null,
            'page' => $page,
            'canManage' => $page->canManage(),
            'publicLayout' => false,
            'isDirectory' => $page->isCollection(),
        ]);
    }

    public function actionDelete($id)
    {
        $page = $this->findPage($id);
        if (!$page->canManage()) {
            throw new ForbiddenHttpException();
        }
        if ($page->isDirectoryHome()) {
            Yii::$app->session->setFlash(
                'error',
                Yii::t('ThiscoveryPageBuilderModule.base', 'The primary public collection cannot be deleted. Edit it in the page builder instead.')
            );
            return $this->redirect(Url::toGlobalEdit($page));
        }
        if ($page->isCollection() && $page->getChildren()->count() > 0) {
            Yii::$app->session->setFlash(
                'error',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Move or delete pages in this collection before deleting the collection.')
            );
            return $this->redirect(Url::toGlobalEdit($page));
        }
        $parentId = (int) $page->parent_id;
        $page->hardDelete();
        return $this->redirect(Url::toIndex(null, $parentId > 0 ? ['collection' => $parentId] : []));
    }

    protected function handleEdit(EngagementPage $page, bool $isNew)
    {
        ThiscoveryPageBuilderAsset::register($this->view);
        $request = Yii::$app->request;

        if ($request->isPost) {
            $page->load($request->post());
            if ($page->isTemplate()) {
                $page->is_template = true;
                $page->listed = false;
                $page->featured = false;
                $page->status = EngagementPage::STATUS_DRAFT;
            }
            if ($page->hasAttribute('is_collection') && $request->post('EngagementPage') !== null) {
                // Keep collection flag from hidden field / checkbox.
            }
            if ($page->hasAttribute('parent_id') && $page->isCollection()) {
                $page->parent_id = null;
            }
            if ($page->hasAttribute('bound_space_id')) {
                $bound = $request->post('EngagementPage')['bound_space_id'] ?? '';
                $page->bound_space_id = ($bound === '' || $bound === null) ? null : (int) $bound;
            }
            $page->sections = $this->parseSectionsFromPost();
            $this->applyPostedAppearance($page);
            $page->content->visibility = ((int) $page->status === EngagementPage::STATUS_PUBLISHED)
                ? Content::VISIBILITY_PUBLIC
                : Content::VISIBILITY_PRIVATE;

            if ($page->save()) {
                $this->recordPageVersion($page);
                $this->syncPageHomes($page);
                $postedHomes = Yii::$app->request->post('PageHome', []);
                $homeEnabled = false;
                if (is_array($postedHomes)) {
                    foreach ($postedHomes as $row) {
                        if (!empty($row['enabled'])) {
                            $homeEnabled = true;
                            break;
                        }
                    }
                }
                if ($homeEnabled && !$page->isPublished()) {
                    Yii::$app->session->setFlash(
                        'warning',
                        Yii::t(
                            'ThiscoveryPageBuilderModule.base',
                            'Homepage assignment was saved, but this page is still a draft. Publish it for the site homepage to take effect.'
                        )
                    );
                }
                return $this->redirectAfterStudioSave($page);
            }
        }

        return $this->render('@thiscovery-page-builder/views/page/edit', [
            'contentContainer' => null,
            'page' => $page,
            'isNew' => $isNew,
            'blockLabels' => BlockRegistry::labels(),
            'formOptions' => $this->formOptions(),
            'pollOptions' => $this->pollOptions(),
            'mapOptions' => $this->mapOptions(),
            'templates' => EngagementPage::findTemplates(null),
            'collectionOptions' => EngagementPage::collectionOptions(null, $page->id),
            'spaceOptions' => EngagementPage::spaceOptions(),
            'pageOptions' => EngagementPage::publishedPageOptions($page->id),
            'groupOptions' => $this->groupOptions(),
            'pageHomes' => $page->isNewRecord ? [] : $page->pageHomes,
            'folderOptions' => PageFolderService::tablesReady()
                ? PageFolderService::treeOptions(null)
                : [],
        ]);
    }

    protected function syncPageHomes(EngagementPage $page): void
    {
        if ($page->isTemplate() || !class_exists(\humhub\modules\thiscoveryPageBuilder\models\PageHome::class)) {
            return;
        }
        $posted = Yii::$app->request->post('PageHome', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        \humhub\modules\thiscoveryPageBuilder\models\PageHome::syncForPage($page, $posted);
    }

    protected function groupOptions(): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Select a group…')];
        foreach (\humhub\modules\user\models\Group::find()->orderBy(['name' => SORT_ASC])->all() as $group) {
            $options[(string) $group->id] = $group->name;
        }
        return $options;
    }

    protected function defaultCollectionSections(): array
    {
        return BlockRegistry::normalizeSections([
            [
                'type' => 'hero',
                'region' => BlockRegistry::REGION_FULL,
                'settings' => [
                    'headline' => Yii::t('ThiscoveryPageBuilderModule.base', 'New collection'),
                    'subheadline' => '',
                ],
            ],
            [
                'type' => 'collection',
                'region' => BlockRegistry::REGION_MAIN,
                'settings' => [
                    'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Pages'),
                    'source' => 'pages',
                ],
            ],
        ]);
    }

    protected function formOptions(): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Select a form…')];
        if (!class_exists(CustomForm::class)) {
            return $options;
        }
        return $options + CustomForm::pickerOptions(null, null, true);
    }

    protected function pollOptions(): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Select a poll…')];
        if (!class_exists(CustomForm::class)) {
            return $options;
        }
        return $options + CustomForm::pickerOptions(null, CustomForm::KIND_POLL, true);
    }

    protected function mapOptions(): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Select a map…')];
        if (!\humhub\modules\thiscoveryPageBuilder\helpers\MappingAvailability::isEnabled()) {
            return $options;
        }
        if (!class_exists(\humhub\modules\thiscoveryMapping\models\Map::class)) {
            return $options;
        }
        return $options + \humhub\modules\thiscoveryMapping\models\Map::pickerOptions(null);
    }

    protected function findPage($id): EngagementPage
    {
        $page = EngagementPage::findOne((int) $id);

        if ($page === null || !$page->isGlobal()) {
            throw new NotFoundHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'Page not found.'));
        }

        // Repair orphaned records (page row without content) so edit/view keep working.
        if ($page->content->isNewRecord) {
            $page->ensureContentRecord();
        }

        return $page;
    }
}
