<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;

/**
 * Soft-dep on Thiscovery Navigation: collections and child pages appear in the site tree.
 */
class PageNavigationSync
{
    public static function sync(EngagementPage $page, array $changedAttributes = []): void
    {
        if (!Yii::$app->hasModule('thiscovery-navigation')) {
            return;
        }
        $class = 'humhub\\modules\\thiscoveryNavigation\\services\\PageNavSyncService';
        if (!class_exists($class)) {
            return;
        }
        try {
            (new $class())->syncPage($page, $changedAttributes);
        } catch (\Throwable $e) {
            Yii::warning('Page navigation sync failed: ' . $e->getMessage(), 'thiscovery-page-builder');
        }
    }

    public static function remove(EngagementPage $page): void
    {
        if (!Yii::$app->hasModule('thiscovery-navigation')) {
            return;
        }
        $class = 'humhub\\modules\\thiscoveryNavigation\\services\\PageNavSyncService';
        if (!class_exists($class)) {
            return;
        }
        try {
            (new $class())->removePage((int) $page->id);
        } catch (\Throwable $e) {
            Yii::warning('Page navigation remove failed: ' . $e->getMessage(), 'thiscovery-page-builder');
        }
    }
}
