<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\controllers;

use humhub\components\Controller;
use humhub\components\access\ControllerAccess;
use humhub\modules\content\models\Content;
use humhub\modules\engagementPages\assets\EngagementPagesAsset;
use humhub\modules\engagementPages\helpers\Url;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\modules\engagementPages\models\PageComment;
use humhub\modules\engagementPages\models\PageFollow;
use humhub\modules\engagementPages\permissions\CreateGlobalPage;
use humhub\modules\engagementPages\permissions\ManageGlobalPage;
use humhub\modules\engagementPages\services\BlockRegistry;
use humhub\modules\thiscoveryForms\models\CustomForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Network-level engagement pages (primary admin path).
 * No Space required — pair with global Thiscovery Forms.
 */
class GlobalController extends Controller
{
    use SectionPostParserTrait;

    public $subLayout = '@engagement-pages/views/layouts/admin';

    protected $access = ControllerAccess::class;

    protected function getAccessRules()
    {
        return [
            ['login'],
        ];
    }

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'save-template' => ['POST'],
                    'moderate-comment' => ['POST'],
                    'delete-subscription' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        EngagementPagesAsset::register($this->view);

        $canManage = Yii::$app->user->can(ManageGlobalPage::class)
            || Yii::$app->user->can(CreateGlobalPage::class)
            || Yii::$app->user->isAdmin();

        if (!$canManage) {
            throw new ForbiddenHttpException();
        }

        // Make sure the editable public homepage exists for admins.
        EngagementPage::ensureDirectoryPage();

        $pages = EngagementPage::find()
            ->joinWith('content')
            ->andWhere(['content.contentcontainer_id' => null])
            ->andWhere(['engagement_page.is_template' => false])
            ->orderBy([
                'engagement_page.is_directory' => SORT_DESC,
                'engagement_page.updated_at' => SORT_DESC,
                'engagement_page.id' => SORT_DESC,
            ])
            ->all();

        $templates = EngagementPage::findTemplates(null);

