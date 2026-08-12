<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages;

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\engagementPages\permissions\CreateGlobalPage;
use humhub\modules\engagementPages\permissions\ManageGlobalPage;
use humhub\modules\space\controllers\SpaceController;
use humhub\modules\space\models\Space;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\space\widgets\Menu;
use Yii;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

class Events
{
    public static function onSpaceMenuInit($event): void
    {
        $space = $event->sender->space ?? null;
        if ($space === null || !$space->moduleManager->isEnabled('engagement-pages')) {
            return;
        }

        $event->sender->addItem([
            'label' => Yii::t('EngagementPagesModule.base', 'Thiscovery Page Builder'),
            'group' => 'modules',
            'url' => $space->createUrl('/engagement-pages/page/index'),
            'icon' => '<i class="fa fa-th-large"></i>',
            'isActive' => (
                Yii::$app->controller->module
                && Yii::$app->controller->module->id === 'engagement-pages'
                && Yii::$app->controller->id === 'page'
            ),
        ]);
    }

    public static function onAdminMenuInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $allowed = Yii::$app->user->isAdmin()
            || Yii::$app->user->can(ManageModules::class)
            || Yii::$app->user->can(ManageGlobalPage::class)
            || Yii::$app->user->can(CreateGlobalPage::class);

        if (!$allowed) {
            return;
        }

        if (!Yii::$app->getModule('engagement-pages')) {
            return;
        }

        /** @var AdminMenu $menu */
        $menu = $event->sender;
        $menu->addEntry(new MenuLink([
            'label' => Yii::t('EngagementPagesModule.base', 'Thiscovery Page Builder'),
            'id' => 'engagement-pages-admin',
            'icon' => 'th-large',
            'url' => ['/engagement-pages/global/index'],
            'sortOrder' => 555,
            'isActive' => ControllerHelper::isActivePath('engagement-pages', 'global'),
            'isVisible' => true,
        ]));
    }

    /**
     * When engagement-pages is enabled on a Space, the wall stream is admins-only.
     */
    public static function onSpaceControllerBeforeAction($event): void
    {
        /** @var SpaceController $controller */
        $controller = $event->sender;
        $action = $controller->action->id ?? '';
        if (!in_array($action, ['home', 'stream'], true)) {
            return;
        }

        $space = $controller->contentContainer ?? null;
        if (!$space instanceof Space) {
            return;
        }
        if (!$space->moduleManager->isEnabled('engagement-pages')) {
            return;
        }
        if ($space->isAdmin()) {
            return;
        }

        if ($action === 'stream') {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            Yii::$app->response->data = [
                'content' => '',
                'errors' => Yii::t('EngagementPagesModule.base', 'The Space stream is only available to administrators.'),
            ];
            Yii::$app->end();
        }

        // Replace home stream with a restricted panel for non-admins.
        $event->isValid = false;
        Yii::$app->response->content = $controller->render('@engagement-pages/views/space/stream_restricted', [
            'space' => $space,
        ]);
        Yii::$app->end();
    }
}
