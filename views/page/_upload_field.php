<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/**
 * Single-file upload that stores a File GUID in a section-scoped hidden input.
 *
 * @var string $inputName e.g. sections[0][settings][image_guid]
 * @var string|null $guid
 * @var string $widgetId unique DOM id prefix
 * @var bool $imagesOnly
 * @var string|null $buttonLabel
 * @var \humhub\modules\thiscoveryPageBuilder\models\EngagementPage|null $page
 */

use humhub\helpers\Html;
use humhub\modules\file\models\File;
use humhub\modules\file\widgets\FilePreview;
use humhub\modules\file\widgets\UploadButton;
use humhub\modules\file\widgets\UploadProgress;

$imagesOnly = $imagesOnly ?? false;
$buttonLabel = $buttonLabel ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Upload');
$guid = trim((string) ($guid ?? ''));
$page = $page ?? null;
$file = ($guid !== '') ? File::findOne(['guid' => $guid]) : null;
$previewId = $widgetId . '-preview';
$progressId = $widgetId . '-progress';
$btnId = $widgetId . '-btn';
// Temporary non-section name so humhub.file can append for preview without polluting sections[]
$tmpName = 'ep_upload_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $widgetId);
$canAttach = $page && !$page->isNewRecord;

$btnConfig = [
    'id' => $btnId,
    'label' => $buttonLabel,
    'tooltip' => $buttonLabel,
    'cssButtonClass' => 'btn-primary btn-sm',
    'single' => true,
    'multiple' => false,
    'max' => 1,
    'hideInStream' => true,
    'postState' => false,
    'submitName' => $tmpName,
    'dropZone' => '#' . $widgetId,
    'preview' => '#' . $previewId,
    'progress' => '#' . $progressId,
    'attach' => $canAttach,
];
if ($imagesOnly) {
    $btnConfig['options'] = ['accept' => 'image/*'];
}
if ($canAttach) {
    $btnConfig['model'] = $page;
}
?>
<div class="ep-upload" id="<?= Html::encode($widgetId) ?>" data-ep-upload data-ep-upload-tmp="<?= Html::encode($tmpName) ?>">
    <?= Html::hiddenInput($inputName, $guid, [
        'data-ep-file-guid' => true,
        'id' => $widgetId . '-guid',
    ]) ?>
    <div class="ep-upload__controls">
        <?= UploadButton::widget($btnConfig) ?>
    </div>
    <?= UploadProgress::widget(['id' => $progressId]) ?>
    <?= FilePreview::widget([
        'id' => $previewId,
        'items' => $file ? [$file] : [],
        'edit' => true,
        'options' => [
            'class' => 'ep-upload__preview mt-2',
            'data-ep-upload-preview' => true,
        ],
    ]) ?>
    <?php if ($file): ?>
        <div class="ep-upload__meta text-muted small mt-1" data-ep-upload-meta>
            <?= Html::encode($file->file_name) ?>
        </div>
    <?php else: ?>
        <div class="ep-upload__meta text-muted small mt-1 d-none" data-ep-upload-meta></div>
    <?php endif; ?>
</div>
