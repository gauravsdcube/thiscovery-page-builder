<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\engagementPages\helpers\Url;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;

/** @var ContentContainerActiveRecord $contentContainer */
/** @var EngagementPage[] $pages */
/** @var EngagementPage[] $templates */
/** @var bool $canCreate */

$templates = $templates ?? [];
$this->title = Yii::t('EngagementPagesModule.base', 'Thiscovery Page Builder');
$statusOptions = EngagementPage::statusOptions();
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Html::encode($this->title) ?></strong>
        <?php if ($canCreate): ?>
            <?= Button::primary(Yii::t('EngagementPagesModule.base', 'Create page'))
                ->link(Url::toCreate($contentContainer))
                ->icon('plus')
                ->right() ?>
        <?php endif; ?>
    </div>
    <div class="panel-body ep-page-list">
        <?php if (empty($pages)): ?>
            <p class="text-muted"><?= Yii::t('EngagementPagesModule.base', 'No pages yet.') ?></p>
        <?php else: ?>
            <?php foreach ($pages as $page): ?>
                <div class="ep-page-card">
                    <div>
                        <strong><?= Html::encode($page->title) ?></strong>
                        <div class="ep-page-meta">
                            <?= Html::encode($page->getPublicPath()) ?>
                            ·
                            <?= Badge::light($statusOptions[$page->status] ?? '')->pill() ?>
                        </div>
                    </div>
                    <div>
                        <?= Button::defaultType(Yii::t('EngagementPagesModule.base', 'View'))
                            ->link(Url::toViewInSpace($page))
                            ->sm() ?>
                        <?php if ($page->canManage()): ?>
                            <?= Button::primary(Yii::t('EngagementPagesModule.base', 'Edit page'))
                                ->link(Url::toEdit($page))
                                ->sm() ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Yii::t('EngagementPagesModule.base', 'Page templates') ?></strong>
    </div>
    <div class="panel-body ep-page-list">
        <?php if (empty($templates)): ?>
            <p class="text-muted">
                <?= Yii::t('EngagementPagesModule.base', 'No templates yet. Open a page in the builder and choose “Save as template”.') ?>
            </p>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="ep-page-card ep-page-card--template">
                    <div>
                        <strong><?= Html::encode($template->title) ?></strong>
                        <?= Badge::warning(Yii::t('EngagementPagesModule.base', 'Template'))->pill() ?>
                    </div>
                    <div class="ep-page-actions">
                        <?php if ($canCreate): ?>
                            <?= Button::primary(Yii::t('EngagementPagesModule.base', 'Create from template'))
                                ->link(Url::toCreate($contentContainer, (int) $template->id))
                                ->sm() ?>
                        <?php endif; ?>
                        <?php if ($template->canManage()): ?>
                            <?= Button::defaultType(Yii::t('EngagementPagesModule.base', 'Edit template'))
                                ->link(Url::toEdit($template))
                                ->sm() ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
