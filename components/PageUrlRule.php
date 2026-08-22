<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\components;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;
use yii\base\Component;
use yii\web\UrlRuleInterface;

/**
 * Pretty public URLs for collections, nested pages, and top-level pages, plus /page-builder admin aliases.
 */
class PageUrlRule extends Component implements UrlRuleInterface
{
    public const CACHE_ID = 'thiscovery-page-builder-public-prefix';
    public const ROOT_SLUGS_CACHE_ID = 'thiscovery-page-builder-root-slugs';
    public const ADMIN_PREFIX = 'page-builder';

    private const ADMIN_FIXED = [
        'thiscovery-page-builder/global/index' => 'page-builder',
        'thiscovery-page-builder/global/create' => 'page-builder/create',
        'thiscovery-page-builder/global/comments' => 'page-builder/comments',
        'thiscovery-page-builder/global/subscriptions' => 'page-builder/subscriptions',
        'thiscovery-page-builder/global/export-subscriptions' => 'page-builder/export-subscriptions',
    ];

    private const ADMIN_WITH_ID = [
        'thiscovery-page-builder/global/view' => 'page-builder/view',
        'thiscovery-page-builder/global/edit' => 'page-builder/edit',
        'thiscovery-page-builder/global/save-template' => 'page-builder/save-template',
        'thiscovery-page-builder/global/moderate-comment' => 'page-builder/moderate-comment',
        'thiscovery-page-builder/global/delete-subscription' => 'page-builder/delete-subscription',
        'thiscovery-page-builder/global/delete' => 'page-builder/delete',
    ];

    /**
     * Legacy helper: primary collection slug (former directory homepage).
     */
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

    /**
     * @return string[] lowercase root page/collection slugs
     */
    public static function getRootSlugs(): array
    {
        $cached = Yii::$app->cache->get(self::ROOT_SLUGS_CACHE_ID);
        if (is_array($cached)) {
            return $cached;
        }

        $slugs = [];
        try {
            if ((new EngagementPage())->hasAttribute('parent_id')) {
                $slugs = EngagementPage::find()
                    ->select('slug')
                    ->where(['parent_id' => null])
                    ->andWhere(['or', ['is_template' => 0], ['is_template' => null]])
                    ->column();
            } else {
                $slugs = [self::getPrefix()];
            }
        } catch (\Throwable $e) {
            $slugs = [EngagementPage::DEFAULT_PUBLIC_PREFIX];
        }

        $slugs = array_values(array_unique(array_filter(array_map(
            static fn ($s) => strtolower(trim((string) $s)),
            $slugs
        ))));

        Yii::$app->cache->set(self::ROOT_SLUGS_CACHE_ID, $slugs, 3600);
        return $slugs;
    }

    public static function flushCache(): void
    {
        Yii::$app->cache->delete(self::CACHE_ID);
        Yii::$app->cache->delete(self::ROOT_SLUGS_CACHE_ID);
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

        if ($route === 'thiscovery-page-builder/public/index') {
            return $this->appendQuery(self::getPrefix(), $params);
        }

        if (in_array($route, [
            'thiscovery-page-builder/public/view',
            'thiscovery-page-builder/public/follow',
            'thiscovery-page-builder/public/comment',
        ], true) && isset($params['slug'])) {
            $slug = (string) $params['slug'];
            $parentSlug = isset($params['parentSlug']) ? (string) $params['parentSlug'] : '';
            unset($params['slug'], $params['parentSlug']);

            if ($parentSlug !== '') {
                $url = rawurlencode($parentSlug) . '/' . rawurlencode($slug);
            } else {
                $url = rawurlencode($slug);
            }

            if ($route === 'thiscovery-page-builder/public/follow') {
                $url .= '/follow';
            } elseif ($route === 'thiscovery-page-builder/public/comment') {
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
            return ['thiscovery-page-builder/public/view', array_merge($query, ['slug' => $parts[1]])];
        }

        $root = strtolower((string) $parts[0]);
        if (in_array($root, EngagementPage::reservedPrefixSlugs(), true)) {
            return false;
        }

        $rootSlugs = self::getRootSlugs();
        // Accept known roots; also allow lookup for newly created roots before cache refresh.
        if (!in_array($root, $rootSlugs, true) && !in_array($root, [self::getPrefix(), EngagementPage::DEFAULT_PUBLIC_PREFIX], true)) {
            // Soft probe: only claim the path if a root page with this slug exists.
            try {
                $probe = EngagementPage::find()
                    ->where(['slug' => $root])
                    ->andWhere(['parent_id' => null])
                    ->one();
                if ($probe === null) {
                    return false;
                }
            } catch (\Throwable $e) {
                return false;
            }
        }

        return $this->parsePublic($parts, $query);
    }

    private function parseAdmin(array $parts, array $query): array|false
    {
        $count = count($parts);
        if ($count === 1) {
            return ['thiscovery-page-builder/global/index', $query];
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
        $root = strtolower((string) $parts[0]);

        if ($count === 1) {
            // Top-level collection or standalone page.
            return ['thiscovery-page-builder/public/view', array_merge($query, ['slug' => $root])];
        }

        if ($count === 2 && in_array($parts[1], ['follow', 'comment'], true)) {
            return [
                'thiscovery-page-builder/public/' . $parts[1],
                array_merge($query, ['slug' => $root]),
            ];
        }

        if ($count === 2) {
            $child = strtolower((string) $parts[1]);
            return [
                'thiscovery-page-builder/public/view',
                array_merge($query, ['slug' => $child, 'parentSlug' => $root]),
            ];
        }

        if ($count === 3 && in_array($parts[2], ['follow', 'comment'], true)) {
            return [
                'thiscovery-page-builder/public/' . $parts[2],
                array_merge($query, [
                    'slug' => strtolower((string) $parts[1]),
                    'parentSlug' => $root,
                ]),
            ];
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
