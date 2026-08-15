<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\helpers;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use yii\helpers\Url as BaseUrl;

class Url
{
    public static function toPublic(EngagementPage $page, $scheme = false): string
    {
        if ($page->isDirectoryHome()) {
            return BaseUrl::to(['/thiscovery-page-builder/public/index'], $scheme);
        }
        $url = BaseUrl::to(['/thiscovery-page-builder/public/view', 'slug' => $page->slug], $scheme);
        return $scheme ? self::ensureHttps($url) : $url;
    }

    public static function toDirectory($scheme = false): string
    {
        return BaseUrl::to(['/thiscovery-page-builder/public/index'], $scheme);
    }

    public static function toGlobalIndex(): string
    {
        return BaseUrl::to(['/thiscovery-page-builder/global/index']);
    }

    public static function toGlobalCreate(?int $templateId = null): string
    {
        $params = ['/thiscovery-page-builder/global/create'];
        if ($templateId) {
            $params['template_id'] = $templateId;
        }
        return BaseUrl::to($params);
    }

    public static function toGlobalSaveTemplate(EngagementPage $page): string
    {
        return BaseUrl::to(['/thiscovery-page-builder/global/save-template', 'id' => $page->id]);
    }

    public static function toGlobalComments(?string $status = 'all', ?int $pageId = null): string
    {
        $params = ['/thiscovery-page-builder/global/comments', 'status' => $status ?: 'all'];
        if ($pageId) {
            $params['page_id'] = $pageId;
        }
        return BaseUrl::to($params);
    }

    public static function toGlobalModerateComment(int $id): string
    {
        return BaseUrl::to(['/thiscovery-page-builder/global/moderate-comment', 'id' => $id]);
    }

    public static function toGlobalSubscriptions(?int $pageId = null): string
    {
        $params = ['/thiscovery-page-builder/global/subscriptions'];
        if ($pageId) {
            $params['page_id'] = $pageId;
        }
        return BaseUrl::to($params);
    }

    public static function toGlobalExportSubscriptions(?int $pageId = null): string
    {
        $params = ['/thiscovery-page-builder/global/export-subscriptions'];
        if ($pageId) {
            $params['page_id'] = $pageId;
        }
        return BaseUrl::to($params);
    }

    public static function toGlobalDeleteSubscription(int $id): string
    {
        return BaseUrl::to(['/thiscovery-page-builder/global/delete-subscription', 'id' => $id]);
    }

    public static function toGlobalView(EngagementPage $page): string
    {
        return BaseUrl::to(['/thiscovery-page-builder/global/view', 'id' => $page->id]);
    }

    public static function toGlobalEdit(EngagementPage $page): string
    {
        return BaseUrl::to(['/thiscovery-page-builder/global/edit', 'id' => $page->id]);
    }

    public static function toViewInSpace(EngagementPage $page): string
    {
        if ($page->isGlobal()) {
            return self::toGlobalView($page);
        }
        return $page->content->container->createUrl('/thiscovery-page-builder/page/view', ['id' => $page->id]);
    }

    public static function toEdit(EngagementPage $page): string
    {
        if ($page->isGlobal()) {
            return self::toGlobalEdit($page);
        }
        return $page->content->container->createUrl('/thiscovery-page-builder/page/edit', ['id' => $page->id]);
    }

    public static function toIndex($container = null): string
    {
        if ($container === null) {
            return self::toGlobalIndex();
        }
        return $container->createUrl('/thiscovery-page-builder/page/index');
    }

    public static function toSaveTemplate(EngagementPage $page): string
    {
        if ($page->isGlobal()) {
            return self::toGlobalSaveTemplate($page);
        }
        return $page->content->container->createUrl('/thiscovery-page-builder/page/save-template', ['id' => $page->id]);
    }

    public static function toCreate($container = null, ?int $templateId = null): string
    {
        if ($container === null) {
            return self::toGlobalCreate($templateId);
        }
        $params = ['/thiscovery-page-builder/page/create'];
        if ($templateId) {
            $params['template_id'] = $templateId;
        }
        return $container->createUrl($params);
    }

    public static function toDelete(EngagementPage $page): string
    {
        if ($page->isGlobal()) {
            return BaseUrl::to(['/thiscovery-page-builder/global/delete', 'id' => $page->id]);
        }
        return $page->content->container->createUrl('/thiscovery-page-builder/page/delete', ['id' => $page->id]);
    }

    public static function ensureHttps(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }
        return $url;
    }
}
