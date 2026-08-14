<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\engagementPages\assets\EngagementPagesAsset;
use humhub\modules\engagementPages\helpers\Url;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\modules\engagementPages\services\BlockRegistry;
use humhub\modules\thiscoveryEditor\widgets\EditorField;
use humhub\widgets\bootstrap\Button;

/** @var ContentContainerActiveRecord|null $contentContainer */
/** @var EngagementPage $page */
/** @var bool $isNew */
/** @var array $blockLabels */
/** @var array $formOptions */
/** @var array $pollOptions */
/** @var EngagementPage[]|null $templates */

EngagementPagesAsset::register($this);

$isDirectory = $page->isDirectoryHome();
$isTemplate = $page->isTemplate();
$templates = $templates ?? [];
$pollOptions = $pollOptions ?? [];
$publicPrefix = EngagementPage::publicPrefix();
$this->title = $isNew
    ? Yii::t('EngagementPagesModule.base', 'Create page')
    : ($isDirectory
        ? Yii::t('EngagementPagesModule.base', 'Edit homepage')
        : ($isTemplate
            ? Yii::t('EngagementPagesModule.base', 'Edit template')
            : Yii::t('EngagementPagesModule.base', 'Edit page')));

$sections = $page->getSections();
$grouped = BlockRegistry::groupByRegion($sections);
$layout = $page->getLayoutKey();
$shareUrl = !$isNew ? Url::toPublic($page, true) : '';
$palette = BlockRegistry::palette();
$layoutOptions = BlockRegistry::layoutOptions();

$regionLabels = [
    BlockRegistry::REGION_FULL => Yii::t('EngagementPagesModule.base', 'Full width'),
    BlockRegistry::REGION_LEFT => Yii::t('EngagementPagesModule.base', 'Left column'),
    BlockRegistry::REGION_MAIN => Yii::t('EngagementPagesModule.base', 'Main column'),
    BlockRegistry::REGION_RIGHT => Yii::t('EngagementPagesModule.base', 'Right column'),
];

$this->registerJsConfig('engagementPages', [
    'untitled' => Yii::t('EngagementPagesModule.base', 'Untitled section'),
    'types' => $blockLabels,
    'clearConfirm' => Yii::t('EngagementPagesModule.base', 'Remove all sections from this page?'),
    'copied' => Yii::t('EngagementPagesModule.base', 'Copied!'),
    'noContainerInContainer' => Yii::t('EngagementPagesModule.base', 'Containers cannot be nested. Drop the container into a column instead.'),
    'columnLabel' => Yii::t('EngagementPagesModule.base', 'Column {n}'),
    'needTitle' => Yii::t('EngagementPagesModule.base', 'Please enter a page title before saving.'),
    'needSlug' => Yii::t('EngagementPagesModule.base', 'Please enter a URL slug before saving.'),
]);
$this->registerJs('humhub.require("engagementPages").initBuilder("#ep-builder");', \yii\web\View::POS_READY);
?>

