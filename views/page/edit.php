<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;
use humhub\widgets\bootstrap\Button;

/** @var ContentContainerActiveRecord|null $contentContainer */
/** @var EngagementPage $page */
/** @var bool $isNew */
/** @var array $blockLabels */
/** @var array $formOptions */
/** @var array $pollOptions */
/** @var array $mapOptions */
/** @var EngagementPage[]|null $templates */
/** @var array $collectionOptions */
/** @var array $spaceOptions */
/** @var array $pageOptions */
/** @var array $groupOptions */
/** @var \humhub\modules\thiscoveryPageBuilder\models\PageHome[] $pageHomes */

ThiscoveryPageBuilderAsset::register($this);

$isDirectory = $page->isCollection();
$isTemplate = $page->isTemplate();
$templates = $templates ?? [];
$pollOptions = $pollOptions ?? [];
$mapOptions = $mapOptions ?? [];
$collectionOptions = $collectionOptions ?? EngagementPage::collectionOptions(null, $page->id);
$spaceOptions = $spaceOptions ?? EngagementPage::spaceOptions();
$pageOptions = $pageOptions ?? EngagementPage::publishedPageOptions($page->id);
$groupOptions = $groupOptions ?? [];
$pageHomes = $pageHomes ?? [];
$publicPrefix = EngagementPage::publicPrefix();
$parentSlug = $page->getParentUrlSlug();
$this->title = $isNew
    ? Yii::t('ThiscoveryPageBuilderModule.base', $page->isCollection() ? 'Create collection' : 'Create page')
    : ($isDirectory
        ? Yii::t('ThiscoveryPageBuilderModule.base', 'Edit collection')
        : ($isTemplate
            ? Yii::t('ThiscoveryPageBuilderModule.base', 'Edit template')
            : Yii::t('ThiscoveryPageBuilderModule.base', 'Edit page')));

$sections = $page->getSections();
$grouped = BlockRegistry::groupByRegion($sections);
$layout = $page->getLayoutKey();
$shareUrl = !$isNew ? Url::toPublic($page, true) : '';
$palette = BlockRegistry::palette();
$layoutOptions = BlockRegistry::layoutOptions();

$regionLabels = [
    BlockRegistry::REGION_FULL => Yii::t('ThiscoveryPageBuilderModule.base', 'Full width'),
    BlockRegistry::REGION_LEFT => Yii::t('ThiscoveryPageBuilderModule.base', 'Left column'),
    BlockRegistry::REGION_MAIN => Yii::t('ThiscoveryPageBuilderModule.base', 'Main column'),
    BlockRegistry::REGION_RIGHT => Yii::t('ThiscoveryPageBuilderModule.base', 'Right column'),
];

$this->registerJsConfig('thiscoveryPageBuilder', [
    'untitled' => Yii::t('ThiscoveryPageBuilderModule.base', 'Untitled section'),
    'types' => $blockLabels,
    'clearConfirm' => Yii::t('ThiscoveryPageBuilderModule.base', 'Remove all sections from this page?'),
    'copied' => Yii::t('ThiscoveryPageBuilderModule.base', 'Copied!'),
    'noContainerInContainer' => Yii::t('ThiscoveryPageBuilderModule.base', 'Containers cannot be nested. Drop the container into a column instead.'),
    'columnLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'Column {n}'),
    'needTitle' => Yii::t('ThiscoveryPageBuilderModule.base', 'Please enter a page title before saving.'),
    'needSlug' => Yii::t('ThiscoveryPageBuilderModule.base', 'Please enter a URL slug before saving.'),
    'guideShow' => Yii::t('ThiscoveryPageBuilderModule.base', 'Guidance'),
    'guideHide' => Yii::t('ThiscoveryPageBuilderModule.base', 'Hide guidance'),
]);
$this->registerJs('humhub.require("thiscoveryPageBuilder").initBuilder("#ep-builder");', \yii\web\View::POS_READY);

