<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\thiscoveryPageBuilder\components\PageUrlRule;
use humhub\modules\thiscoveryPageBuilder\Events;
use humhub\modules\thiscoveryPageBuilder\Module;
use humhub\modules\space\controllers\SpaceController;
use humhub\modules\space\widgets\Menu;
use yii\base\Controller;

return [
    'id' => 'thiscovery-page-builder',
    'class' => Module::class,
    'namespace' => 'humhub\modules\thiscoveryPageBuilder',
    'events' => [
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => [Events::class, 'onSpaceMenuInit']],
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
        ['class' => SpaceController::class, 'event' => Controller::EVENT_BEFORE_ACTION, 'callback' => [Events::class, 'onSpaceControllerBeforeAction']],
    ],
    'urlManagerRules' => [
        ['class' => PageUrlRule::class],
        // Current module-id routes.
        'thiscovery-page-builder/global/view/<id:\d+>' => 'thiscovery-page-builder/global/view',
        'thiscovery-page-builder/global/edit/<id:\d+>' => 'thiscovery-page-builder/global/edit',
        'thiscovery-page-builder/global/save-template/<id:\d+>' => 'thiscovery-page-builder/global/save-template',
        'thiscovery-page-builder/global/comments' => 'thiscovery-page-builder/global/comments',
        'thiscovery-page-builder/global/moderate-comment/<id:\d+>' => 'thiscovery-page-builder/global/moderate-comment',
        'thiscovery-page-builder/global/subscriptions' => 'thiscovery-page-builder/global/subscriptions',
        'thiscovery-page-builder/global/export-subscriptions' => 'thiscovery-page-builder/global/export-subscriptions',
        'thiscovery-page-builder/global/delete-subscription/<id:\d+>' => 'thiscovery-page-builder/global/delete-subscription',
        // Legacy module-id routes from engagement-pages.
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
