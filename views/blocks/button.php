<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\ButtonBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var ButtonBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$label = (string) ($settings['label'] ?? '');
if ($label === '') {
    return;
}
$link = $block->resolveLink($page);
$tone = $settings['tone'] ?? ButtonBlock::TONE_PRIMARY;
$icon = trim((string) ($settings['icon'] ?? ''));
$aOpts = [
    'class' => 'btn ep-btn ep-btn--' . $tone,
];
if (!empty($link['target'])) {
    $aOpts['target'] = $link['target'];
    $aOpts['rel'] = 'noopener noreferrer';
}
foreach ($link['attrs'] as $k => $v) {
    $aOpts[$k] = $v;
}
$content = ($icon !== '' ? '<i class="fa ' . Html::encode($icon) . '" aria-hidden="true"></i> ' : '')
    . Html::encode($label);
?>
<div class="ep-block ep-button-block">
    <?= Html::a($content, $link['href'], $aOpts) ?>
</div>