$activeTab = (string) Yii::$app->request->get('tab', 'builder');
if (!in_array($activeTab, ['builder', 'settings', 'share'], true)) {
    $activeTab = 'builder';
}
$navTitle = $isNew
    ? Yii::t('ThiscoveryPageBuilderModule.base', $page->isCollection() ? 'New collection' : ($isTemplate ? 'New template' : 'New page'))
    : ($page->title !== '' ? $page->title : $this->title);
$saveLabel = Yii::t('ThiscoveryPageBuilderModule.base', $isTemplate ? 'Save template' : 'Save page');
$backParams = [];
$parentId = (int) ($page->parent_id ?: Yii::$app->request->get('parent_id', 0));
if ($parentId > 0) {
    $backParams['collection'] = $parentId;
} elseif (!$isNew && $page->isCollection()) {
    $backParams['collection'] = (int) $page->id;
}
$backUrl = Url::toIndex($contentContainer, $backParams);
?>

<div class="ep-studio panel panel-default" id="ep-builder" data-ep-layout="<?= Html::encode($layout) ?>">
    <div class="ep-studio__nav">
        <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Back to pages'))
            ->link($backUrl)
            ->icon('arrow-left')
            ->loader(false) ?>
        <div class="ep-studio__nav-title"><?= Html::encode($navTitle) ?></div>
        <div class="ep-studio__nav-actions">
            <?php if (!$isNew && !$isTemplate): ?>
                <a class="btn btn-light" href="<?= Html::encode(Url::toPublic($page)) ?>" target="_blank" rel="noopener">
                    <i class="fa fa-external-link" aria-hidden="true"></i>
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Open page') ?>
                </a>
            <?php endif; ?>
            <button type="submit" name="after_save" value="preview" form="ep-studio-form" class="btn btn-primary ep-studio__preview-btn">
                <i class="fa fa-eye" aria-hidden="true"></i>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Preview') ?>
            </button>
            <?= Button::save($saveLabel)
                ->submit()
                ->icon('floppy-o')
                ->options(['form' => 'ep-studio-form'])
                ->cssClass('ep-studio__preview-btn')
                ->loader(false) ?>
        </div>
    </div>

    <?= Html::beginForm('', 'post', [
        'class' => 'ep-studio__form',
        'id' => 'ep-studio-form',
        'novalidate' => true,
    ]) ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
    <?= Html::hiddenInput('studio_tab', $activeTab, ['data-ep-studio-tab' => true]) ?>

    <?php if ($page->hasErrors()): ?>
        <div class="alert alert-danger ep-studio__errors" role="alert" data-ep-form-errors>
            <strong><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Could not save the page.') ?></strong>
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
        <button type="button" class="ep-studio__tab <?= $activeTab === 'builder' ? 'is-active' : '' ?>" data-ep-tab="builder" role="tab" aria-selected="<?= $activeTab === 'builder' ? 'true' : 'false' ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page builder') ?>
        </button>
        <button type="button" class="ep-studio__tab <?= $activeTab === 'settings' ? 'is-active' : '' ?>" data-ep-tab="settings" role="tab" aria-selected="<?= $activeTab === 'settings' ? 'true' : 'false' ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Settings') ?>
        </button>
        <button type="button" class="ep-studio__tab <?= $activeTab === 'share' ? 'is-active' : '' ?>" data-ep-tab="share" role="tab" aria-selected="<?= $activeTab === 'share' ? 'true' : 'false' ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Share') ?>
        </button>
        <a class="ep-studio__help-link"
           href="<?= Html::encode(Url::toHelp($contentContainer, 'creators-builder')) ?>"
           target="_blank"
           rel="noopener"
           data-ep-studio-help
           data-ep-help-pages="<?= Html::encode(json_encode([
               'builder' => Url::toHelp($contentContainer, 'creators-builder'),
               'settings' => Url::toHelp($contentContainer, 'creators-settings'),
               'share' => Url::toHelp($contentContainer, 'creators-publishing'),
           ])) ?>">
            <i class="fa fa-question-circle" aria-hidden="true"></i>
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Help') ?>
        </a>
    </div>

    <div class="ep-studio__panel <?= $activeTab === 'builder' ? 'is-active' : '' ?>" data-ep-panel="builder">
        <div class="ep-studio__workspace">
            <aside class="ep-studio__palette" data-ep-palette>
                <div class="ep-palette__scroll">
                <div class="ep-palette__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Add sections') ?></div>
                <?php
                $groups = [
                    'layout' => Yii::t('ThiscoveryPageBuilderModule.base', 'Layout'),
                    'content' => Yii::t('ThiscoveryPageBuilderModule.base', 'Content'),
                    'engagement' => Yii::t('ThiscoveryPageBuilderModule.base', 'Engagement'),
                    'space' => Yii::t('ThiscoveryPageBuilderModule.base', 'Space widgets'),
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
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Clear') ?>
                </button>
                </div>
            </aside>

            <div class="ep-studio__canvas" data-ep-canvas>
                <div class="ep-layout-bar">
                    <label class="ep-layout-bar__label" for="ep-layout-select">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Columns') ?>
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
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page width') ?>
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
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Choose columns for the canvas, and the public page width for this client.') ?>
                    </p>
                </div>

                <div class="ep-edit-stage d-none" data-ep-edit-stage aria-hidden="true">
                    <div class="ep-edit-stage__bar">
                        <strong><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Edit section') ?></strong>
                        <button type="button" class="btn btn-sm btn-primary" data-ep-edit-done>
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Done') ?>
                        </button>
                    </div>
                    <div class="ep-edit-stage__body" data-ep-edit-stage-body></div>
                </div>

                <div class="ep-canvas-empty<?= !empty($sections) ? ' d-none' : '' ?>" data-ep-empty>
                    <div class="ep-canvas-empty__inner">
                        <i class="fa fa-hand-pointer-o" aria-hidden="true"></i>
                        <p><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Drag a section or container into a column.') ?></p>
                        <span><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Or click a palette item to add it to the main column.') ?></span>
                    </div>
                </div>

                <div class="ep-builder-board" data-ep-board>
                    <?= $this->render('_region', [
                        'region' => BlockRegistry::REGION_FULL,
                        'label' => $regionLabels[BlockRegistry::REGION_FULL],
                        'items' => $grouped[BlockRegistry::REGION_FULL] ?? [],
                        'formOptions' => $formOptions,
                        'pollOptions' => $pollOptions ?? [],
                        'mapOptions' => $mapOptions ?? [],
                        'pageOptions' => $pageOptions,
                        'spaceOptions' => $spaceOptions,
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
                        'mapOptions' => $mapOptions ?? [],
                        'pageOptions' => $pageOptions,
                        'spaceOptions' => $spaceOptions,
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
                        'mapOptions' => $mapOptions ?? [],
                        'pageOptions' => $pageOptions,
                        'spaceOptions' => $spaceOptions,
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
                        'mapOptions' => $mapOptions ?? [],
                        'pageOptions' => $pageOptions,
                        'spaceOptions' => $spaceOptions,
                            'blockLabels' => $blockLabels,
                            'hidden' => !in_array($layout, [BlockRegistry::LAYOUT_RIGHT, BlockRegistry::LAYOUT_BOTH], true),
                            'page' => $page,
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ep-studio__panel <?= $activeTab === 'settings' ? 'is-active' : '' ?>" data-ep-panel="settings">
        <?= $this->render('_studio_settings', [
            'contentContainer' => $contentContainer,
            'page' => $page,
            'isDirectory' => $isDirectory,
            'isTemplate' => $isTemplate,
            'collectionOptions' => $collectionOptions,
            'spaceOptions' => $spaceOptions,
            'groupOptions' => $groupOptions,
            'pageHomes' => $pageHomes,
            'publicPrefix' => $publicPrefix,
            'parentSlug' => $parentSlug,
        ]) ?>
    </div>

    <div class="ep-studio__panel <?= $activeTab === 'share' ? 'is-active' : '' ?>" data-ep-panel="share">
        <div class="ep-studio__settings">
            <h5 class="ep-section__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Public URL') ?></h5>
            <?php if ($isNew): ?>
                <p class="ep-hint text-muted">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Save the page first to generate a shareable link.') ?>
                </p>
            <?php else: ?>
                <div class="form-group">
                    <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Public link') ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" readonly value="<?= Html::encode($shareUrl) ?>" data-ep-share-url>
                        <button type="button" class="btn btn-primary" data-ep-copy-url>
                            <i class="fa fa-clipboard"></i>
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Copy link') ?>
                        </button>
                    </div>
                    <div class="ep-copy-feedback text-success d-none" data-ep-copy-feedback>
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Copied!') ?>
                    </div>
                </div>
                <p>
                    <a href="<?= Html::encode(Url::toPublic($page)) ?>" target="_blank" rel="noopener">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Open public page') ?>
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
            'mapOptions' => $mapOptions ?? [],
            'pageOptions' => $pageOptions,
            'spaceOptions' => $spaceOptions,
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
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Container'),
                'show_title' => true,
                'columns' => 2,
            ],
            'phases' => ['title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Project phases'), 'style' => 'linear', 'items' => [['label' => '', 'description' => '', 'status' => 'upcoming']]],
            'events' => ['title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Upcoming events'), 'items' => [['title' => '', 'date' => '', 'time' => '', 'location' => '', 'url' => '', 'cta_label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Register')]]],
            'team' => ['title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Meet the team'), 'people' => [['name' => '', 'role' => '', 'email' => '', 'phone' => '', 'bio' => '']]],
            'contact' => ['title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Contact us'), 'show_email_link' => true],
            'updates' => [],
            'comments' => [
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Comments'),
                'allow_guests' => true,
                'ask_name' => true,
                'require_email' => true,
                'moderate_guests' => true,
                'show_comments' => true,
            ],
            'accordion' => ['title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Frequently asked questions'), 'items' => [['heading' => '', 'body' => '']]],
            'callout' => ['tone' => 'info'],
            'image' => [],
            'directory' => [
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open for feedback'),
                'source' => 'pages',
                'show_featured_first' => true,
                'event_limit' => 5,
            ],
