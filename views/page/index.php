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
use humhub\modules\thiscoveryPageBuilder\models\PageFolder;
use humhub\modules\thiscoveryPageBuilder\services\PageFolderService;
use humhub\widgets\bootstrap\Button;
use yii\web\View;

/** @var ContentContainerActiveRecord|null $contentContainer */
/** @var EngagementPage[] $pages */
/** @var EngagementPage[] $templates */
/** @var bool $canCreate */
/** @var bool $canViewHelp */
/** @var int $pendingComments */
/** @var int $subscriptionCount */
/** @var bool $foldersReady */
/** @var bool $canManageFolders */

ThiscoveryPageBuilderAsset::register($this);

$templates = $templates ?? [];
$pages = $pages ?? [];
$canCreate = !empty($canCreate);
$canViewHelp = !empty($canViewHelp);
$foldersReady = !empty($foldersReady) && PageFolderService::tablesReady();
$canManageFolders = !empty($canManageFolders) || $canCreate;
$isNetwork = $contentContainer === null;
$pendingComments = (int) ($pendingComments ?? 0);
$subscriptionCount = (int) ($subscriptionCount ?? 0);
$statusOptions = EngagementPage::statusOptions();
$viewKey = (string) Yii::$app->request->get('view', '');
$filters = [
    'q' => trim((string) Yii::$app->request->get('q', '')),
    'status' => (string) Yii::$app->request->get('status', ''),
    'collection' => (int) Yii::$app->request->get('collection', 0),
    'folder' => (int) Yii::$app->request->get('folder', 0),
];
$searching = $filters['q'] !== '';
$hasFilters = $searching || $filters['status'] !== '';
$showTemplates = $viewKey === 'templates';

$collections = [];
$standalone = [];
$byParent = [];
$byId = [];
$collectionsByFolder = [];
$standaloneByFolder = [];
foreach ($pages as $page) {
    $byId[(int) $page->id] = $page;
    $fid = (int) ($page->folder_id ?? 0);
    if ($page->isCollection()) {
        $collections[] = $page;
        $collectionsByFolder[$fid][] = $page;
    } elseif ($page->isTopLevel()) {
        $standalone[] = $page;
        $standaloneByFolder[$fid][] = $page;
    } else {
        $byParent[(int) $page->parent_id][] = $page;
    }
}

$unfiledStandalone = $standaloneByFolder[0] ?? [];
$unfiledCollections = $collectionsByFolder[0] ?? [];

$folderWalk = $foldersReady ? PageFolderService::walk($contentContainer) : [];
$currentFolder = $foldersReady ? PageFolderService::findFolder($contentContainer, $filters['folder']) : null;
$childFolders = [];
if ($currentFolder) {
    $childFolders = PageFolder::findForContainer($contentContainer)->andWhere(['parent_id' => (int) $currentFolder->id])->all();
    if ($filters['q'] !== '') {
        $q = mb_strtolower($filters['q']);
        $childFolders = array_values(array_filter($childFolders, static function (PageFolder $folder) use ($q) {
            return str_contains(mb_strtolower($folder->name . ' ' . (string) $folder->description), $q);
        }));
    }
}

