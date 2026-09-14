<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\thiscoveryPageBuilder\helpers\Url as PageUrl;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;
use humhub\widgets\bootstrap\Button;

/** @var ContentContainerActiveRecord|null $contentContainer */
/** @var EngagementPage $page */
/** @var bool $canManage */
/** @var bool $publicLayout */
/** @var bool $isDirectory */
/** @var bool $isPreview */

$this->title = $page->title;
$layout = $page->getLayoutKey();
$grouped = BlockRegistry::groupByRegion($page->getSections());
$isDirectory = $isDirectory ?? $page->isDirectoryHome();
$isPreview = !empty($isPreview);
$customCss = method_exists($page, 'getSafeCustomCss') ? $page->getSafeCustomCss() : '';

$renderRegionSections = static function (array $sections) use ($page): string {
    $html = '';
    foreach ($sections as $section) {
        $block = $page->hydrateBlock($section);
        if ($block !== null) {
            $html .= $block->render($page);
        }
    }
    return $html;
};

$showLeft = in_array($layout, [BlockRegistry::LAYOUT_LEFT, BlockRegistry::LAYOUT_BOTH], true);
$showRight = in_array($layout, [BlockRegistry::LAYOUT_RIGHT, BlockRegistry::LAYOUT_BOTH], true);
?>

<?php if ($customCss !== ''): ?>
    <style id="ep-page-css"><?= $customCss ?></style>
<?php endif; ?>

<article id="ep-page" class="engagement-page<?= $publicLayout ? ' engagement-page--public' : '' ?> engagement-page--layout-<?= Html::encode($layout) ?><?= $isDirectory ? ' engagement-page--directory' : '' ?>"
     data-ep-width="<?= Html::encode($page->getPageWidthKey()) ?>">
    <?php if ($isPreview): ?>
        <div class="alert alert-info ep-preview-banner" role="status">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Preview: visitors still see the last published edition until you publish this draft.') ?>
        </div>
    <?php endif; ?>
    <?php if ($canManage): ?>
        <div class="engagement-page-toolbar">
            <?= Button::defaultType(Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder'))
                ->link(PageUrl::toIndex($contentContainer))
                ->sm() ?>
            <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', $isDirectory ? 'Edit homepage' : 'Edit page'))
                ->link(PageUrl::toEdit($page))
                ->sm() ?>
            <?php if (!$isDirectory && $page->isGlobal()): ?>
                <?= Button::defaultType(Yii::t('ThiscoveryPageBuilderModule.base', 'Moderate comments'))
                    ->link(PageUrl::toGlobalComments('all', (int) $page->id))
                    ->sm() ?>
                <?= Button::defaultType(Yii::t('ThiscoveryPageBuilderModule.base', 'Subscriptions'))
                    ->link(PageUrl::toGlobalSubscriptions((int) $page->id))
                    ->sm() ?>
            <?php endif; ?>
            <?php if (!$isDirectory): ?>
                <?= Html::beginForm(PageUrl::toDelete($page), 'post', ['class' => 'ep-inline-form']) ?>
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <?= Button::danger(Yii::t('ThiscoveryPageBuilderModule.base', 'Delete'))
                        ->submit()
                        ->sm()
                        ->confirm(Yii::t('ThiscoveryPageBuilderModule.base', 'Are you sure you want to delete this engagement page?')) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
            <span class="ep-public-link">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Public link') ?>:
                <a href="<?= Html::encode(PageUrl::toPublic($page)) ?>">
                    <?= Html::encode($page->getPublicPath()) ?>
                </a>
            </span>
        </div>
    <?php endif; ?>

    <div class="engagement-page-body">
        <?php if (!empty($grouped[BlockRegistry::REGION_FULL])): ?>
            <div class="ep-page-full">
                <?= $renderRegionSections($grouped[BlockRegistry::REGION_FULL]) ?>
            </div>
        <?php endif; ?>

        <div class="ep-page-columns ep-page-columns--<?= Html::encode($layout) ?>">
            <?php if ($showLeft): ?>
                <aside class="ep-page-col ep-page-col--left">
                    <?= $renderRegionSections($grouped[BlockRegistry::REGION_LEFT] ?? []) ?>
                </aside>
            <?php endif; ?>

            <div class="ep-page-col ep-page-col--main">
                <?= $renderRegionSections($grouped[BlockRegistry::REGION_MAIN] ?? []) ?>
            </div>

            <?php if ($showRight): ?>
                <aside class="ep-page-col ep-page-col--right">
                    <?= $renderRegionSections($grouped[BlockRegistry::REGION_RIGHT] ?? []) ?>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</article>
