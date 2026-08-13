<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\helpers\Url;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;

/** @var EngagementPage[] $pages */
/** @var EngagementPage[] $templates */
/** @var bool $canCreate */
/** @var int $pendingComments */
/** @var int $subscriptionCount */

$templates = $templates ?? [];
$pendingComments = (int) ($pendingComments ?? 0);
$subscriptionCount = (int) ($subscriptionCount ?? 0);

$this->title = Yii::t('EngagementPagesModule.base', 'Thiscovery Page Builder');
$statusOptions = EngagementPage::statusOptions();
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Html::encode($this->title) ?></strong>
        <span class="text-muted" style="margin-left:8px;font-weight:normal;">
            <?= Yii::t('EngagementPagesModule.base', 'Network-level public pages') ?>
        </span>
        <?php if ($canCreate): ?>
            <?= Button::primary(Yii::t('EngagementPagesModule.base', 'Create page'))
                ->link(Url::toGlobalCreate())
                ->icon('plus')
                ->right() ?>
        <?php endif; ?>
        <?= Button::defaultType(
            Yii::t('EngagementPagesModule.base', 'Comments')
            . ($pendingComments > 0 ? ' (' . $pendingComments . ')' : '')
        )
            ->link(Url::toGlobalComments())
            ->icon('comments')
            ->right() ?>
        <?= Button::defaultType(
            Yii::t('EngagementPagesModule.base', 'Subscriptions')
            . ($subscriptionCount > 0 ? ' (' . $subscriptionCount . ')' : '')
        )
            ->link(Url::toGlobalSubscriptions())
            ->icon('envelope')
            ->right() ?>
    </div>
    <div class="panel-body ep-page-list">
        <p class="text-muted">
            <?= Yii::t(
                'EngagementPagesModule.base',
                'These pages are global — they do not live in a Space. Link Survey CTAs to global Thiscovery Forms so the public never needs Space access.'
            ) ?>
        </p>
        <?php if (empty($pages)): ?>
            <p class="text-muted"><?= Yii::t('EngagementPagesModule.base', 'No pages yet.') ?></p>
        <?php else: ?>
            <?php foreach ($pages as $page): ?>
                <?php $isDirectory = $page->isDirectoryHome(); ?>
                <div class="ep-page-card<?= $isDirectory ? ' ep-page-card--directory' : '' ?>">
                    <div>
                        <strong><?= Html::encode($page->title) ?></strong>
                        <?php if ($isDirectory): ?>
                            <?= Badge::info(Yii::t('EngagementPagesModule.base', '/pages homepage'))->pill() ?>
                        <?php endif; ?>
                        <div class="ep-page-meta">
                            <?= $isDirectory ? '/pages' : ('/pages/' . Html::encode($page->slug)) ?>
                            ·
                            <?= Badge::light($statusOptions[$page->status] ?? '')->pill() ?>
                        </div>
                    </div>
                    <div class="ep-page-actions">
                        <?= Button::defaultType(Yii::t('EngagementPagesModule.base', 'View'))
                            ->link(Url::toGlobalView($page))
                            ->sm() ?>
                        <?php if ($page->canManage()): ?>
                            <?= Button::primary(Yii::t('EngagementPagesModule.base', $isDirectory ? 'Edit /pages homepage' : 'Edit page'))
                                ->link(Url::toGlobalEdit($page))
                                ->sm() ?>
                        <?php endif; ?>
                        <a class="btn btn-link btn-sm" href="<?= Html::encode(Url::toPublic($page)) ?>" target="_blank" rel="noopener">
                            <?= Yii::t('EngagementPagesModule.base', 'Public link') ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Yii::t('EngagementPagesModule.base', 'Page templates') ?></strong>
        <span class="text-muted" style="margin-left:8px;font-weight:normal;">
            <?= Yii::t('EngagementPagesModule.base', 'Reuse layouts and sections when creating new pages') ?>
        </span>
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
                        <div class="ep-page-meta">
                            <?= Yii::t('EngagementPagesModule.base', 'Starter for new pages') ?>
                        </div>
                    </div>
                    <div class="ep-page-actions">
                        <?php if ($canCreate): ?>
                            <?= Button::primary(Yii::t('EngagementPagesModule.base', 'Create from template'))
                                ->link(Url::toGlobalCreate((int) $template->id))
                                ->sm() ?>
                        <?php endif; ?>
                        <?php if ($template->canManage()): ?>
                            <?= Button::defaultType(Yii::t('EngagementPagesModule.base', 'Edit template'))
                                ->link(Url::toGlobalEdit($template))
                                ->sm() ?>
                            <?= Html::beginForm(Url::toDelete($template), 'post', ['style' => 'display:inline']) ?>
                                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm(<?= Html::encode(json_encode(Yii::t('EngagementPagesModule.base', 'Delete this template?'))) ?>);">
                                    <?= Yii::t('EngagementPagesModule.base', 'Delete') ?>
                                </button>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
