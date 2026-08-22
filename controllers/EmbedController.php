<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\components\Controller;
use humhub\components\access\ControllerAccess;
use humhub\modules\space\models\Space;
use humhub\modules\stream\actions\ContentContainerStream;
use humhub\modules\thiscoveryPageBuilder\helpers\SpaceWidgets;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Stream and similar embeds for page-bound spaces (bypasses SpaceController wall lockdown).
 */
class EmbedController extends Controller
{
    protected $access = ControllerAccess::class;

    protected function getAccessRules()
    {
        return [];
    }

    public function actions()
    {
        return [];
    }

    public function actionStream($pageId)
    {
        $page = EngagementPage::findOne((int) $pageId);
        if ($page === null || !$page->canAccessPublic()) {
            throw new NotFoundHttpException();
        }

        $space = SpaceWidgets::resolveSpace($page);
        if (!$space instanceof Space) {
            throw new ForbiddenHttpException();
        }

        $action = Yii::createObject([
            'class' => ContentContainerStream::class,
            'id' => 'stream',
            'controller' => $this,
            'contentContainer' => $space,
        ]);

        return $action->runWithParams([]);
    }
}
