<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\engagementPages\blocks\HeroBlock;
use humhub\modules\engagementPages\helpers\FileHelper;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\widgets\bootstrap\Button;

/** @var HeroBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$imageUrl = FileHelper::url($settings['image_guid'] ?? null)
    ?: (($settings['image_url'] ?? '') !== '' ? $settings['image_url'] : null);
$imageAlt = $settings['image_alt'] ?? '';
$hasImage = !empty($imageUrl);

$bg = $block->getBackgroundColor();
$fg = $block->getTextColor();
$border = $block->getBorderColor();
$showBorder = $block->getShowBorder();

// Apply colours on the hero element itself (not only the parent wrapper).
$style = [];
if ($bg !== '') {
    $style[] = '--ep-bg:' . $bg;
    $style[] = 'background-color:' . $bg;
}
if ($fg !== '') {
    $style[] = '--ep-fg:' . $fg;
    $style[] = 'color:' . $fg;
}
if (!$showBorder) {
    $style[] = 'border:none';
} elseif ($border !== '') {
    $style[] = 'border:2px solid ' . $border;
}

$classes = 'ep-block ep-hero';
if ($hasImage) {
    $classes .= ' has-image';
}
if (!$showBorder) {
    $classes .= ' ep-hero--no-border';
}
?>
<section class="<?= Html::encode($classes) ?>"<?php if ($style !== []): ?> style="<?= Html::encode(implode(';', $style)) ?>"<?php endif; ?>>
    <?php if ($hasImage): ?>
        <img class="ep-hero-image"
             src="<?= Html::encode($imageUrl) ?>"
             alt="<?= Html::encode($imageAlt !== '' ? $imageAlt : ($settings['headline'] !== '' ? $settings['headline'] : $page->title)) ?>">
    <?php endif; ?>
    <div class="ep-hero-body">
        <h1><?= Html::encode($settings['headline'] !== '' ? $settings['headline'] : $page->title) ?></h1>
        <?php if ($settings['subheadline'] !== ''): ?>
            <div class="ep-hero-sub richtext-output">
                <?= RichText::convert($settings['subheadline'], RichText::FORMAT_HTML) ?>
            </div>
        <?php endif; ?>
        <?php if ($settings['cta_label'] !== '' && $settings['cta_url'] !== ''): ?>
            <?= Button::light($settings['cta_label'])->link($settings['cta_url']) ?>
        <?php endif; ?>
    </div>
</section>
