<?php

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryPageBuilder\permissions\CreateGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\CreatePage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManageGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManagePages;
use humhub\modules\thiscoveryPageBuilder\services\HelpService;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * In-product Help for page creators and administrators.
 */
trait HelpTrait
{
    protected function helpContainer()
    {
        return property_exists($this, 'contentContainer') ? $this->contentContainer : null;
    }

    public function canViewHelp(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        if (Yii::$app->user->isAdmin() || Yii::$app->user->can(ManageModules::class)) {
            return true;
        }

        $container = $this->helpContainer();
        if ($container instanceof Space) {
            $pm = $container->getPermissionManager();
            return $pm->can(CreatePage::class) || $pm->can(ManagePages::class);
        }

        return Yii::$app->user->can(CreateGlobalPage::class)
            || Yii::$app->user->can(ManageGlobalPage::class);
    }

    public function actionHelp($page = null)
    {
        if (!$this->canViewHelp()) {
            throw new ForbiddenHttpException();
        }

        $container = $this->helpContainer();
        $page = trim((string)$page);
        if ($page !== '') {
            $article = HelpService::render($page, $container);
            if (!$article) {
                throw new NotFoundHttpException();
            }
            return $this->render('@thiscovery-page-builder/views/help/page', [
                'article' => $article,
                'contentContainer' => $container,
                'sections' => HelpService::sections(),
                'pages' => HelpService::pages(),
            ]);
        }

        return $this->render('@thiscovery-page-builder/views/help/index', [
            'contentContainer' => $container,
            'sections' => HelpService::sections(),
            'pages' => HelpService::pages(),
        ]);
    }
}
