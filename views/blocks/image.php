<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\blocks\ImageBlock;
use humhub\modules\engagementPages\helpers\FileHelper;
use humhub\modules\engagementPages\models\EngagementPage;

/** @var ImageBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$url = FileHelper::url($settings['image_guid'] ?? null)
    ?: (($settings['image_url'] ?? '') !== '' ? $settings['image_url'] : null);
if (!$url) {
    return;
}
$alt = ($settings['alt'] ?? '') !== '' ? $settings['alt'] : ($settings['caption'] ?? 'Image');
$img = Html::img($url, [
    'alt' => $alt,
    'class' => 'ep-image__img',
    'loading' => 'lazy',
]);
if (($settings['link_url'] ?? '') !== '') {
    $img = Html::a($img, $settings['link_url'], ['target' => '_blank', 'rel' => 'noopener']);
}
?>
<figure class="ep-block ep-image">
    <?= $img ?>
    <?php if (($settings['caption'] ?? '') !== ''): ?>
        <figcaption><?= Html::encode($settings['caption']) ?></figcaption>
    <?php endif; ?>
</figure>
