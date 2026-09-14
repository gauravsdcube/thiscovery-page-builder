<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\helpers\Html;

/** @var \humhub\modules\thiscoveryPageBuilder\models\EngagementPage $page */
/** @var bool $isNew */
/** @var bool $isTemplate */
/** @var \humhub\modules\content\components\ContentContainerActiveRecord|null $contentContainer */
/** @var string $activeSection */

$activeSection = $activeSection ?? 'basics';
$showBoundSpace = !$isTemplate && $page->hasAttribute('bound_space_id');
$showNavHome = !$isTemplate && $page->hasAttribute('show_in_top_menu') && $contentContainer === null;
$showVersions = !$isNew && !$isTemplate && \humhub\modules\thiscoveryPageBuilder\services\PageVersionService::isAvailable();

$navItem = static function (string $section, string $title, string $icon, string $summary) use ($activeSection) {
    $active = $section === $activeSection;
    return Html::beginTag('button', [
        'type' => 'button',
        'class' => 'ep-palette__item ep-studio-rail__item' . ($active ? ' is-active' : ''),
        'data-ep-settings-nav' => $section,
        'role' => 'tab',
        'aria-selected' => $active ? 'true' : 'false',
        'title' => $summary,
    ])
        . '<span class="ep-palette__icon" aria-hidden="true"><i class="fa ' . Html::encode($icon) . '"></i></span>'
        . '<span class="ep-studio-rail__text">'
        . '<span class="ep-palette__label">' . Html::encode($title) . '</span>'
        . '<span class="ep-studio-rail__summary">' . Html::encode($summary) . '</span>'
        . '</span>'
        . Html::endTag('button');
};
?>
<aside class="ep-studio__rail ep-settings-rail" data-ep-settings-rail role="tablist" aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Settings sections')) ?>">
    <div class="ep-palette__scroll">
        <div class="ep-palette__group">
            <div class="ep-palette__group-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page') ?></div>
            <?= $navItem('basics', Yii::t('ThiscoveryPageBuilderModule.base', 'Basics'), 'fa-file-text-o', Yii::t('ThiscoveryPageBuilderModule.base', 'Title, URL, status')) ?>
            <?php if ($showBoundSpace): ?>
                <?= $navItem('space', Yii::t('ThiscoveryPageBuilderModule.base', 'Bound Space'), 'fa-cube', Yii::t('ThiscoveryPageBuilderModule.base', 'Space used by stream, tasks, files, gallery, calendar')) ?>
            <?php endif; ?>
            <?= $navItem('directory', Yii::t('ThiscoveryPageBuilderModule.base', 'Directory listing'), 'fa-th-large', Yii::t('ThiscoveryPageBuilderModule.base', 'Listing, featured, category, closing date')) ?>
            <?php if ($showNavHome): ?>
                <?= $navItem('navigation', Yii::t('ThiscoveryPageBuilderModule.base', 'Navigation'), 'fa-bars', Yii::t('ThiscoveryPageBuilderModule.base', 'Top bar and collection menu')) ?>
                <?= $navItem('homepage', Yii::t('ThiscoveryPageBuilderModule.base', 'Site homepage'), 'fa-home', Yii::t('ThiscoveryPageBuilderModule.base', 'Guest, logged-in, and group homepage targets')) ?>
            <?php endif; ?>
        </div>

        <div class="ep-palette__group">
            <div class="ep-palette__group-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Quality & style') ?></div>
            <?= $navItem('css', Yii::t('ThiscoveryPageBuilderModule.base', 'CSS'), 'fa-paint-brush', Yii::t('ThiscoveryPageBuilderModule.base', 'Theme and custom styles')) ?>
        </div>

        <div class="ep-palette__group">
            <div class="ep-palette__group-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Publish') ?></div>
            <?= $navItem('share', Yii::t('ThiscoveryPageBuilderModule.base', 'Share'), 'fa-link', Yii::t('ThiscoveryPageBuilderModule.base', 'Links, preview, publish')) ?>
            <?php if ($showVersions): ?>
                <?= $navItem('versions', Yii::t('ThiscoveryPageBuilderModule.base', 'Versions'), 'fa-history', Yii::t('ThiscoveryPageBuilderModule.base', 'Revisions and published editions')) ?>
            <?php endif; ?>
        </div>
    </div>
</aside>
