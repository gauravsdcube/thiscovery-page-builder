<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/**
 * @var int|string $sectionIndex
 * @var int|string $itemIndex
 * @var array $item
 * @var string|null $namePrefix
 * @var \humhub\modules\engagementPages\models\EngagementPage|null $page
 */

use humhub\helpers\Html;

$safeSec = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $sectionIndex);
$safeItem = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $itemIndex);
$parentPrefix = $namePrefix ?? ('sections[' . $sectionIndex . ']');
$namePrefix = $parentPrefix . '[settings][items][' . $itemIndex . ']';
$page = $page ?? null;
?>
<div class="ep-download-item" data-ep-download-item>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Document label') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[label]"
               value="<?= Html::encode($item['label'] ?? '') ?>"
               placeholder="<?= Yii::t('EngagementPagesModule.base', 'Document label') ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'File') ?></label>
        <?= $this->render('_upload_field', [
            'inputName' => $namePrefix . '[file_guid]',
            'guid' => $item['file_guid'] ?? '',
            'widgetId' => 'ep-doc-' . $safeSec . '-' . $safeItem,
            'imagesOnly' => false,
            'buttonLabel' => Yii::t('EngagementPagesModule.base', 'Upload document'),
            'page' => $page,
        ]) ?>
        <?php if (!empty($item['url']) && empty($item['file_guid'])): ?>
            <p class="ep-hint text-muted">
                <?= Yii::t('EngagementPagesModule.base', 'Previously used document URL (replace by uploading a file):') ?>
                <?= Html::encode($item['url']) ?>
            </p>
            <input type="hidden" name="<?= $namePrefix ?>[url]" value="<?= Html::encode($item['url']) ?>">
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Accessibility text') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[alt]"
               value="<?= Html::encode($item['alt'] ?? '') ?>"
               placeholder="<?= Yii::t('EngagementPagesModule.base', 'Describe the document for screen readers') ?>">
    </div>
    <button type="button" class="btn btn-light btn-sm" data-ep-remove-download>
        <i class="fa fa-times"></i> <?= Yii::t('EngagementPagesModule.base', 'Remove') ?>
    </button>
    <hr>
</div>
