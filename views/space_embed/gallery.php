<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\gallery\models\Media;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryPageBuilder\helpers\SpaceWidgets;

/** @var Space $space */
/** @var Media[] $media */
/** @var string $spaceUrl */

$media = $media ?? [];
ob_start();
?>
<div class="ep-space-gallery">
    <?php foreach ($media as $item): ?>
        <?php
        $url = SpaceWidgets::mediaUrl($item);
        $href = method_exists($item, 'getUrl') ? (string) $item->getUrl() : $spaceUrl;
        $title = trim((string) ($item->description ?? $item->title ?? ''));
        if ($url === '') {
            continue;
        }
        ?>
        <a class="ep-space-gallery__item" href="<?= Html::encode($href) ?>" title="<?= Html::encode($title) ?>">
            <img src="<?= Html::encode($url) ?>" alt="<?= Html::encode($title) ?>" loading="lazy">
        </a>
    <?php endforeach; ?>
</div>
<?php
$body = ob_get_clean();
$visible = substr_count($body, 'ep-space-gallery__item') > 0;

echo $this->render('_panel', [
    'space' => $space,
    'kind' => 'gallery',
    'heading' => Yii::t('ThiscoveryPageBuilderModule.base', 'Gallery'),
    'spaceUrl' => $spaceUrl,
    'ctaLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open gallery'),
    'bodyHtml' => $body,
    'isEmpty' => !$visible,
    'emptyText' => Yii::t('ThiscoveryPageBuilderModule.base', 'No gallery images to show.'),
]);
