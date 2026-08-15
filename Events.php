<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder;

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\thiscoveryPageBuilder\permissions\CreateGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManageGlobalPage;
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
        if ($space === null || !$space->moduleManager->isEnabled('thiscovery-page-builder')) {
            return;
        }

        $event->sender->addItem([
            'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder'),
            'group' => 'modules',
            'url' => $space->createUrl('/thiscovery-page-builder/page/index'),
            'icon' => '<i class="fa fa-th-large"></i>',
            'isActive' => (
                Yii::$app->controller->module
                && Yii::$app->controller->module->id === 'thiscovery-page-builder'
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

        if (!Yii::$app->getModule('thiscovery-page-builder')) {
            return;
        }

        /** @var AdminMenu $menu */
        $menu = $event->sender;
        $menu->addEntry(new MenuLink([
            'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder'),
            'id' => 'thiscovery-page-builder-admin',
            'icon' => 'th-large',
            'url' => ['/thiscovery-page-builder/global/index'],
            'sortOrder' => 555,
            'isActive' => ControllerHelper::isActivePath('thiscovery-page-builder', 'global'),
            'isVisible' => true,
        ]));
    }

    /**
     * When thiscovery-page-builder is enabled on a Space, the wall stream is admins-only.
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
        if (!$space->moduleManager->isEnabled('thiscovery-page-builder')) {
            return;
        }
        if ($space->isAdmin()) {
            return;
        }

        if ($action === 'stream') {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            Yii::$app->response->data = [
                'content' => '',
                'errors' => Yii::t('ThiscoveryPageBuilderModule.base', 'The Space stream is only available to administrators.'),
            ];
            Yii::$app->end();
        }

        // Replace home stream with a restricted panel for non-admins.
        $event->isValid = false;
        Yii::$app->response->content = $controller->render('@thiscovery-page-builder/views/space/stream_restricted', [
            'space' => $space,
        ]);
        Yii::$app->end();
    }
}
