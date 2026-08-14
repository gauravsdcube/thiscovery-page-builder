<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\components;

use humhub\modules\engagementPages\models\EngagementPage;
use Yii;
use yii\base\Component;
use yii\web\UrlRuleInterface;

/**
 * Pretty public URLs under the editable homepage slug, plus /page-builder admin aliases.
 */
class PageUrlRule extends Component implements UrlRuleInterface
{
    public const CACHE_ID = 'engagement-pages-public-prefix';
    public const ADMIN_PREFIX = 'page-builder';

    private const ADMIN_FIXED = [
        'engagement-pages/global/index' => 'page-builder',
        'engagement-pages/global/create' => 'page-builder/create',
        'engagement-pages/global/comments' => 'page-builder/comments',
        'engagement-pages/global/subscriptions' => 'page-builder/subscriptions',
        'engagement-pages/global/export-subscriptions' => 'page-builder/export-subscriptions',
    ];

    private const ADMIN_WITH_ID = [
        'engagement-pages/global/view' => 'page-builder/view',
        'engagement-pages/global/edit' => 'page-builder/edit',
        'engagement-pages/global/save-template' => 'page-builder/save-template',
        'engagement-pages/global/moderate-comment' => 'page-builder/moderate-comment',
        'engagement-pages/global/delete-subscription' => 'page-builder/delete-subscription',
        'engagement-pages/global/delete' => 'page-builder/delete',
    ];

    public static function getPrefix(): string
    {
        $cached = Yii::$app->cache->get(self::CACHE_ID);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $prefix = EngagementPage::DEFAULT_PUBLIC_PREFIX;
        try {
            $home = EngagementPage::findDirectoryHome();
            $slug = trim((string) ($home->slug ?? ''));
            if ($slug !== '' && $slug !== 'directory') {
                $prefix = $slug;
            }
        } catch (\Throwable $e) {
            // Schema not ready during install/migrations.
        }

        Yii::$app->cache->set(self::CACHE_ID, $prefix, 3600);
        return $prefix;
    }

    public static function flushCache(): void
    {
        Yii::$app->cache->delete(self::CACHE_ID);
    }

    public function createUrl($manager, $route, $params)
    {
        if (isset($params['cguid'])) {
            return false;
        }

        if (isset(self::ADMIN_FIXED[$route])) {
            $url = self::ADMIN_FIXED[$route];
            unset($params['cguid']);
            return $this->appendQuery($url, $params);
        }

        if (isset(self::ADMIN_WITH_ID[$route]) && isset($params['id'])) {
            $url = self::ADMIN_WITH_ID[$route] . '/' . (int) $params['id'];
            unset($params['id']);
            return $this->appendQuery($url, $params);
        }

        $prefix = self::getPrefix();

        if ($route === 'engagement-pages/public/index') {
            return $this->appendQuery($prefix, $params);
        }

        if (in_array($route, ['engagement-pages/public/view', 'engagement-pages/public/follow', 'engagement-pages/public/comment'], true)
            && isset($params['slug'])) {
            $slug = (string) $params['slug'];
            unset($params['slug']);
            $url = ($slug === $prefix)
                ? $prefix
                : $prefix . '/' . rawurlencode($slug);
            if ($route === 'engagement-pages/public/follow') {
                $url .= '/follow';
            } elseif ($route === 'engagement-pages/public/comment') {
                $url .= '/comment';
            }
            return $this->appendQuery($url, $params);
        }

        return false;
    }

    public function parseRequest($manager, $request)
    {
        $path = trim((string) $request->getPathInfo(), '/');
        if ($path === '') {
            return false;
        }
        $parts = explode('/', $path);
        $query = $request->getQueryParams();

        if ($parts[0] === self::ADMIN_PREFIX) {
            return $this->parseAdmin($parts, $query);
        }

        if ($parts[0] === 'engage' && isset($parts[1]) && $parts[1] !== '') {
            return ['engagement-pages/public/view', array_merge($query, ['slug' => $parts[1]])];
        }

        $prefix = self::getPrefix();
        $publicRoots = array_unique([$prefix, EngagementPage::DEFAULT_PUBLIC_PREFIX]);
        if (!in_array($parts[0], $publicRoots, true)) {
            return false;
        }

        return $this->parsePublic($parts, $query);
    }

    private function parseAdmin(array $parts, array $query): array|false
    {
        $count = count($parts);
        if ($count === 1) {
            return ['engagement-pages/global/index', $query];
        }

        $action = $parts[1];
        $fixed = array_flip(self::ADMIN_FIXED);
        $key = self::ADMIN_PREFIX . '/' . $action;
        if (isset($fixed[$key]) && $count === 2) {
            return [$fixed[$key], $query];
        }

        $withId = array_flip(self::ADMIN_WITH_ID);
        $idKey = self::ADMIN_PREFIX . '/' . $action;
        if (isset($withId[$idKey], $parts[2]) && $count === 3 && ctype_digit((string) $parts[2])) {
            $query['id'] = (int) $parts[2];
            return [$withId[$idKey], $query];
        }

        return false;
    }

    private function parsePublic(array $parts, array $query): array|false
    {
        $count = count($parts);
        $root = $parts[0];

        if ($count === 1) {
            return ['engagement-pages/public/index', $query];
        }

        if ($count === 2 && in_array($parts[1], ['follow', 'comment'], true)) {
            return ['engagement-pages/public/' . $parts[1], array_merge($query, ['slug' => $root])];
        }

        if ($count === 2) {
            if ($parts[1] === $root) {
                return ['engagement-pages/public/index', $query];
            }
            return ['engagement-pages/public/view', array_merge($query, ['slug' => $parts[1]])];
        }

        if ($count === 3 && in_array($parts[2], ['follow', 'comment'], true)) {
            return ['engagement-pages/public/' . $parts[2], array_merge($query, ['slug' => $parts[1]])];
        }

        return false;
    }

    private function appendQuery(string $url, array $params): string
    {
        unset($params['cguid']);
        if ($params !== [] && ($query = http_build_query($params)) !== '') {
            return $url . '?' . $query;
        }
        return $url;
    }
}