<div class="ep-studio panel panel-default" id="ep-builder" data-ep-layout="<?= Html::encode($layout) ?>">
    <?= Html::beginForm('', 'post', ['class' => 'ep-studio__form', 'novalidate' => true]) ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

    <?php if ($page->hasErrors()): ?>
        <div class="alert alert-danger ep-studio__errors" role="alert" data-ep-form-errors>
            <strong><?= Yii::t('EngagementPagesModule.base', 'Could not save the page.') ?></strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($page->getErrors() as $attr => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <li><?= Html::encode($message) ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="ep-studio__tabs" role="tablist">
        <button type="button" class="ep-studio__tab is-active" data-ep-tab="builder" role="tab" aria-selected="true">
            <?= Yii::t('EngagementPagesModule.base', 'Page builder') ?>
        </button>
        <button type="button" class="ep-studio__tab" data-ep-tab="settings" role="tab" aria-selected="false">
            <?= Yii::t('EngagementPagesModule.base', 'Settings') ?>
        </button>
        <button type="button" class="ep-studio__tab" data-ep-tab="share" role="tab" aria-selected="false">
            <?= Yii::t('EngagementPagesModule.base', 'Share') ?>
        </button>
    </div>

    <div class="ep-studio__panel is-active" data-ep-panel="builder">
        <div class="ep-studio__workspace">
            <aside class="ep-studio__palette" data-ep-palette>
                <div class="ep-palette__title"><?= Yii::t('EngagementPagesModule.base', 'Add sections') ?></div>
                <?php
                $groups = [
                    'layout' => Yii::t('EngagementPagesModule.base', 'Layout'),
                    'content' => Yii::t('EngagementPagesModule.base', 'Content'),
                    'engagement' => Yii::t('EngagementPagesModule.base', 'Engagement'),
                ];
                foreach ($groups as $groupKey => $groupLabel):
                    $items = array_filter($palette, static fn($p) => $p['group'] === $groupKey);
                    if (!$items) {
                        continue;
                    }
                    ?>
                    <div class="ep-palette__group">
                        <div class="ep-palette__group-label"><?= Html::encode($groupLabel) ?></div>
                        <?php foreach ($items as $item): ?>
                            <button type="button"
                                    class="ep-palette__item"
                                    draggable="true"
                                    data-ep-palette-type="<?= Html::encode($item['type']) ?>"
                                    title="<?= Html::encode($blockLabels[$item['type']] ?? $item['type']) ?>">
                                <span class="ep-palette__icon"><i class="fa <?= Html::encode($item['icon']) ?>"></i></span>
                                <span class="ep-palette__label"><?= Html::encode($blockLabels[$item['type']] ?? $item['type']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <button type="button" class="btn btn-sm btn-dark ep-palette__clear" data-ep-clear-sections>
                    <?= Yii::t('EngagementPagesModule.base', 'Clear') ?>
                </button>
            </aside>

            <div class="ep-studio__canvas" data-ep-canvas>
                <div class="ep-layout-bar">
                    <label class="ep-layout-bar__label" for="ep-layout-select">
                        <?= Yii::t('EngagementPagesModule.base', 'Columns') ?>
                    </label>
                    <select id="ep-layout-select" class="form-control ep-layout-bar__select"
                            name="EngagementPage[layout]" data-ep-layout-select>
                        <?php foreach ($layoutOptions as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>" <?= $layout === $value ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="ep-layout-bar__label" for="ep-width-select">
                        <?= Yii::t('EngagementPagesModule.base', 'Page width') ?>
                    </label>
                    <select id="ep-width-select" class="form-control ep-layout-bar__select"
                            name="EngagementPage[page_width]" data-ep-page-width>
                        <?php foreach (EngagementPage::pageWidthOptions() as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>" <?= $page->getPageWidthKey() === $value ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="ep-layout-bar__hint">
                        <?= Yii::t('EngagementPagesModule.base', 'Choose columns for the canvas, and the public page width for this client.') ?>
                    </p>
                </div>

                <div class="ep-edit-stage d-none" data-ep-edit-stage aria-hidden="true">
                    <div class="ep-edit-stage__bar">
                        <strong><?= Yii::t('EngagementPagesModule.base', 'Edit section') ?></strong>
                        <button type="button" class="btn btn-sm btn-primary" data-ep-edit-done>
                            <?= Yii::t('EngagementPagesModule.base', 'Done') ?>
                        </button>
                    </div>
                    <div class="ep-edit-stage__body" data-ep-edit-stage-body></div>
                </div>

                <div class="ep-canvas-empty<?= !empty($sections) ? ' d-none' : '' ?>" data-ep-empty>
                    <div class="ep-canvas-empty__inner">
                        <i class="fa fa-hand-pointer-o" aria-hidden="true"></i>
                        <p><?= Yii::t('EngagementPagesModule.base', 'Drag a section or container into a column.') ?></p>
                        <span><?= Yii::t('EngagementPagesModule.base', 'Or click a palette item to add it to the main column.') ?></span>
                    </div>
                </div>

                <div class="ep-builder-board" data-ep-board>
                    <?= $this->render('_region', [
                        'region' => BlockRegistry::REGION_FULL,
                        'label' => $regionLabels[BlockRegistry::REGION_FULL],
                        'items' => $grouped[BlockRegistry::REGION_FULL] ?? [],
                        'formOptions' => $formOptions,
                        'pollOptions' => $pollOptions ?? [],
                        'blockLabels' => $blockLabels,
                        'hidden' => false,
                        'page' => $page,
                    ]) ?>

                    <div class="ep-builder-columns" data-ep-columns data-layout="<?= Html::encode($layout) ?>">
                        <?= $this->render('_region', [
                            'region' => BlockRegistry::REGION_LEFT,
                            'label' => $regionLabels[BlockRegistry::REGION_LEFT],
                            'items' => $grouped[BlockRegistry::REGION_LEFT] ?? [],
                            'formOptions' => $formOptions,
                        'pollOptions' => $pollOptions ?? [],
                            'blockLabels' => $blockLabels,
                            'hidden' => !in_array($layout, [BlockRegistry::LAYOUT_LEFT, BlockRegistry::LAYOUT_BOTH], true),
                            'page' => $page,
                        ]) ?>
                        <?= $this->render('_region', [
                            'region' => BlockRegistry::REGION_MAIN,
                            'label' => $regionLabels[BlockRegistry::REGION_MAIN],
                            'items' => $grouped[BlockRegistry::REGION_MAIN] ?? [],
                            'formOptions' => $formOptions,
                        'pollOptions' => $pollOptions ?? [],
                            'blockLabels' => $blockLabels,
                            'hidden' => false,
                            'page' => $page,
                        ]) ?>
                        <?= $this->render('_region', [
                            'region' => BlockRegistry::REGION_RIGHT,
                            'label' => $regionLabels[BlockRegistry::REGION_RIGHT],
                            'items' => $grouped[BlockRegistry::REGION_RIGHT] ?? [],
                            'formOptions' => $formOptions,
                        'pollOptions' => $pollOptions ?? [],
                            'blockLabels' => $blockLabels,
                            'hidden' => !in_array($layout, [BlockRegistry::LAYOUT_RIGHT, BlockRegistry::LAYOUT_BOTH], true),
                            'page' => $page,
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ep-studio__panel" data-ep-panel="settings">
        <div class="ep-studio__settings">
            <h5 class="ep-section__title"><?= Yii::t('EngagementPagesModule.base', 'Page details') ?></h5>

            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Title') ?></label>
                <input type="text" class="form-control form-control-lg" name="EngagementPage[title]" value="<?= Html::encode($page->title) ?>" required>
                <?php if ($page->hasErrors('title')): ?>
                    <div class="help-block help-block-error"><?= Html::encode(implode(' ', $page->getErrors('title'))) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'URL slug') ?></label>
                <?php if ($isDirectory): ?>
                    <div class="input-group">
                        <span class="input-group-text">/</span>
                        <input type="text" class="form-control" name="EngagementPage[slug]"
                               value="<?= Html::encode($page->slug ?: EngagementPage::DEFAULT_PUBLIC_PREFIX) ?>"
                               required
                               data-ep-home-slug>
                    </div>
                    <div class="ep-hint text-muted">
                        <?= Yii::t('EngagementPagesModule.base', 'Public homepage URL. Other pages are nested under this path, for example /your-slug/another-page.') ?>
                    </div>
                    <?php if ($page->hasErrors('slug')): ?>
                        <div class="help-block help-block-error"><?= Html::encode(implode(' ', $page->getErrors('slug'))) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="input-group">
                        <span class="input-group-text">/<?= Html::encode($publicPrefix) ?>/</span>
                        <input type="text" class="form-control" name="EngagementPage[slug]" value="<?= Html::encode($page->slug) ?>" required>
                    </div>
                    <div class="ep-hint text-muted">
                        <?= Yii::t('EngagementPagesModule.base', 'Custom URL path for this page. Use lowercase letters, numbers, and hyphens.') ?>
                    </div>
                    <?php if ($page->hasErrors('slug')): ?>
                        <div class="help-block help-block-error"><?= Html::encode(implode(' ', $page->getErrors('slug'))) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Summary') ?>
                    <span class="ep-optional"><?= Yii::t('EngagementPagesModule.base', 'optional') ?></span>
                </label>
                <div class="ep-rich-editor" data-ep-rich-editor>
                    <?= EditorField::widget([
                        'id' => 'ep-page-summary',
                        'name' => 'EngagementPage[summary]',
                        'value' => (string) $page->summary,
                        'placeholder' => Yii::t('EngagementPagesModule.base', 'Short summary for directories…'),
                        'height' => 180,
                        'profile' => 'simple',
                    ]) ?>
                </div>
            </div>

            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Status') ?></label>
                <?php if ($isTemplate): ?>
                    <input type="hidden" name="EngagementPage[status]" value="<?= (int) EngagementPage::STATUS_DRAFT ?>">
                    <input type="hidden" name="EngagementPage[is_template]" value="1">
                    <p class="ep-hint text-muted mb-0">
                        <?= Yii::t('EngagementPagesModule.base', 'Templates stay as drafts and are not published publicly.') ?>
                    </p>
                <?php else: ?>
                    <select class="form-control" name="EngagementPage[status]" style="max-width:280px">
                        <?php foreach (EngagementPage::statusOptions() as $value => $label): ?>
                            <option value="<?= (int) $value ?>" <?= (int) $page->status === (int) $value ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="ep-label" for="ep-width-select-settings">
                    <?= Yii::t('EngagementPagesModule.base', 'Page width') ?>
                </label>
                <select id="ep-width-select-settings" class="form-control" name="EngagementPage[page_width]"
                        data-ep-page-width style="max-width:320px">
                    <?php foreach (EngagementPage::pageWidthOptions() as $value => $label): ?>
                        <option value="<?= Html::encode($value) ?>" <?= $page->getPageWidthKey() === $value ? 'selected' : '' ?>>
                            <?= Html::encode($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ep-hint text-muted">
                    <?= Yii::t('EngagementPagesModule.base', 'Controls how wide this page appears publicly. Wide is 1440px; Full uses the browser width with side padding.') ?>
                </div>
            </div>

            <?php if (!$isTemplate): ?>
                <div class="form-group">
                    <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Who can view') ?></label>
                    <select class="form-control" name="EngagementPage[audience]" style="max-width:360px">
                        <?php foreach (EngagementPage::audienceOptions() as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>" <?= $page->getAudienceKey() === $value ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ep-hint text-muted">
                        <?= Yii::t('EngagementPagesModule.base', 'Public pages are open to guests. Community members only requires sign-in.') ?>
                    </div>
                </div>
            <?php else: ?>
                <input type="hidden" name="EngagementPage[audience]" value="<?= Html::encode(EngagementPage::AUDIENCE_PUBLIC) ?>">
            <?php endif; ?>

            <h5 class="ep-section__title" style="margin-top:1.75rem"><?= Yii::t('EngagementPagesModule.base', 'Directory listing') ?></h5>
            <?php if ($isTemplate): ?>
                <input type="hidden" name="EngagementPage[listed]" value="0">
                <input type="hidden" name="EngagementPage[featured]" value="0">
                <p class="ep-hint text-muted">
                    <?= Yii::t('EngagementPagesModule.base', 'Templates are never listed on the public homepage. Use “Create from template” on the page list.') ?>
                </p>
            <?php elseif ($isDirectory): ?>
                <input type="hidden" name="EngagementPage[listed]" value="0">
                <input type="hidden" name="EngagementPage[featured]" value="0">
                <input type="hidden" name="EngagementPage[is_directory]" value="1">
                <p class="ep-hint text-muted">
                    <?= Yii::t('EngagementPagesModule.base', 'Add a Collection section in the builder to list pages, forms, or spaces. This homepage is never listed as a card on itself.') ?>
                </p>
            <?php else: ?>
                <div class="form-check mb-2">
                    <input type="hidden" name="EngagementPage[listed]" value="0">
                    <input class="form-check-input" type="checkbox" value="1" name="EngagementPage[listed]" id="ep-listed"
                        <?= !empty($page->listed) || $page->isNewRecord ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ep-listed">
                        <?= Yii::t('EngagementPagesModule.base', 'Show in public directory') ?>
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="EngagementPage[featured]" value="0">
                    <input class="form-check-input" type="checkbox" value="1" name="EngagementPage[featured]" id="ep-featured"
                        <?= !empty($page->featured) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ep-featured">
                        <?= Yii::t('EngagementPagesModule.base', 'Featured on directory') ?>
                    </label>
                </div>
            <?php endif; ?>
            <?php if (!$isDirectory): ?>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Category') ?>
                    <span class="ep-optional"><?= Yii::t('EngagementPagesModule.base', 'optional') ?></span>
                </label>
                <input type="text" class="form-control" name="EngagementPage[category]"
                       value="<?= Html::encode((string) $page->category) ?>"
                       placeholder="<?= Yii::t('EngagementPagesModule.base', 'e.g. Consultation, Survey') ?>"
                       style="max-width:320px">
            </div>
            <div class="form-group">
                <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Closes at') ?>
                    <span class="ep-optional"><?= Yii::t('EngagementPagesModule.base', 'optional') ?></span>
                </label>
                <input type="date" class="form-control" name="EngagementPage[closes_at]"
                       value="<?= Html::encode($page->closes_at ? date('Y-m-d', strtotime((string) $page->closes_at)) : '') ?>"
                       style="max-width:220px">
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ep-studio__panel" data-ep-panel="share">
        <div class="ep-studio__settings">
            <h5 class="ep-section__title"><?= Yii::t('EngagementPagesModule.base', 'Public URL') ?></h5>
            <?php if ($isNew): ?>
                <p class="ep-hint text-muted">
                    <?= Yii::t('EngagementPagesModule.base', 'Save the page first to generate a shareable link.') ?>
                </p>
            <?php else: ?>
                <div class="form-group">
                    <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Public link') ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" readonly value="<?= Html::encode($shareUrl) ?>" data-ep-share-url>
                        <button type="button" class="btn btn-primary" data-ep-copy-url>
                            <i class="fa fa-clipboard"></i>
                            <?= Yii::t('EngagementPagesModule.base', 'Copy link') ?>
                        </button>
                    </div>
                    <div class="ep-copy-feedback text-success d-none" data-ep-copy-feedback>
                        <?= Yii::t('EngagementPagesModule.base', 'Copied!') ?>
                    </div>
                </div>
                <p>
                    <a href="<?= Html::encode(Url::toPublic($page)) ?>" target="_blank" rel="noopener">
                        <?= Yii::t('EngagementPagesModule.base', 'Open public page') ?>
                        <i class="fa fa-external-link"></i>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script type="text/template" id="ep-section-template">
        <?= $this->render('_section_card', [
            'index' => '__INDEX__',
            'section' => ['type' => 'rich_text', 'region' => 'main', 'settings' => []],
            'formOptions' => $formOptions,
            'pollOptions' => $pollOptions ?? [],
            'blockLabels' => $blockLabels,
            'collapsed' => false,
            'isChild' => false,
            'page' => $page,
        ]) ?>
    </script>
    <?php foreach (array_keys($blockLabels) as $type): ?>
        <?php
        $templateSettings = match ($type) {
            'downloads' => ['items' => [['label' => '', 'file_guid' => '', 'alt' => '']]],
            'container' => [
                'title' => Yii::t('EngagementPagesModule.base', 'Container'),
                'show_title' => true,
                'columns' => 2,
            ],
            'phases' => ['title' => Yii::t('EngagementPagesModule.base', 'Project phases'), 'style' => 'linear', 'items' => [['label' => '', 'description' => '', 'status' => 'upcoming']]],
            'events' => ['title' => Yii::t('EngagementPagesModule.base', 'Upcoming events'), 'items' => [['title' => '', 'date' => '', 'time' => '', 'location' => '', 'url' => '', 'cta_label' => Yii::t('EngagementPagesModule.base', 'Register')]]],
            'team' => ['title' => Yii::t('EngagementPagesModule.base', 'Meet the team'), 'people' => [['name' => '', 'role' => '', 'email' => '', 'phone' => '', 'bio' => '']]],
            'contact' => ['title' => Yii::t('EngagementPagesModule.base', 'Contact us'), 'show_email_link' => true],
            'updates' => [],
            'comments' => [
                'title' => Yii::t('EngagementPagesModule.base', 'Comments'),
                'allow_guests' => true,
                'ask_name' => true,
                'require_email' => true,
                'moderate_guests' => true,
                'show_comments' => true,
            ],
            'accordion' => ['title' => Yii::t('EngagementPagesModule.base', 'Frequently asked questions'), 'items' => [['heading' => '', 'body' => '']]],
            'callout' => ['tone' => 'info'],
            'image' => [],
            'directory' => [
                'title' => Yii::t('EngagementPagesModule.base', 'Open for feedback'),
                'source' => 'pages',
                'show_featured_first' => true,
                'event_limit' => 5,
            ],
            'collection' => [
                'title' => Yii::t('EngagementPagesModule.base', 'Open for feedback'),
                'source' => 'pages',
                'show_featured_first' => true,
                'event_limit' => 5,
            ],
            default => [],
        };
        $region = BlockRegistry::defaultRegion($type);
        ?>
        <script type="text/template" id="ep-section-template-<?= Html::encode($type) ?>">
            <?= $this->render('_section_card', [
                'index' => '__INDEX__',
                'section' => [
                    'type' => $type,
                    'region' => $region,
                    'settings' => $templateSettings,
                    'children' => $type === 'container' ? [] : null,
                ],
                'formOptions' => $formOptions,
                'pollOptions' => $pollOptions ?? [],
                'blockLabels' => $blockLabels,
                'collapsed' => false,
                'isChild' => false,
                'page' => $page,
            ]) ?>
        </script>
    <?php endforeach; ?>

    <script type="text/template" id="ep-download-item-template">
        <?= $this->render('_download_item', [
            'sectionIndex' => '__SEC__',
            'itemIndex' => '__ROW__',
            'item' => ['label' => '', 'file_guid' => '', 'alt' => ''],
            'page' => $page,
        ]) ?>
    </script>
    <script type="text/template" id="ep-repeat-template-phases">
        <?= $this->render('_item_phase', [
            'namePrefix' => 'sections[__SEC__][settings][items][__ROW__]',
            'item' => ['label' => '', 'description' => '', 'status' => 'upcoming'],
            'safeIndex' => '__SEC__-__ROW__',
        ]) ?>
    </script>
    <script type="text/template" id="ep-repeat-template-events">
        <?= $this->render('_item_event', [
            'namePrefix' => 'sections[__SEC__][settings][items][__ROW__]',
            'item' => ['title' => '', 'date' => '', 'time' => '', 'location' => '', 'url' => '', 'cta_label' => Yii::t('EngagementPagesModule.base', 'Register')],
        ]) ?>
    </script>
    <script type="text/template" id="ep-repeat-template-team">
        <?= $this->render('_item_person', [
            'namePrefix' => 'sections[__SEC__][settings][people][__ROW__]',
            'item' => ['name' => '', 'role' => '', 'email' => '', 'phone' => '', 'bio' => ''],
        ]) ?>
    </script>
    <script type="text/template" id="ep-repeat-template-accordion">
        <?= $this->render('_item_accordion', [
            'namePrefix' => 'sections[__SEC__][settings][items][__ROW__]',
            'item' => ['heading' => '', 'body' => ''],
            'safeIndex' => '__SEC__-__ROW__',
        ]) ?>
    </script>

    <div class="ep-studio__footer">
        <?= Button::light(Yii::t('EngagementPagesModule.base', 'Cancel'))
            ->link(Url::toIndex($contentContainer)) ?>
        <?php if (!$isNew && !$isDirectory && !$isTemplate): ?>
            <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#ep-save-template-modal">
                <i class="fa fa-files-o"></i>
                <?= Yii::t('EngagementPagesModule.base', 'Save as template') ?>
            </button>
        <?php endif; ?>
        <?= Button::save(Yii::t('EngagementPagesModule.base', $isTemplate ? 'Save template' : 'Save page'))->submit()->icon('floppy-o') ?>
    </div>

    <?= Html::endForm() ?>

    <?php if (!$isNew && !$isDirectory && !$isTemplate): ?>
        <div class="modal fade" id="ep-save-template-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <?= Html::beginForm(Url::toSaveTemplate($page), 'post') ?>
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><?= Yii::t('EngagementPagesModule.base', 'Save as template') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            <?= Yii::t('EngagementPagesModule.base', 'Creates a reusable copy of this page’s layout, width, and sections. File uploads stay shared by GUID.') ?>
                        </p>
                        <div class="form-group mb-0">
                            <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Template name') ?></label>
                            <input type="text" class="form-control" name="template_title"
                                   value="<?= Html::encode($page->title) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <?= Yii::t('EngagementPagesModule.base', 'Cancel') ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <?= Yii::t('EngagementPagesModule.base', 'Save template') ?>
                        </button>
                    </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
