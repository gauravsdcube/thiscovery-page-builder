<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryForms\models\CustomForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class PageController extends ContentContainerController
{
    use SectionPostParserTrait;
    use HelpTrait;

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
        ThiscoveryPageBuilderAsset::register($this->view);

        $probe = new EngagementPage($this->contentContainer);
        if (!$probe->canCreate() && !$probe->canManage()
            && !EngagementPage::find()->contentContainer($this->contentContainer)->count()) {
            throw new ForbiddenHttpException();
        }

        $pages = EngagementPage::find()
            ->alias('p')
            ->contentContainer($this->contentContainer)
            ->andWhere(['p.is_template' => false])
            ->orderBy(['p.updated_at' => SORT_DESC, 'p.id' => SORT_DESC])
            ->all();

        $templates = EngagementPage::findTemplates((int) $this->contentContainer->contentcontainer_id);

        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'pages' => $pages,
            'templates' => $templates,
            'canCreate' => $probe->canCreate(),
            'canViewHelp' => $this->canViewHelp(),
        ]);
    }

    public function actionCreate($template_id = null, $parent_id = null, $collection = null)
    {
        $page = new EngagementPage($this->contentContainer);
        if (!$page->canCreate()) {
            throw new ForbiddenHttpException();
        }

        $page->status = EngagementPage::STATUS_DRAFT;
        $page->sections = [];
        if ($page->hasAttribute('bound_space_id') && $this->contentContainer instanceof Space) {
            $page->bound_space_id = (int) $this->contentContainer->id;
        }

        $asCollection = (int) ($collection ?: Yii::$app->request->get('collection', 0)) === 1;
        if ($asCollection && $page->hasAttribute('is_collection')) {
            $page->is_collection = true;
            $page->listed = false;
        }

        $parentId = (int) ($parent_id ?: Yii::$app->request->get('parent_id', 0));
        if (!$asCollection && $parentId > 0 && $page->hasAttribute('parent_id')) {
            $parent = EngagementPage::find()
                ->alias('p')
                ->contentContainer($this->contentContainer)
                ->andWhere(['p.id' => $parentId])
                ->one();
            if ($parent instanceof EngagementPage && $parent->isCollection()) {
                $page->parent_id = $parent->id;
            }
        }

        $templateId = (int) ($template_id ?: Yii::$app->request->get('template_id', 0));
        if ($templateId > 0) {
            $template = EngagementPage::find()
                ->alias('p')
                ->contentContainer($this->contentContainer)
                ->andWhere(['p.id' => $templateId, 'p.is_template' => true])
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
                Yii::t('ThiscoveryPageBuilderModule.base', 'The public homepage cannot be saved as a template.')
            );
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
        ThiscoveryPageBuilderAsset::register($this->view);

        $page = $this->findPage($id);
        if ($page->isPublished() || $page->canManage() || $page->content->canView()) {
            // ok
        } else {
            throw new ForbiddenHttpException();
        }
        if (!$page->isPublished() && !$page->canManage()) {
            throw new ForbiddenHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'This page is not published.'));
        }

        return $this->render('view', [
            'contentContainer' => $this->contentContainer,
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
            throw new ForbiddenHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'The primary public collection cannot be deleted.'));
        }
        if ($page->isCollection() && $page->getChildren()->count() > 0) {
            throw new ForbiddenHttpException(
                Yii::t('ThiscoveryPageBuilderModule.base', 'Move or delete pages in this collection before deleting the collection.')
            );
        }
        $parentId = (int) $page->parent_id;
        $page->hardDelete();
        return $this->redirect(Url::toIndex($this->contentContainer, $parentId > 0 ? ['collection' => $parentId] : []));
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
            if ($page->hasAttribute('parent_id') && $page->isCollection()) {
                $page->parent_id = null;
            }
            if ($page->hasAttribute('bound_space_id')) {
                $bound = $request->post('EngagementPage')['bound_space_id'] ?? '';
                $page->bound_space_id = ($bound === '' || $bound === null) ? null : (int) $bound;
            }
            $page->sections = $this->parseSectionsFromPost();
            if ($page->save()) {
                return $this->redirectAfterStudioSave($page);
            }
        }

        $ccId = (int) $this->contentContainer->contentcontainer_id;

        return $this->render('edit', [
            'contentContainer' => $this->contentContainer,
            'page' => $page,
            'isNew' => $isNew,
            'blockLabels' => BlockRegistry::labels(),
            'formOptions' => $this->formOptions(),
            'pollOptions' => $this->pollOptions(),
            'mapOptions' => $this->mapOptions(),
            'templates' => EngagementPage::findTemplates($ccId),
            'collectionOptions' => EngagementPage::collectionOptions($ccId, $page->id),
            'spaceOptions' => EngagementPage::spaceOptions(),
            'pageOptions' => EngagementPage::publishedPageOptions($page->id),
            'groupOptions' => [],
            'pageHomes' => [],
        ]);
    }

    protected function formOptions(): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Select a form…')];
        if (!class_exists(CustomForm::class)) {
            return $options;
        }
        return $options + CustomForm::pickerOptions($this->contentContainer, null, true);
    }

    protected function pollOptions(): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Select a poll…')];
        if (!class_exists(CustomForm::class)) {
            return $options;
        }
        return $options + CustomForm::pickerOptions($this->contentContainer, CustomForm::KIND_POLL, true);
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
        return $options + \humhub\modules\thiscoveryMapping\models\Map::pickerOptions($this->contentContainer);
    }

    /**
     * Stay in the studio after save, or open the public preview.
     */
    protected function redirectAfterStudioSave(EngagementPage $page)
    {
        $after = (string) Yii::$app->request->post('after_save', 'stay');
        if ($after === 'preview') {
            if ($page->isTemplate()) {
                Yii::$app->session->setFlash(
                    'info',
                    Yii::t('ThiscoveryPageBuilderModule.base', 'Templates cannot be previewed publicly. Open the page builder Share tab after creating a page from this template.')
                );
                return $this->redirect(Url::toEdit($page));
            }
            return $this->redirect(Url::toPublic($page));
        }

        $tab = trim((string) Yii::$app->request->post('studio_tab', ''));
        $url = Url::toEdit($page);
        if ($tab !== '' && $tab !== 'builder') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'tab=' . rawurlencode($tab);
        }
        Yii::$app->session->setFlash(
            'success',
            Yii::t('ThiscoveryPageBuilderModule.base', 'Page saved.')
        );
        return $this->redirect($url);
    }

    protected function findPage($id): EngagementPage
    {
        $page = EngagementPage::find()
            ->alias('p')
            ->contentContainer($this->contentContainer)
            ->andWhere(['p.id' => (int) $id])
            ->one();

        if ($page === null) {
            throw new NotFoundHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'Page not found.'));
        }

        return $page;
    }
}