'collection' => [
                'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open for feedback'),
                'source' => 'pages',
                'show_featured_first' => true,
                'event_limit' => 5,
            ],
            'button' => [
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Learn more'),
                'action' => 'link',
                'tone' => 'primary',
            ],
            'map_embed' => [
                'height' => 480,
            ],
            'space_stream', 'space_tasks', 'space_files', 'space_gallery', 'space_calendar' => [
                'title' => '',
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
                'mapOptions' => $mapOptions ?? [],
                'pageOptions' => $pageOptions,
                'spaceOptions' => $spaceOptions,
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
            'item' => ['title' => '', 'date' => '', 'time' => '', 'location' => '', 'url' => '', 'cta_label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Register')],
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
        <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Cancel'))
            ->link($backUrl)
            ->loader(false) ?>
        <div class="ep-studio__footer-actions">
            <?php if (!$isNew && !$isDirectory && !$isTemplate): ?>
                <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#ep-save-template-modal">
                    <i class="fa fa-files-o"></i>
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Save as template') ?>
                </button>
            <?php endif; ?>
            <button type="submit" name="after_save" value="preview" class="btn btn-primary">
                <i class="fa fa-eye" aria-hidden="true"></i>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Preview') ?>
            </button>
            <?= Button::save($saveLabel)->submit()->icon('floppy-o')->loader(false) ?>
        </div>
    </div>

    <?= Html::endForm() ?>

    <?php if (!$isNew && !$isDirectory && !$isTemplate): ?>
        <div class="modal fade" id="ep-save-template-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <?= Html::beginForm(Url::toSaveTemplate($page), 'post') ?>
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Save as template') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Creates a reusable copy of this page’s layout, width, and sections. File uploads stay shared by GUID.') ?>
                        </p>
                        <div class="form-group mb-0">
                            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Template name') ?></label>
                            <input type="text" class="form-control" name="template_title"
                                   value="<?= Html::encode($page->title) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Cancel') ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Save template') ?>
                        </button>
                    </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