$currentCollection = null;
if (!$showTemplates && $filters['collection'] > 0 && isset($byId[$filters['collection']]) && $byId[$filters['collection']]->isCollection()) {
    $currentCollection = $byId[$filters['collection']];
    if (!$currentFolder && (int) ($currentCollection->folder_id ?? 0) > 0) {
        $currentFolder = PageFolderService::findFolder($contentContainer, (int) $currentCollection->folder_id);
    }
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

$folderCollections = [];
if ($showTemplates) {
    $tablePages = array_values(array_filter($templates, $matches));
} elseif ($currentCollection) {
    $tablePages = array_values(array_filter($byParent[(int) $currentCollection->id] ?? [], $matches));
} elseif ($currentFolder) {
    $tablePages = array_values(array_filter($standaloneByFolder[(int) $currentFolder->id] ?? [], $matches));
    $folderCollections = array_values(array_filter($collectionsByFolder[(int) $currentFolder->id] ?? [], $matches));
} else {
    $tablePages = array_values(array_filter($unfiledStandalone, $matches));
}

$indexParams = [];
if ($showTemplates) {
    $indexParams['view'] = 'templates';
} elseif ($currentCollection) {
    $indexParams['collection'] = (int) $currentCollection->id;
    if ($currentFolder) {
        $indexParams['folder'] = (int) $currentFolder->id;
    } elseif ((int) ($currentCollection->folder_id ?? 0) > 0) {
        $indexParams['folder'] = (int) $currentCollection->folder_id;
    }
} elseif ($currentFolder) {
    $indexParams['folder'] = (int) $currentFolder->id;
}
$indexUrl = Url::toIndex($contentContainer);
$clearUrl = Url::toIndex($contentContainer, $indexParams);
$createFolderId = $currentFolder ? (int) $currentFolder->id : null;
$createPageUrl = Url::toCreate(
    $contentContainer,
    null,
    $currentCollection ? (int) $currentCollection->id : null,
    false,
    $currentCollection ? null : $createFolderId
);
$createCollectionUrl = Url::toCreate($contentContainer, null, null, true, $createFolderId);
$shown = count($tablePages);
$nothingYet = $collections === [] && $standalone === [] && $templates === [] && $folderWalk === [] && !$hasFilters;

$folderTitle = $showTemplates
    ? Yii::t('ThiscoveryPageBuilderModule.base', 'Templates')
    : ($currentCollection
        ? $currentCollection->title
        : ($currentFolder
            ? $currentFolder->name
            : Yii::t('ThiscoveryPageBuilderModule.base', 'Top-level pages')));

$this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Pages');

$folderLink = static function (array $params, bool $active, string $icon, string $name, int $count, int $depth = 0) use ($contentContainer) {
    $href = Url::toIndex($contentContainer, $params);
    $depthClass = $depth > 0 ? ' ep-cms-folder--nested' : '';
    return '<a class="ep-cms-folder' . $depthClass . ($active ? ' is-active' : '') . '" href="' . Html::encode($href) . '"'
        . ($depth > 0 ? ' style="--ep-depth:' . (int) $depth . '"' : '') . '>'
        . '<span class="ep-cms-folder__icon"><i class="fa ' . Html::encode($icon) . '"></i></span>'
        . '<span class="ep-cms-folder__body">'
        . '<span class="ep-cms-folder__name">' . Html::encode($name) . '</span>'
        . '<span class="ep-cms-folder__count">' . Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=0{No items}=1{1 item} other{# items}}', ['n' => $count]) . '</span>'
        . '</span></a>';
};
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
                <?php
                $canThemes = Yii::$app->user->isAdmin()
                    || Yii::$app->user->can(\humhub\modules\admin\permissions\ManageModules::class);
                ?>
                <?php if ($canThemes): ?>
                    <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Themes'))
                        ->link(Url::toThemes())
                        ->icon('paint-brush')
                        ->loader(false) ?>
                <?php endif; ?>
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
            <?php if ($canManageFolders && $foldersReady && !$showTemplates && !$currentCollection): ?>
                <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'New folder'))
                    ->link(Url::toFolderEdit($contentContainer, null, $currentFolder ? ['parent' => (int) $currentFolder->id] : []))
                    ->icon('folder')
                    ->loader(false) ?>
            <?php endif; ?>
            <?php if ($canCreate && !$currentCollection && !$showTemplates): ?>
                <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Create collection'))
                    ->link($createCollectionUrl)
                    ->icon('folder-o')
                    ->loader(false) ?>
            <?php endif; ?>
            <?php if ($canCreate && !$showTemplates): ?>
                <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Create page'))
                    ->link($createPageUrl)
                    ->icon('plus')
                    ->loader(false) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($nothingYet): ?>
        <div class="ep-list-empty">
            <i class="fa fa-th-large"></i>
            <h3><?= Yii::t('ThiscoveryPageBuilderModule.base', 'No pages yet.') ?></h3>
            <?php if ($canCreate): ?>
                <p><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Create a folder to organise pages, a collection to group public URLs, or a standalone page.') ?></p>
                <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Create page'))
                    ->link($createPageUrl)
                    ->loader(false) ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="ep-cms">
            <aside class="ep-cms-rail" aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Folders and collections')) ?>">
                <div class="ep-cms-rail__label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Pages') ?></div>
                <?= $folderLink([], !$showTemplates && !$currentCollection && !$currentFolder, 'fa-file-text-o', Yii::t('ThiscoveryPageBuilderModule.base', 'Top-level pages'), count($unfiledStandalone)) ?>

                <?php if ($folderWalk): ?>
                    <div class="ep-cms-rail__label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Folders') ?></div>
                    <?php foreach ($folderWalk as $row): ?>
                        <?php
                        /** @var PageFolder $navFolder */
                        $navFolder = $row['folder'];
                        $fid = (int) $navFolder->id;
                        $inFolder = count($standaloneByFolder[$fid] ?? []) + count($collectionsByFolder[$fid] ?? []);
                        $folderActive = !$showTemplates && !$currentCollection && $currentFolder && (int) $currentFolder->id === $fid;
                        echo $folderLink(
                            ['folder' => $fid],
                            $folderActive,
                            'fa-folder',
                            $navFolder->name,
                            $inFolder,
                            (int) $row['depth']
                        );
                        foreach ($collectionsByFolder[$fid] ?? [] as $filedCollection) {
                            $childCount = count($byParent[(int) $filedCollection->id] ?? []);
                            echo $folderLink(
                                ['folder' => $fid, 'collection' => (int) $filedCollection->id],
                                $currentCollection && (int) $currentCollection->id === (int) $filedCollection->id,
                                'fa-files-o',
                                $filedCollection->title,
                                $childCount,
                                (int) $row['depth'] + 1
                            );
                        }
                        ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($unfiledCollections): ?>
                    <div class="ep-cms-rail__label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Collections') ?></div>
                    <?php foreach ($unfiledCollections as $collection): ?>
                        <?php $childCount = count($byParent[(int) $collection->id] ?? []); ?>
                        <?= $folderLink(
                            ['collection' => (int) $collection->id],
                            $currentCollection && (int) $currentCollection->id === (int) $collection->id,
                            'fa-folder',
                            $collection->title,
                            $childCount
                        ) ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($templates): ?>
                    <div class="ep-cms-rail__label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Templates') ?></div>
                    <?= $folderLink(['view' => 'templates'], $showTemplates, 'fa-files-o', Yii::t('ThiscoveryPageBuilderModule.base', 'Templates'), count($templates)) ?>
                <?php endif; ?>
            </aside>

            <div class="ep-cms-main">
                <div class="ep-cms-main__head">
                    <div>
                        <h2 class="ep-cms-main__title"><?= Html::encode($folderTitle) ?></h2>
                        <?php if ($currentCollection): ?>
                            <p class="ep-cms-main__meta"><code><?= Html::encode($currentCollection->getPublicPath()) ?></code></p>
                        <?php elseif ($currentFolder && $currentFolder->description): ?>
                            <p class="ep-cms-main__meta"><?= Html::encode($currentFolder->description) ?></p>
                        <?php endif; ?>
                        <?php
                        $crumbs = [];
                        if ($currentFolder) {
                            $crumbs[] = [
                                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Top-level pages'),
                                'url' => Url::toIndex($contentContainer),
                            ];
                            foreach ($currentFolder->getAncestors() as $crumb) {
                                $crumbs[] = [
                                    'label' => $crumb->name,
                                    'url' => Url::toIndex($contentContainer, ['folder' => (int) $crumb->id]),
                                ];
                            }
                            if ($currentCollection) {
                                $crumbs[] = [
                                    'label' => $currentFolder->name,
                                    'url' => Url::toIndex($contentContainer, ['folder' => (int) $currentFolder->id]),
                                ];
                            }
                        }
                        ?>
                        <?php if ($crumbs): ?>
                            <nav class="ep-folder-crumbs" aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Folders')) ?>">
                                <?php foreach ($crumbs as $i => $crumb): ?>
                                    <?php if ($i): ?><span class="ep-folder-crumbs__sep">/</span><?php endif; ?>
                                    <a href="<?= Html::encode($crumb['url']) ?>"><?= Html::encode($crumb['label']) ?></a>
                                <?php endforeach; ?>
                                <span class="ep-folder-crumbs__sep">/</span>
                                <span><?= Html::encode($folderTitle) ?></span>
                            </nav>
                        <?php endif; ?>
                    </div>
                    <div class="ep-folder-toolbar">
                    <?php if ($currentFolder && $canManageFolders && !$currentCollection): ?>
                        <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Folder settings'))
                            ->link(Url::toFolderEdit($contentContainer, (int) $currentFolder->id))
                            ->sm()
                            ->icon('cog')
                            ->loader(false) ?>
                        <?= Html::beginForm(Url::toFolderDelete($contentContainer, (int) $currentFolder->id), 'post', ['class' => 'ep-folder-toolbar__delete']) ?>
                        <?= Button::danger(Yii::t('ThiscoveryPageBuilderModule.base', 'Delete folder'))
                            ->confirm(Yii::t('ThiscoveryPageBuilderModule.base', 'Delete this folder and its subfolders? Pages inside will be moved to Unfiled.'))
                            ->submit()
                            ->sm()
                            ->icon('trash')
                            ->loader(false) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                    <?php if ($currentCollection && ($currentCollection->canManage() || $canCreate)): ?>
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
                    <?php endif; ?>
                    </div>
                </div>

                <form method="get" action="<?= Html::encode($indexUrl) ?>" class="ep-list-filters" data-pjax-prevent>
                    <?php if ($showTemplates): ?>
                        <input type="hidden" name="view" value="templates">
                    <?php endif; ?>
                    <?php if ($currentFolder): ?>
                        <input type="hidden" name="folder" value="<?= (int) $currentFolder->id ?>">
                    <?php endif; ?>
                    <?php if ($currentCollection): ?>
                        <input type="hidden" name="collection" value="<?= (int) $currentCollection->id ?>">
                    <?php endif; ?>
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

                <?php
                $hasFolderCards = !$currentCollection && ($childFolders || $folderCollections);
                ?>
                <?php if ($hasFolderCards): ?>
                    <div class="ep-folder-grid">
                        <?php foreach ($childFolders as $child): ?>
                            <?php
                            $cid = (int) $child->id;
                            $itemCount = count($standaloneByFolder[$cid] ?? []) + count($collectionsByFolder[$cid] ?? []);
                            $subCount = PageFolderService::childCount($child);
                            ?>
                            <a class="ep-folder-card" href="<?= Html::encode(Url::toIndex($contentContainer, ['folder' => $cid])) ?>">
                                <span class="ep-folder-card__icon"><i class="fa fa-folder"></i></span>
                                <span>
                                    <span class="ep-folder-card__name"><?= Html::encode($child->name) ?></span>
                                    <span class="ep-folder-card__meta">
                                        <?= Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=0{No items}=1{1 item} other{# items}}', ['n' => $itemCount]) ?>
                                        <?php if ($subCount): ?>
                                            · <?= Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=1{1 subfolder} other{# subfolders}}', ['n' => $subCount]) ?>
                                        <?php endif; ?>
                                    </span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <?php foreach ($folderCollections as $filedCollection): ?>
                            <?php $childCount = count($byParent[(int) $filedCollection->id] ?? []); ?>
                            <a class="ep-folder-card" href="<?= Html::encode(Url::toIndex($contentContainer, [
                                'folder' => (int) $currentFolder->id,
                                'collection' => (int) $filedCollection->id,
                            ])) ?>">
                                <span class="ep-folder-card__icon"><i class="fa fa-files-o"></i></span>
                                <span>
                                    <span class="ep-folder-card__name"><?= Html::encode($filedCollection->title) ?></span>
                                    <span class="ep-folder-card__meta">
                                        <?= Html::encode($filedCollection->getPublicPath()) ?>
                                        · <?= Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=0{No items}=1{1 item} other{# items}}', ['n' => $childCount]) ?>
                                    </span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($shown === 0): ?>
                    <?php if (!$hasFolderCards || $hasFilters): ?>
                    <div class="ep-list-empty">
                        <i class="fa <?= $hasFilters ? 'fa-search' : 'fa-file-o' ?>"></i>
                        <h3><?= $hasFilters
                            ? Yii::t('ThiscoveryPageBuilderModule.base', 'No matching pages.')
                            : ($showTemplates
                                ? Yii::t('ThiscoveryPageBuilderModule.base', 'No templates yet.')
                                : ($currentCollection
                                    ? Yii::t('ThiscoveryPageBuilderModule.base', 'No pages in this collection.')
                                    : ($currentFolder
                                        ? Yii::t('ThiscoveryPageBuilderModule.base', 'This folder is empty.')
                                        : Yii::t('ThiscoveryPageBuilderModule.base', 'No standalone pages.')))) ?></h3>
                        <p><?= $hasFilters
                            ? Yii::t('ThiscoveryPageBuilderModule.base', 'Try a different search or clear the filters.')
                            : ($currentFolder && !$currentCollection
                                ? Yii::t('ThiscoveryPageBuilderModule.base', 'Create a subfolder, a collection, or a page here.')
                                : Yii::t('ThiscoveryPageBuilderModule.base', 'Create a page here, or open a collection on the left.')) ?></p>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="ep-page-table-wrap">
                        <table class="ep-page-table">
                            <thead>
                            <tr>
                                <th class="ep-page-table__actions-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Actions') ?></th>
                                <th class="ep-page-table__status-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Status') ?></th>
                                <th class="ep-page-table__page-col"><?= Yii::t('ThiscoveryPageBuilderModule.base', $showTemplates ? 'Template' : 'Page') ?></th>
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
                            <?= $showTemplates
                                ? Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=1{1 template} other{# templates}}', ['n' => $shown])
                                : Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=1{1 page} other{# pages}}', ['n' => $shown]) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
$this->registerJs('humhub.require("thiscoveryPageBuilder").initListPages();', View::POS_READY);
