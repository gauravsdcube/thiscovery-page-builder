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
use humhub\widgets\bootstrap\Button;
use yii\web\View;

/** @var ContentContainerActiveRecord|null $contentContainer */
/** @var EngagementPage[] $pages */
/** @var EngagementPage[] $templates */
/** @var bool $canCreate */
/** @var bool $canViewHelp */
/** @var int $pendingComments */
/** @var int $subscriptionCount */

ThiscoveryPageBuilderAsset::register($this);

$templates = $templates ?? [];
$pages = $pages ?? [];
$canCreate = !empty($canCreate);
$canViewHelp = !empty($canViewHelp);
$isNetwork = $contentContainer === null;
$pendingComments = (int) ($pendingComments ?? 0);
$subscriptionCount = (int) ($subscriptionCount ?? 0);
$statusOptions = EngagementPage::statusOptions();
$filters = [
    'q' => trim((string) Yii::$app->request->get('q', '')),
    'status' => (string) Yii::$app->request->get('status', ''),
    'collection' => (int) Yii::$app->request->get('collection', 0),
];
$searching = $filters['q'] !== '';
$hasFilters = $searching || $filters['status'] !== '';

$collections = [];
$standalone = [];
$byParent = [];
$byId = [];
foreach ($pages as $page) {
    $byId[(int) $page->id] = $page;
    if ($page->isCollection()) {
        $collections[] = $page;
    } elseif ($page->isTopLevel()) {
        $standalone[] = $page;
    } else {
        $byParent[(int) $page->parent_id][] = $page;
    }
}

$currentCollection = null;
if ($filters['collection'] > 0 && isset($byId[$filters['collection']]) && $byId[$filters['collection']]->isCollection()) {
    $currentCollection = $byId[$filters['collection']];
}

$matches = static function (EngagementPage $page) use ($filters): bool {
    if ($filters['status'] !== '' && (string) $page->status !== (string) $filters['status']) {
        return false;
    }
    if ($filters['q'] !== '') {
        $haystack = mb_strtolower(
            $page->title . ' ' . $page->slug . ' ' . $page->getPublicPath() . ' ' . (string) $page->summary
        );
        if (!str_contains($haystack, mb_strtolower($filters['q']))) {
            return false;
        }
    }
    return true;
};

if ($searching && !$currentCollection) {
    $tablePages = array_values(array_filter($pages, static function (EngagementPage $page) use ($matches) {
        return !$page->isCollection() && $matches($page);
    }));
    $visibleCollections = [];
} elseif ($currentCollection) {
    $tablePages = array_values(array_filter($byParent[(int) $currentCollection->id] ?? [], $matches));
    $visibleCollections = [];
} else {
    $tablePages = array_values(array_filter($standalone, $matches));
    $visibleCollections = $searching ? [] : $collections;
}

$indexParams = $currentCollection ? ['collection' => (int) $currentCollection->id] : [];
$indexUrl = Url::toIndex($contentContainer);
$clearUrl = Url::toIndex($contentContainer, $indexParams);
$createPageUrl = Url::toCreate($contentContainer, null, $currentCollection ? (int) $currentCollection->id : null);
$shown = count($tablePages);
$nothingYet = !$currentCollection && $collections === [] && $standalone === [] && !$hasFilters;

$this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Pages');
?>