        return $this->render('index', [
            'pages' => $pages,
            'templates' => $templates,
            'pendingComments' => PageComment::countPending(),
            'subscriptionCount' => PageFollow::countGlobal(),
            'canCreate' => Yii::$app->user->can(CreateGlobalPage::class) || Yii::$app->user->isAdmin(),
            'contentContainer' => null,
        ]);
    }

    public function actionComments($page_id = null, $status = 'all')
    {
        $this->requireManage();
        EngagementPagesAsset::register($this->view);

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
            Yii::$app->session->setFlash('success', Yii::t('EngagementPagesModule.base', 'Comment approved.'));
            // Stay on history (All) so moderated comments remain visible.
            return $this->redirect(Url::toGlobalComments('all', $filterPageId));
        }
        if ($action === 'reject') {
            $comment->reject($moderatorId);
            Yii::$app->session->setFlash('success', Yii::t('EngagementPagesModule.base', 'Comment rejected.'));
            return $this->redirect(Url::toGlobalComments('all', $filterPageId));
        }
        if ($action === 'delete') {
            $comment->delete();
            Yii::$app->session->setFlash('success', Yii::t('EngagementPagesModule.base', 'Comment deleted.'));
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
        EngagementPagesAsset::register($this->view);

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
            Yii::t('EngagementPagesModule.base', 'Email'),
            Yii::t('EngagementPagesModule.base', 'Page'),
            Yii::t('EngagementPagesModule.base', 'Public URL'),
            Yii::t('EngagementPagesModule.base', 'Subscribed'),
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
        Yii::$app->session->setFlash('success', Yii::t('EngagementPagesModule.base', 'Subscription removed.'));

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

    public function actionCreate($template_id = null)
    {
        if (!Yii::$app->user->can(CreateGlobalPage::class) && !Yii::$app->user->isAdmin()) {
            throw new ForbiddenHttpException();
        }

        $page = new EngagementPage();
        $page->status = EngagementPage::STATUS_DRAFT;
        $page->sections = $this->defaultSections();
        $page->content->visibility = Content::VISIBILITY_PUBLIC;

        $templateId = (int) ($template_id ?: Yii::$app->request->get('template_id', 0));
        if ($templateId > 0) {
            $template = EngagementPage::findOne(['id' => $templateId]);
            if ($template !== null && $template->isTemplate() && $template->isGlobal()) {
                $template->applyTemplateTo($page);
            }
        }

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
                Yii::t('EngagementPagesModule.base', 'The public homepage cannot be saved as a template.')
            );
            return $this->redirect(Url::toGlobalEdit($page));
        }

        $title = trim((string) Yii::$app->request->post('template_title', ''));
        try {
            $tpl = $page->saveAsTemplate($title !== '' ? $title : null);
            Yii::$app->session->setFlash(
                'success',
                Yii::t('EngagementPagesModule.base', 'Template “{title}” saved. You can create new pages from it.', [
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
        EngagementPagesAsset::register($this->view);

        $page = $this->findPage($id);
        if (!$page->isPublished() && !$page->canManage()) {
            throw new ForbiddenHttpException(Yii::t('EngagementPagesModule.base', 'This page is not published.'));
        }

        return $this->render('@engagement-pages/views/page/view', [
            'contentContainer' => null,
            'page' => $page,
            'canManage' => $page->canManage(),
            'publicLayout' => false,
            'isDirectory' => $page->isDirectoryHome(),
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
                Yii::t('EngagementPagesModule.base', 'The public homepage cannot be deleted. Edit it in the page builder instead.')
            );
            return $this->redirect(Url::toGlobalEdit($page));
        }
        $page->hardDelete();
        return $this->redirect(Url::toGlobalIndex());
    }

    protected function handleEdit(EngagementPage $page, bool $isNew)
    {
        EngagementPagesAsset::register($this->view);
        $request = Yii::$app->request;

        if ($request->isPost) {
            $page->load($request->post());
            if ($page->isTemplate()) {
                $page->is_template = true;
                $page->listed = false;
                $page->featured = false;
                $page->status = EngagementPage::STATUS_DRAFT;
            }
            $page->sections = $this->parseSectionsFromPost();
            $page->content->visibility = ((int) $page->status === EngagementPage::STATUS_PUBLISHED)
                ? Content::VISIBILITY_PUBLIC
                : Content::VISIBILITY_PRIVATE;

            if ($page->save()) {
                return $this->redirect(Url::toGlobalView($page));
            }
        }

        return $this->render('@engagement-pages/views/page/edit', [
            'contentContainer' => null,
            'page' => $page,
            'isNew' => $isNew,
            'blockLabels' => BlockRegistry::labels(),
            'formOptions' => $this->formOptions(),
            'templates' => EngagementPage::findTemplates(null),
        ]);
    }

    protected function formOptions(): array
    {
        $options = ['' => Yii::t('EngagementPagesModule.base', 'Select a form…')];
        if (!class_exists(CustomForm::class)) {
            return $options;
        }

        $forms = CustomForm::find()
            ->joinWith('content')
            ->andWhere(['content.contentcontainer_id' => null])
            ->orderBy(['custom_form.title' => SORT_ASC])
            ->all();

        foreach ($forms as $form) {
            $options[$form->id] = $form->title;
        }

        return $options;
    }

    protected function findPage($id): EngagementPage
    {
        $page = EngagementPage::findOne((int) $id);

        if ($page === null || !$page->isGlobal()) {
            throw new NotFoundHttpException(Yii::t('EngagementPagesModule.base', 'Page not found.'));
        }

        // Repair orphaned records (page row without content) so edit/view keep working.
        if ($page->content->isNewRecord) {
            $page->ensureContentRecord();
        }

        return $page;
    }
}
