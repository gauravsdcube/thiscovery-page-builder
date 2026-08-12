<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages;

use humhub\components\console\Application as ConsoleApplication;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\modules\engagementPages\permissions\CreateGlobalPage;
use humhub\modules\engagementPages\permissions\CreatePage;
use humhub\modules\engagementPages\permissions\ManageGlobalPage;
use humhub\modules\engagementPages\permissions\ManagePages;
use humhub\modules\space\models\Space;
use Yii;

class Module extends ContentContainerModule
{
    public $resourcesPath = 'resources';

    public function init()
    {
        parent::init();
        if (Yii::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'humhub\modules\engagementPages\commands';
        }
    }

    public function getName()
    {
        return Yii::t('EngagementPagesModule.base', 'Thiscovery Page Builder');
    }

    public function getDescription()
    {
        return Yii::t(
            'EngagementPagesModule.base',
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
        return \yii\helpers\Url::to(['/engagement-pages/global/index']);
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
        return Yii::t('EngagementPagesModule.base', 'Thiscovery Page Builder');
    }

    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        return Yii::t(
            'EngagementPagesModule.base',
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