<div class="ep-list-page">
    <div class="ep-list-header">
        <div>
            <h1 class="ep-list-title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Pages') ?></h1>
            <p class="ep-list-sub">
                <?= $isNetwork
                    ? Yii::t('ThiscoveryPageBuilderModule.base', 'Browse, open, and manage network-level public pages.')
                    : Yii::t('ThiscoveryPageBuilderModule.base', 'Browse, open, and manage pages in one place.') ?>
            </p>
        </div>
        <div class="ep-list-header__actions">
            <?php if ($isNetwork): ?>
                <?= Button::light(
                    Yii::t('ThiscoveryPageBuilderModule.base', 'Comments')
                    . ($pendingComments > 0 ? ' (' . $pendingComments . ')' : '')
                )
                    ->link(Url::toGlobalComments())
                    ->icon('comments')
                    ->loader(false) ?>
                <?= Button::light(
                    Yii::t('ThiscoveryPageBuilderModule.base', 'Subscriptions')
                    . ($subscriptionCount > 0 ? ' (' . $subscriptionCount . ')' : '')
                )
                    ->link(Url::toGlobalSubscriptions())
                    ->icon('envelope')
                    ->loader(false) ?>
            <?php endif; ?>
            <?php if ($canViewHelp): ?>
                <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Help'))
                    ->link(Url::toHelp($contentContainer))
                    ->icon('question-circle')
                    ->loader(false) ?>
            <?php endif; ?>
            <?php if ($canCreate && !$currentCollection): ?>
                <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Create collection'))
                    ->link(Url::toCreate($contentContainer, null, null, true))
                    ->icon('folder-o')
                    ->loader(false) ?>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Create page'))
                    ->link($createPageUrl)
                    ->icon('plus')
                    ->loader(false) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($currentCollection): ?>
        <nav class="ep-folder-crumbs" aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Collections')) ?>">
            <a href="<?= Html::encode(Url::toIndex($contentContainer)) ?>"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'All pages') ?></a>
            <span class="ep-folder-crumbs__sep">/</span>
            <span><?= Html::encode($currentCollection->title) ?></span>
        </nav>
        <?php if ($currentCollection->canManage() || $canCreate): ?>
            <div class="ep-folder-toolbar">
                <?php if ($currentCollection->canManage()): ?>
                    <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Edit collection'))
                        ->link(Url::toEdit($currentCollection))
                        ->sm()
                        ->icon('pencil')
                        ->loader(false) ?>
                <?php endif; ?>
                <?php if ($canCreate): ?>
                    <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Add page'))
                        ->link($createPageUrl)
                        ->sm()
                        ->icon('plus')
                        ->loader(false) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($nothingYet): ?>
        <div class="ep-list-empty">
            <i class="fa fa-th-large"></i>
            <h3><?= Yii::t('ThiscoveryPageBuilderModule.base', 'No pages yet.') ?></h3>
            <?php if ($canCreate): ?>
                <p><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Create a collection to group pages, or create a standalone page.') ?></p>
                <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Create page'))
                    ->link($createPageUrl)
                    ->loader(false) ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if (!$searching && $visibleCollections): ?>
            <div class="ep-folder-grid">
                <?php foreach ($visibleCollections as $collection): ?>
                    <?php $childCount = count($byParent[(int) $collection->id] ?? []); ?>
                    <a class="ep-folder-card" href="<?= Html::encode(Url::toIndex($contentContainer, ['collection' => (int) $collection->id])) ?>">
                        <span class="ep-folder-card__icon"><i class="fa fa-folder"></i></span>
                        <span class="ep-folder-card__body">
                            <span class="ep-folder-card__name"><?= Html::encode($collection->title) ?></span>
                            <span class="ep-folder-card__meta">
                                <?= Html::encode($collection->getPublicPath()) ?>
                                ·
                                <?= Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=0{No pages}=1{1 page} other{# pages}}', ['n' => $childCount]) ?>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="get" action="<?= Html::encode($indexUrl) ?>" class="ep-list-filters" data-pjax-prevent>
            <select name="collection" class="ep-folder-jump" data-ep-auto-submit aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Collection')) ?>">
                <option value=""><?= Yii::t('ThiscoveryPageBuilderModule.base', 'All pages') ?></option>
                <?php foreach ($collections as $collection): ?>
                    <option value="<?= (int) $collection->id ?>"<?= $currentCollection && (int) $currentCollection->id === (int) $collection->id ? ' selected' : '' ?>>
                        <?= Html::encode($collection->title) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="ep-list-filters__search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" name="q" value="<?= Html::encode($filters['q']) ?>"
                       placeholder="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Search pages')) ?>"
                       aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Search pages')) ?>">
            </div>
            <select name="status" aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Status')) ?>" data-ep-auto-submit>
                <option value=""><?= Yii::t('ThiscoveryPageBuilderModule.base', 'All statuses') ?></option>
                <?php foreach ($statusOptions as $status => $label): ?>
                    <option value="<?= (int) $status ?>"<?= (string) $filters['status'] === (string) $status ? ' selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-default btn-sm"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Search') ?></button>
            <?php if ($hasFilters): ?>
                <a class="btn btn-link btn-sm" href="<?= Html::encode($clearUrl) ?>"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Clear') ?></a>
            <?php endif; ?>
        </form>

        <?php if ($shown === 0): ?>
            <div class="ep-list-empty">
                <i class="fa <?= $hasFilters ? 'fa-search' : 'fa-file-o' ?>"></i>
                <h3><?= $hasFilters
                    ? Yii::t('ThiscoveryPageBuilderModule.base', 'No matching pages.')
                    : ($currentCollection
                        ? Yii::t('ThiscoveryPageBuilderModule.base', 'No pages in this collection.')
                        : Yii::t('ThiscoveryPageBuilderModule.base', 'No standalone pages.')) ?></h3>
                <p><?= $hasFilters
                    ? Yii::t('ThiscoveryPageBuilderModule.base', 'Try a different search or clear the filters.')
                    : Yii::t('ThiscoveryPageBuilderModule.base', 'Open a collection above, or create a page here.') ?></p>
            </div>
        <?php else: ?>
            <div class="ep-page-table-wrap">
                <table class="ep-page-table">
                    <thead>
                    <tr>
                        <th class="ep-page-table__actions-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Actions') ?></th>
                        <th class="ep-page-table__status-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Status') ?></th>
                        <th class="ep-page-table__page-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page') ?></th>
                        <th class="ep-page-table__type-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Type') ?></th>
                        <th class="ep-page-table__url-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'URL') ?></th>
                        <th class="ep-page-table__num-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Sections') ?></th>
                        <th class="ep-page-table__date-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Date created') ?></th>
                        <th class="ep-page-table__date-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Date modified') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tablePages as $page): ?>
                        <?= $this->render('@thiscovery-page-builder/views/page/_page_row', [
                            'page' => $page,
                            'depth' => 0,
                            'childCount' => 0,
                            'statusOptions' => $statusOptions,
                            'canCreate' => $canCreate,
                            'listContainer' => $contentContainer,
                        ]) ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ep-list-pager">
                <div class="ep-list-pager__count">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=1{1 page} other{# pages}}', ['n' => $shown]) ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($templates) && !$currentCollection): ?>
        <h2 class="ep-list-section-title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Templates') ?></h2>
        <div class="ep-page-table-wrap">
            <table class="ep-page-table">
                <thead>
                <tr>
                    <th class="ep-page-table__actions-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Actions') ?></th>
                    <th class="ep-page-table__status-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Status') ?></th>
                    <th class="ep-page-table__page-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page') ?></th>
                    <th class="ep-page-table__type-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Type') ?></th>
                    <th class="ep-page-table__url-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'URL') ?></th>
                    <th class="ep-page-table__num-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Sections') ?></th>
                    <th class="ep-page-table__date-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Date created') ?></th>
                    <th class="ep-page-table__date-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Date modified') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($templates as $template): ?>
                    <?= $this->render('@thiscovery-page-builder/views/page/_page_row', [
                        'page' => $template,
                        'depth' => 0,
                        'childCount' => 0,
                        'statusOptions' => $statusOptions,
                        'canCreate' => $canCreate,
                        'listContainer' => $contentContainer,
                    ]) ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="ep-list-pager">
            <div class="ep-list-pager__count">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=1{1 template} other{# templates}}', ['n' => count($templates)]) ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
$this->registerJs('humhub.require("thiscoveryPageBuilder").initListPages();', View::POS_READY);
