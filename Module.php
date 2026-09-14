<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder;

use humhub\components\console\Application as ConsoleApplication;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\permissions\CreateGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\CreatePage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManageGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManagePages;
use humhub\modules\space\models\Space;
use Yii;

class Module extends ContentContainerModule
{
    public $resourcesPath = 'resources';

    public function init()
    {
        parent::init();
        self::registerLegacyAliases();
        if (Yii::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'humhub\modules\thiscoveryPageBuilder\commands';
        }

        if (Yii::$app->hasModule('thiscovery-versioning')
            && class_exists(\humhub\modules\thiscoveryVersioning\Module::class)) {
            \humhub\modules\thiscoveryVersioning\Module::registerAdapter(
                new \humhub\modules\thiscoveryPageBuilder\services\PageVersionAdapter()
            );
        }
    }

    /**
     * Keep old class names resolvable after the module id/namespace rename.
     */
    public static function registerLegacyAliases(): void
    {
        $map = [
            'humhub\\modules\\engagementPages\\Module' => self::class,
            'humhub\\modules\\engagementPages\\models\\EngagementPage' => EngagementPage::class,
            'humhub\\modules\\engagementPages\\permissions\\CreatePage' => CreatePage::class,
            'humhub\\modules\\engagementPages\\permissions\\ManagePages' => ManagePages::class,
            'humhub\\modules\\engagementPages\\permissions\\CreateGlobalPage' => CreateGlobalPage::class,
            'humhub\\modules\\engagementPages\\permissions\\ManageGlobalPage' => ManageGlobalPage::class,
        ];
        foreach ($map as $legacy => $current) {
            if (!class_exists($legacy, false)) {
                class_alias($current, $legacy);
            }
        }
    }

    public function getName()
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder');
    }

    public function getDescription()
    {
        return Yii::t(
            'ThiscoveryPageBuilderModule.base',
            'Build public pages with reusable sections, file uploads, survey links, and custom URL slugs.'
        );
    }

    public function getContentContainerTypes()
    {
        return [Space::class];
    }

    public function getContentClasses(): array
    {
        return [EngagementPage::class];
    }

    public function getConfigUrl()
    {
        return \yii\helpers\Url::to(['/thiscovery-page-builder/global/index']);
    }

    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer instanceof Space) {
            return [
                new CreatePage(),
                new ManagePages(),
            ];
        }

        if ($contentContainer === null) {
            return [
                new CreateGlobalPage(),
                new ManageGlobalPage(),
            ];
        }

        return [];
    }

    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder');
    }

    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        return Yii::t(
            'ThiscoveryPageBuilderModule.base',
            'Publish public pages in this space with surveys, downloads, and custom slugs.'
        );
    }

    public function disable()
    {
        foreach (EngagementPage::find()->each(100) as $page) {
            $page->hardDelete();
        }
        parent::disable();
    }

    public function disableContentContainer(ContentContainerActiveRecord $container)
    {
        foreach (EngagementPage::find()->contentContainer($container)->each(100) as $page) {
            $page->hardDelete();
        }
        parent::disableContentContainer($container);
    }
}
