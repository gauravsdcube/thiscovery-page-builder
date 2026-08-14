<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\engagementPages\assets\EngagementPagesAsset;
use humhub\modules\engagementPages\helpers\Url;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\modules\engagementPages\services\BlockRegistry;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryForms\models\CustomForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class PageController extends ContentContainerController
{
    use SectionPostParserTrait;

    public $validContentContainerClasses = [Space::class];

    protected function getAccessRules()
    {
        return [
            ['guestAccess' => ['view']],
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
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        EngagementPagesAsset::register($this->view);

        $probe = new EngagementPage($this->contentContainer);
        if (!$probe->canCreate() && !$probe->canManage()
            && !EngagementPage::find()->contentContainer($this->contentContainer)->count()) {
            throw new ForbiddenHttpException();
        }

        $pages = EngagementPage::find()
            ->contentContainer($this->contentContainer)
            ->andWhere(['engagement_page.is_template' => false])
            ->orderBy(['engagement_page.updated_at' => SORT_DESC, 'engagement_page.id' => SORT_DESC])
            ->all();

        $templates = EngagementPage::findTemplates((int) $this->contentContainer->contentcontainer_id);

        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'pages' => $pages,
            'templates' => $templates,
            'canCreate' => $probe->canCreate(),
        ]);
    }

    public function actionCreate($template_id = null)
    {
        $page = new EngagementPage($this->contentContainer);
        if (!$page->canCreate()) {
            throw new ForbiddenHttpException();
        }

        $page->status = EngagementPage::STATUS_DRAFT;
        $page->sections = $this->defaultSections();

        $templateId = (int) ($template_id ?: Yii::$app->request->get('template_id', 0));
        if ($templateId > 0) {
            $template = EngagementPage::find()
                ->contentContainer($this->contentContainer)
                ->andWhere(['engagement_page.id' => $templateId, 'engagement_page.is_template' => true])
                ->one();
            if ($template instanceof EngagementPage) {
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
            throw new ForbiddenHttpException(
                Yii::t('EngagementPagesModule.base', 'The public homepage cannot be saved as a template.')
            );
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
            return $this->redirect(Url::toEdit($tpl));
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(Url::toEdit($page));
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
        if ($page->isPublished() || $page->canManage() || $page->content->canView()) {
            // ok
        } else {
            throw new ForbiddenHttpException();
        }
        if (!$page->isPublished() && !$page->canManage()) {
            throw new ForbiddenHttpException(Yii::t('EngagementPagesModule.base', 'This page is not published.'));
        }

        return $this->render('view', [
            'contentContainer' => $this->contentContainer,
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
            throw new ForbiddenHttpException(Yii::t('EngagementPagesModule.base', 'The public homepage cannot be deleted.'));
        }
        $page->hardDelete();
        return $this->redirect(Url::toIndex($this->contentContainer));
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
            if ($page->save()) {
                return $this->redirect(Url::toViewInSpace($page));
            }
        }

        return $this->render('edit', [
            'contentContainer' => $this->contentContainer,
            'page' => $page,
            'isNew' => $isNew,
            'blockLabels' => BlockRegistry::labels(),
            'formOptions' => $this->formOptions(),
            'templates' => EngagementPage::findTemplates((int) $this->contentContainer->contentcontainer_id),
        ]);
    }

    protected function formOptions(): array
    {
        $options = ['' => Yii::t('EngagementPagesModule.base', 'Select a form…')];
        if (!class_exists(CustomForm::class)) {
            return $options;
        }

        // Prefer listing global forms first so public surveys do not require Space membership.
        $globalForms = CustomForm::find()
            ->joinWith('content')
            ->andWhere(['content.contentcontainer_id' => null])
            ->orderBy(['custom_form.title' => SORT_ASC])
            ->all();
        foreach ($globalForms as $form) {
            $options[$form->id] = Yii::t('EngagementPagesModule.base', 'Global') . ': ' . $form->title;
        }

        $spaceForms = CustomForm::find()
            ->contentContainer($this->contentContainer)
            ->orderBy(['custom_form.title' => SORT_ASC])
            ->all();
        foreach ($spaceForms as $form) {
            $options[$form->id] = $form->title;
        }

        return $options;
    }

    protected function findPage($id): EngagementPage
    {
        $page = EngagementPage::find()
            ->contentContainer($this->contentContainer)
            ->andWhere(['engagement_page.id' => (int) $id])
            ->one();

        if ($page === null) {
            throw new NotFoundHttpException(Yii::t('EngagementPagesModule.base', 'Page not found.'));
        }

        return $page;
    }
}
