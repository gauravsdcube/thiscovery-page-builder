<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\engagementPages\components\PageUrlRule;
use humhub\modules\engagementPages\Events;
use humhub\modules\engagementPages\Module;
use humhub\modules\space\controllers\SpaceController;
use humhub\modules\space\widgets\Menu;
use yii\base\Controller;

return [
    'id' => 'engagement-pages',
    'class' => Module::class,
    'namespace' => 'humhub\modules\engagementPages',
    'events' => [
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => [Events::class, 'onSpaceMenuInit']],
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
        ['class' => SpaceController::class, 'event' => Controller::EVENT_BEFORE_ACTION, 'callback' => [Events::class, 'onSpaceControllerBeforeAction']],
    ],
    'urlManagerRules' => [
        ['class' => PageUrlRule::class],
        // Fallback module-id routes (bookmarks / Yii default routing still work).
        'engagement-pages/global/view/<id:\d+>' => 'engagement-pages/global/view',
        'engagement-pages/global/edit/<id:\d+>' => 'engagement-pages/global/edit',
        'engagement-pages/global/save-template/<id:\d+>' => 'engagement-pages/global/save-template',
        'engagement-pages/global/comments' => 'engagement-pages/global/comments',
        'engagement-pages/global/moderate-comment/<id:\d+>' => 'engagement-pages/global/moderate-comment',
        'engagement-pages/global/subscriptions' => 'engagement-pages/global/subscriptions',
        'engagement-pages/global/export-subscriptions' => 'engagement-pages/global/export-subscriptions',
        'engagement-pages/global/delete-subscription/<id:\d+>' => 'engagement-pages/global/delete-subscription',
    ],
];
