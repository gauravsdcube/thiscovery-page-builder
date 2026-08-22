<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;

/** @var EngagementPage $page */
/** @var int $depth */
/** @var array $statusOptions */
/** @var bool $canCreate */
/** @var int $childCount */
/** @var \humhub\modules\content\components\ContentContainerActiveRecord|null $listContainer */

$depth = (int) ($depth ?? 0);
$childCount = (int) ($childCount ?? 0);
$canCreate = !empty($canCreate);
$listContainer = $listContainer ?? ($page->isGlobal() ? null : ($page->content->container ?? null));
$status = $statusOptions[$page->status] ?? '';
$canManage = $page->canManage();
$createdAt = $page->content->created_at ?? $page->created_at ?? null;
$updatedAt = $page->content->updated_at ?? $page->updated_at ?? null;
$sectionCount = count($page->getSections());
$canDelete = $canManage && !$page->isDirectoryHome() && !($page->isCollection() && $childCount > 0);
$isTemplate = $page->isTemplate();
$typeLabel = $isTemplate
    ? Yii::t('ThiscoveryPageBuilderModule.base', 'Template')
    : ($page->isCollection()
        ? Yii::t('ThiscoveryPageBuilderModule.base', 'Collection')
        : Yii::t('ThiscoveryPageBuilderModule.base', 'Page'));
?>
<tr class="ep-page-row<?= $depth > 0 ? ' ep-page-row--child' : '' ?>" data-status="<?= (int) $page->status ?>">
    <td class="ep-page-table__actions-col">
        <div class="ep-page-row__actions">
            <?php if ($isTemplate): ?>
                <?php if ($canCreate): ?>
                    <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Use template'))
                        ->link(Url::toCreate($listContainer, (int) $page->id))
                        ->sm()
                        ->loader(false) ?>
                <?php endif; ?>
                <?php if ($canManage): ?>
                    <?= Button::light()
                        ->link(Url::toEdit($page))
                        ->sm()
                        ->icon('pencil')
                        ->tooltip(Yii::t('ThiscoveryPageBuilderModule.base', 'Edit template'))
                        ->loader(false) ?>
                <?php endif; ?>
            <?php else: ?>
                <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Open'))
                    ->link(Url::toViewInSpace($page))
                    ->sm()
                    ->loader(false) ?>

                <?php if ($canManage): ?>
                    <?= Button::light()
                        ->link(Url::toEdit($page))
                        ->sm()
                        ->icon('pencil')
                        ->tooltip(Yii::t('ThiscoveryPageBuilderModule.base', 'Edit'))
                        ->loader(false) ?>
                <?php endif; ?>

                <?= Button::light()
                    ->link(Url::toPublic($page))
                    ->sm()
                    ->icon('external-link')
                    ->tooltip(Yii::t('ThiscoveryPageBuilderModule.base', 'Public link'))
                    ->options(['target' => '_blank', 'rel' => 'noopener'])
                    ->loader(false) ?>

                <?php if ($canCreate && $page->isCollection()): ?>
                    <?= Button::light()
                        ->link(Url::toCreate($listContainer, null, (int) $page->id))
                        ->sm()
                        ->icon('plus')
                        ->tooltip(Yii::t('ThiscoveryPageBuilderModule.base', 'Add page'))
                        ->loader(false) ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($canDelete): ?>
                <?= Html::beginForm(Url::toDelete($page), 'post', [
                    'class' => 'ep-page-row__delete',
                    'data-pjax-prevent' => true,
                ]) ?>
                    <?= Button::danger()
                        ->confirm(Yii::t('ThiscoveryPageBuilderModule.base', 'Are you sure you want to delete this engagement page?'))
                        ->submit()
                        ->sm()
                        ->icon('trash')
                        ->tooltip(Yii::t('ThiscoveryPageBuilderModule.base', 'Delete'))
                        ->loader(false) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </div>
    </td>
    <td class="ep-page-table__status-col">
        <?php if ((int) $page->status === EngagementPage::STATUS_ARCHIVED): ?>
            <?= Badge::danger($status) ?>
        <?php elseif ((int) $page->status === EngagementPage::STATUS_DRAFT): ?>
            <?= Badge::warning($status) ?>
        <?php else: ?>
            <?= Badge::success($status) ?>
        <?php endif; ?>
    </td>
    <td class="ep-page-table__page-col">
        <div class="ep-page-row__title">
            <?= Html::a(Html::encode($page->title), Url::toEdit($page)) ?>
            <?php if ($page->isDirectoryHome()): ?>
                <span class="ep-page-row__chip"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Primary') ?></span>
            <?php endif; ?>
            <?php if (!empty($page->show_in_top_menu)): ?>
                <span class="ep-page-row__chip ep-page-row__chip--icon" title="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Top menu')) ?>">
                    <i class="fa fa-bars"></i>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($page->summary): ?>
            <div class="ep-page-row__desc">
                <?= Html::encode(mb_strimwidth(strip_tags((string) $page->summary), 0, 120, '…')) ?>
            </div>
        <?php endif; ?>
    </td>
    <td class="ep-page-table__type-col">
        <span class="ep-page-row__chip"><?= Html::encode($typeLabel) ?></span>
    </td>
    <td class="ep-page-table__url-col">
        <?php if ($isTemplate): ?>
            —
        <?php else: ?>
            <code class="ep-page-row__path"><?= Html::encode($page->getPublicPath()) ?></code>
        <?php endif; ?>
    </td>
    <td class="ep-page-table__num-col">
        <span class="ep-page-row__stat"><?= (int) $sectionCount ?></span>
    </td>
    <td class="ep-page-table__date-col">
        <?= $createdAt ? Html::encode(Yii::$app->formatter->asDatetime($createdAt, 'short')) : '—' ?>
    </td>
    <td class="ep-page-table__date-col">
        <?= $updatedAt ? Html::encode(Yii::$app->formatter->asDatetime($updatedAt, 'short')) : '—' ?>
    </td>
</tr>
