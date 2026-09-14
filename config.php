<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\components\Application;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\thiscoveryPageBuilder\components\PageUrlRule;
use humhub\modules\thiscoveryPageBuilder\Events;
use humhub\modules\thiscoveryPageBuilder\Module;
use humhub\modules\space\controllers\SpaceController;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\controllers\AuthController;
use humhub\widgets\TopMenu;
use yii\base\Controller;

return [
    'id' => 'thiscovery-page-builder',
    'class' => Module::class,
    'namespace' => 'humhub\modules\thiscoveryPageBuilder',
    'events' => [
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => [Events::class, 'onSpaceMenuInit']],
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
        ['class' => SpaceController::class, 'event' => Controller::EVENT_BEFORE_ACTION, 'callback' => [Events::class, 'onSpaceControllerBeforeAction']],
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => [Events::class, 'onTopMenuInit']],
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_ACTION, 'callback' => [Events::class, 'onApplicationBeforeAction']],
        ['class' => AuthController::class, 'event' => AuthController::EVENT_AFTER_LOGIN, 'callback' => [Events::class, 'onAfterLogin']],
    ],
    'urlManagerRules' => [
        ['class' => PageUrlRule::class],
        'thiscovery-page-builder/global/view/<id:\d+>' => 'thiscovery-page-builder/global/view',
        'thiscovery-page-builder/global/edit/<id:\d+>' => 'thiscovery-page-builder/global/edit',
        'thiscovery-page-builder/global/save-template/<id:\d+>' => 'thiscovery-page-builder/global/save-template',
        'thiscovery-page-builder/global/comments' => 'thiscovery-page-builder/global/comments',
        'thiscovery-page-builder/global/moderate-comment/<id:\d+>' => 'thiscovery-page-builder/global/moderate-comment',
        'thiscovery-page-builder/global/subscriptions' => 'thiscovery-page-builder/global/subscriptions',
        'thiscovery-page-builder/global/export-subscriptions' => 'thiscovery-page-builder/global/export-subscriptions',
        'thiscovery-page-builder/global/delete-subscription/<id:\d+>' => 'thiscovery-page-builder/global/delete-subscription',
        'thiscovery-page-builder/global/help' => 'thiscovery-page-builder/global/help',
        'thiscovery-page-builder/global/help/<page:[\\w\\-]+>' => 'thiscovery-page-builder/global/help',
        'thiscovery-page-builder/global/themes' => 'thiscovery-page-builder/global/themes',
        'thiscovery-page-builder/global/theme-edit' => 'thiscovery-page-builder/global/theme-edit',
        'thiscovery-page-builder/global/theme-edit/<id:\\d+>' => 'thiscovery-page-builder/global/theme-edit',
        'thiscovery-page-builder/global/theme-delete/<id:\\d+>' => 'thiscovery-page-builder/global/theme-delete',
        'thiscovery-page-builder/global/theme-export/<id:\\d+>' => 'thiscovery-page-builder/global/theme-export',
        'thiscovery-page-builder/global/theme-import' => 'thiscovery-page-builder/global/theme-import',
        'thiscovery-page-builder/global/publish-version/<id:\\d+>' => 'thiscovery-page-builder/global/publish-version',
        'thiscovery-page-builder/global/restore-version/<id:\\d+>' => 'thiscovery-page-builder/global/restore-version',
        'thiscovery-page-builder/global/delete-revision/<id:\\d+>' => 'thiscovery-page-builder/global/delete-revision',
        'thiscovery-page-builder/global/delete-edition/<id:\\d+>' => 'thiscovery-page-builder/global/delete-edition',
        'thiscovery-page-builder/embed/stream' => 'thiscovery-page-builder/embed/stream',
        'engagement-pages/global/view/<id:\d+>' => 'thiscovery-page-builder/global/view',
        'engagement-pages/global/edit/<id:\d+>' => 'thiscovery-page-builder/global/edit',
        'engagement-pages/global/save-template/<id:\d+>' => 'thiscovery-page-builder/global/save-template',
        'engagement-pages/global/comments' => 'thiscovery-page-builder/global/comments',
        'engagement-pages/global/moderate-comment/<id:\d+>' => 'thiscovery-page-builder/global/moderate-comment',
        'engagement-pages/global/subscriptions' => 'thiscovery-page-builder/global/subscriptions',
        'engagement-pages/global/export-subscriptions' => 'thiscovery-page-builder/global/export-subscriptions',
        'engagement-pages/global/delete-subscription/<id:\d+>' => 'thiscovery-page-builder/global/delete-subscription',
        'engagement-pages/global/index' => 'thiscovery-page-builder/global/index',
    ],
];
