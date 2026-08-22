<?php

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\Module;
use Yii;
use yii\helpers\Markdown;

/**
 * In-product Help from docs/user markdown.
 */
class HelpService
{
    /**
     * @return array<int, array{id:string,title:string,intro:string,pages:string[]}>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'admin',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Administration'),
                'intro' => Yii::t('ThiscoveryPageBuilderModule.base', 'Enable the module, set permissions, and choose how public pages sit on the site.'),
                'pages' => ['admins'],
            ],
            [
                'id' => 'creators',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page creators'),
                'intro' => Yii::t('ThiscoveryPageBuilderModule.base', 'Build pages, publish URLs, and add comments, forms, and space widgets.'),
                'pages' => [
                    'creators-getting-started',
                    'creators-builder',
                    'creators-settings',
                    'creators-publishing',
                    'creators-engagement',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{file:string,title:string,summary:string,icon:string}>
     */
    public static function pages(): array
    {
        return [
            'admins' => [
                'file' => 'admins.md',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder for administrators'),
                'summary' => Yii::t('ThiscoveryPageBuilderModule.base', 'Module enablement, permissions, space stream, and site homepage.'),
                'icon' => 'cog',
            ],
            'creators-getting-started' => [
                'file' => 'creators-getting-started.md',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Getting started'),
                'summary' => Yii::t('ThiscoveryPageBuilderModule.base', 'Where pages live, collections, and the studio tabs.'),
                'icon' => 'play-circle',
            ],
            'creators-builder' => [
                'file' => 'creators-builder.md',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Builder and sections'),
                'summary' => Yii::t('ThiscoveryPageBuilderModule.base', 'Layouts, section types, containers, and colours.'),
                'icon' => 'th-large',
            ],
            'creators-settings' => [
                'file' => 'creators-settings.md',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page settings'),
                'summary' => Yii::t('ThiscoveryPageBuilderModule.base', 'Slug, status, audience, bound Space, menu, and homepage.'),
                'icon' => 'wrench',
            ],
            'creators-publishing' => [
                'file' => 'creators-publishing.md',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Publishing and URLs'),
                'summary' => Yii::t('ThiscoveryPageBuilderModule.base', 'Public links, collections, templates, and who can view.'),
                'icon' => 'external-link',
            ],
            'creators-engagement' => [
                'file' => 'creators-engagement.md',
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Comments, forms, and space widgets'),
                'summary' => Yii::t('ThiscoveryPageBuilderModule.base', 'Moderation, update emails, Thiscovery Forms, and Space embeds.'),
                'icon' => 'comments',
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        $pages = self::pages();
        return $pages[$slug] ?? null;
    }

    /**
     * @return array{slug:string,title:string,html:string}|null
     */
    public static function render(string $slug, $container = null): ?array
    {
        $meta = self::find($slug);
        if (!$meta) {
            return null;
        }

        $path = self::docsPath() . DIRECTORY_SEPARATOR . $meta['file'];
        if (!is_readable($path)) {
            return null;
        }

        $markdown = (string)file_get_contents($path);
        $markdown = preg_replace('/^#\s+.*\R+/', '', $markdown, 1) ?? $markdown;
        $markdown = preg_replace_callback(
            '/\]\(([\w-]+)\.md(#[^)]+)?\)/',
            static function (array $m) use ($container): string {
                $url = Url::toHelp($container, $m[1]);
                return '](' . $url . ($m[2] ?? '') . ')';
            },
            $markdown
        ) ?? $markdown;

        return [
            'slug' => $slug,
            'title' => $meta['title'],
            'html' => Markdown::process($markdown, 'gfm'),
        ];
    }

    public static function docsPath(): string
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('thiscovery-page-builder');
        return $module->getBasePath() . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'user';
    }
}
