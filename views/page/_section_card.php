<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\services\BlockRegistry;
use humhub\modules\thiscoveryEditor\widgets\EditorField;

/** @var int|string $index */
/** @var array $section */
/** @var array $formOptions */
/** @var array $pollOptions */
/** @var array $blockLabels */
/** @var bool $collapsed */
/** @var bool $isChild */
/** @var string|null $namePrefix */
/** @var \humhub\modules\engagementPages\models\EngagementPage|null $page */
/** @var int|null $column */

$collapsed = $collapsed ?? true;
$isChild = $isChild ?? false;
$page = $page ?? null;
$formOptions = $formOptions ?? [];
$type = $section['type'] ?? 'rich_text';
$settings = $section['settings'] ?? [];
$pollOptions = $pollOptions ?? [];
$region = $section['region'] ?? BlockRegistry::REGION_MAIN;
$column = (int) ($column ?? ($section['column'] ?? 0));
$label = $blockLabels[$type] ?? $type;
$namePrefix = $namePrefix ?? ('sections[' . $index . ']');
$safeIndex = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $index);

$titleHint = match ($type) {
    'hero' => $settings['headline'] ?? '',
    'rich_text' => $settings['title'] ?? '',
    'survey_cta' => $settings['button_label'] ?? '',
    'poll_embed' => '',
    'downloads', 'container', 'phases', 'events', 'team', 'contact', 'updates', 'comments', 'accordion', 'callout' => $settings['title'] ?? '',
    'image' => $settings['alt'] ?? ($settings['caption'] ?? ''),
    'directory' => $settings['title'] ?? '',
    'collection' => $settings['title'] ?? '',
    default => '',
};
?>
<div class="ep-section-card<?= $collapsed ? ' is-collapsed' : ' is-expanded' ?><?= $isChild ? ' ep-section-card--child' : '' ?><?= $type === 'container' ? ' ep-section-card--container' : '' ?>"
     data-ep-section
     data-ep-type="<?= Html::encode($type) ?>"
     <?= $isChild ? 'data-ep-child' : '' ?>>
    <input type="hidden" name="<?= $namePrefix ?>[type]" value="<?= Html::encode($type) ?>" data-ep-section-type>
    <?php if (!$isChild): ?>
        <input type="hidden" name="<?= $namePrefix ?>[region]" value="<?= Html::encode($region) ?>" data-ep-section-region>
    <?php else: ?>
        <input type="hidden" name="<?= $namePrefix ?>[column]" value="<?= (int) $column ?>" data-ep-child-column>
    <?php endif; ?>

    <div class="ep-section-card__header" data-ep-toggle-card>
        <div class="ep-section-card__handle" data-ep-drag-handle title="<?= Yii::t('EngagementPagesModule.base', 'Drag to reorder') ?>">
            <i class="fa fa-bars"></i>
        </div>
        <div class="ep-section-card__title">
            <span class="ep-section-card__index" data-ep-index></span>
            <span class="ep-section-card__name" data-ep-title>
                <?= Html::encode($titleHint !== '' ? $titleHint : $label) ?>
            </span>
            <span class="ep-section-card__type-badge" data-ep-type-label>
                <?= Html::encode($label) ?>
            </span>
        </div>
        <div class="ep-section-card__actions">
            <button type="button" class="btn btn-sm btn-light" data-ep-toggle-card-btn title="<?= Yii::t('EngagementPagesModule.base', 'Edit section') ?>">
                <i class="fa fa-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" data-ep-remove-section title="<?= Yii::t('EngagementPagesModule.base', 'Remove') ?>">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>

    <div class="ep-section-card__body">
        <div class="form-group ep-align-field">
            <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Position') ?></label>
            <select class="form-control" name="<?= $namePrefix ?>[settings][align]" style="max-width:280px"
                    data-ep-block-align>
                <?php
                $alignValue = $settings['align']
                    ?? (($type === 'collection' || $type === 'directory')
                        ? \humhub\modules\engagementPages\blocks\BaseBlock::ALIGN_CENTER
                        : \humhub\modules\engagementPages\blocks\BaseBlock::ALIGN_START);
                foreach (\humhub\modules\engagementPages\blocks\BaseBlock::alignOptions() as $value => $alignLabel):
                ?>
                    <option value="<?= Html::encode($value) ?>" <?= $alignValue === $value ? 'selected' : '' ?>>
                        <?= Html::encode($alignLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="ep-hint text-muted">
                <?= Yii::t(
                    'EngagementPagesModule.base',
                    'Full width keeps content on the page edges. Centre / Right shift card grids and compact sections within the page width.'
                ) ?>
            </div>
        </div>

        <?= $this->render('_color_fields', [
            'settings' => $settings,
            'namePrefix' => $namePrefix,
            'safeIndex' => $safeIndex,
            'type' => $type,
        ]) ?>

        <?php if ($type === 'hero'): ?>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Headline') ?></label>
                <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][headline]"
                       value="<?= Html::encode($settings['headline'] ?? '') ?>" data-ep-card-title-source>
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Subheadline') ?></label>
                <div class="ep-rich-editor" data-ep-rich-editor>
                    <?= EditorField::widget([
                        'id' => 'ep-hero-sub-' . $safeIndex,
                        'name' => $namePrefix . '[settings][subheadline]',
                        'value' => (string) ($settings['subheadline'] ?? ''),
                        'placeholder' => Yii::t('EngagementPagesModule.base', 'Supporting text under the headline…'),
                        'height' => 180,
                        'profile' => 'simple',
                    ]) ?>
                </div>
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Hero image') ?></label>
                <?= $this->render('_upload_field', [
                    'inputName' => $namePrefix . '[settings][image_guid]',
                    'guid' => $settings['image_guid'] ?? '',
                    'widgetId' => 'ep-hero-img-' . $safeIndex,
                    'imagesOnly' => true,
                    'buttonLabel' => Yii::t('EngagementPagesModule.base', 'Upload image'),
                    'page' => $page,
                ]) ?>
                <?php if (empty($settings['image_guid']) && !empty($settings['image_url'])): ?>
                    <p class="ep-hint text-muted">
                        <?= Yii::t('EngagementPagesModule.base', 'Previously used image URL (replace by uploading a file):') ?>
                        <?= Html::encode($settings['image_url']) ?>
                    </p>
                    <input type="hidden" name="<?= $namePrefix ?>[settings][image_url]" value="<?= Html::encode($settings['image_url']) ?>">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Image alt text') ?></label>
                <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][image_alt]"
                       value="<?= Html::encode($settings['image_alt'] ?? '') ?>"
                       placeholder="<?= Yii::t('EngagementPagesModule.base', 'Describe the image for screen readers') ?>">
                <div class="ep-hint text-muted"><?= Yii::t('EngagementPagesModule.base', 'Required for accessibility when an image is used.') ?></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6 form-group">
                    <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Button label') ?></label>
                    <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][cta_label]"
                           value="<?= Html::encode($settings['cta_label'] ?? '') ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Button URL') ?></label>
                    <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][cta_url]"
                           value="<?= Html::encode($settings['cta_url'] ?? '') ?>">
                </div>
            </div>

        <?php elseif ($type === 'rich_text'): ?>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Title') ?></label>
                <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
                       value="<?= Html::encode($settings['title'] ?? '') ?>" data-ep-card-title-source>
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Body') ?></label>
                <div class="ep-field-note">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <div><?= Yii::t('EngagementPagesModule.base', 'Use the editor for headings, lists, links, and Thiscovery blocks (callout, accordion, survey).') ?></div>
                </div>
                <div class="ep-rich-editor" data-ep-rich-editor>
                    <?= EditorField::widget([
                        'id' => 'ep-rich-body-' . $safeIndex,
                        'name' => $namePrefix . '[settings][body]',
                        'value' => (string) ($settings['body'] ?? ''),
                        'placeholder' => Yii::t('EngagementPagesModule.base', 'Write consultation copy…'),
                        'height' => 320,
                        'profile' => 'page',
                        'forms' => $formOptions,
                    ]) ?>
                </div>
            </div>

        <?php elseif ($type === 'survey_cta'): ?>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Thiscovery Form') ?></label>
                <select class="form-control" name="<?= $namePrefix ?>[settings][form_id]">
                    <?php foreach ($formOptions as $id => $title): ?>
                        <option value="<?= Html::encode((string) $id) ?>" <?= (string) ($settings['form_id'] ?? '') === (string) $id ? 'selected' : '' ?>>
                            <?= Html::encode($title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Button label') ?></label>
                <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][button_label]"
                       value="<?= Html::encode($settings['button_label'] ?? Yii::t('EngagementPagesModule.base', 'Take the survey')) ?>"
                       data-ep-card-title-source>
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Intro text') ?></label>
                <div class="ep-rich-editor" data-ep-rich-editor>
                    <?= EditorField::widget([
                        'id' => 'ep-survey-intro-' . $safeIndex,
                        'name' => $namePrefix . '[settings][intro]',
                        'value' => (string) ($settings['intro'] ?? ''),
                        'placeholder' => Yii::t('EngagementPagesModule.base', 'Explain why people should take the survey…'),
                        'height' => 180,
                        'profile' => 'simple',
                    ]) ?>
                </div>
            </div>

        <?php elseif ($type === 'poll_embed'): ?>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Quick poll') ?></label>
                <select class="form-control" name="<?= $namePrefix ?>[settings][form_id]">
                    <?php foreach ($pollOptions as $id => $title): ?>
                        <option value="<?= Html::encode((string) $id) ?>" <?= (string) ($settings['form_id'] ?? '') === (string) $id ? 'selected' : '' ?>>
                            <?= Html::encode($title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ep-hint text-muted">
                    <?= Yii::t('EngagementPagesModule.base', 'Shows the poll on the page so people can vote without leaving.') ?>
                </div>
            </div>

        <?php elseif ($type === 'downloads'): ?>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Title') ?></label>
                <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
                       value="<?= Html::encode($settings['title'] ?? Yii::t('EngagementPagesModule.base', 'Documents')) ?>"
                       data-ep-card-title-source>
            </div>
            <div class="ep-download-items" data-ep-download-items>
                <?php
                $downloadItems = $settings['items'] ?? [['label' => '', 'file_guid' => '', 'alt' => '', 'url' => '']];
                if ($downloadItems === []) {
                    $downloadItems = [['label' => '', 'file_guid' => '', 'alt' => '', 'url' => '']];
                }
                foreach ($downloadItems as $j => $item):
                    echo $this->render('_download_item', [
                        'sectionIndex' => $index,
                        'itemIndex' => $j,
                        'item' => $item,
                        'namePrefix' => $namePrefix,
                        'page' => $page,
                    ]);
                endforeach;
                ?>
            </div>
            <button type="button" class="btn btn-sm btn-light" data-ep-add-download>
                <i class="fa fa-plus"></i> <?= Yii::t('EngagementPagesModule.base', 'Add document') ?>
            </button>

        <?php elseif ($type === 'container'): ?>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Container title') ?></label>
                <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
                       value="<?= Html::encode($settings['title'] ?? Yii::t('EngagementPagesModule.base', 'Container')) ?>"
                       data-ep-card-title-source>
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Columns') ?></label>
                <select class="form-control" name="<?= $namePrefix ?>[settings][columns]"
                        data-ep-container-columns style="max-width:220px">
                    <?php
                    $cols = \humhub\modules\engagementPages\blocks\ContainerBlock::clampColumns($settings['columns'] ?? 1);
                    foreach (\humhub\modules\engagementPages\blocks\ContainerBlock::columnOptions() as $value => $colLabel):
                    ?>
                        <option value="<?= (int) $value ?>" <?= $cols === (int) $value ? 'selected' : '' ?>>
                            <?= Html::encode($colLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ep-hint text-muted">
                    <?= Yii::t('EngagementPagesModule.base', 'Drop elements into each column. On small screens columns stack.') ?>
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="hidden" name="<?= $namePrefix ?>[settings][show_title]" value="0">
                <input class="form-check-input" type="checkbox" value="1"
                       name="<?= $namePrefix ?>[settings][show_title]"
                       id="ep-show-title-<?= Html::encode($safeIndex) ?>"
                    <?= !isset($settings['show_title']) || !empty($settings['show_title']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="ep-show-title-<?= Html::encode($safeIndex) ?>">
                    <?= Yii::t('EngagementPagesModule.base', 'Show title on public page') ?>
                </label>
            </div>
        <?php else: ?>
            <?= $this->render('_block_fields', [
                'type' => $type,
                'settings' => $settings,
                'namePrefix' => $namePrefix,
                'safeIndex' => $safeIndex,
                'formOptions' => $formOptions,
                'pollOptions' => $pollOptions ?? [],
                'page' => $page,
            ]) ?>
        <?php endif; ?>
    </div>

    <?php if ($type === 'container' && !$isChild): ?>
        <?php
        $cols = \humhub\modules\engagementPages\blocks\ContainerBlock::clampColumns($settings['columns'] ?? 1);
        $childrenByCol = array_fill(0, $cols, []);
        foreach (array_values((array) ($section['children'] ?? [])) as $child) {
            $c = (int) ($child['column'] ?? 0);
            if ($c < 0 || $c >= $cols) {
                $c = 0;
            }
            $childrenByCol[$c][] = $child;
        }
        ?>
        <div class="ep-container-zone" data-ep-container-zone data-ep-cols="<?= (int) $cols ?>">
            <div class="ep-container-zone__label">
                <?= Yii::t('EngagementPagesModule.base', 'Drop elements into columns') ?>
            </div>
            <div class="ep-container-zone__grid" data-ep-container-grid data-cols="<?= (int) $cols ?>">
                <?php for ($col = 0; $col < $cols; $col++): ?>
                    <div class="ep-container-col" data-ep-container-col="<?= (int) $col ?>">
                        <div class="ep-container-col__label">
                            <?= Yii::t('EngagementPagesModule.base', 'Column {n}', ['n' => $col + 1]) ?>
                        </div>
                        <div class="ep-container-children"
                             data-ep-container-children
                             data-ep-drop-zone="container"
                             data-ep-container-col="<?= (int) $col ?>">
                            <?php foreach ($childrenByCol[$col] as $ci => $child): ?>
                                <?= $this->render('_section_card', [
                                    'index' => $index . '-c' . $col . '-' . $ci,
                                    'section' => $child,
                                    'formOptions' => $formOptions,
                                    'pollOptions' => $pollOptions ?? [],
                                    'blockLabels' => $blockLabels,
                                    'collapsed' => true,
                                    'isChild' => true,
                                    'namePrefix' => $namePrefix . '[children][' . $col . '-' . $ci . ']',
                                    'page' => $page,
                                    'column' => $col,
                                ]) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
