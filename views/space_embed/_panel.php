<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\space\models\Space;

/**
 * Shared chrome for space embed panels.
 *
 * @var Space $space
 * @var string $kind stream|tasks|files|gallery|calendar
 * @var string $heading
 * @var string $spaceUrl
 * @var string $ctaLabel
 * @var string $bodyHtml
 * @var bool $isEmpty
 * @var string|null $emptyText
 */

$kind = $kind ?? 'stream';
$heading = $heading ?? '';
$ctaLabel = $ctaLabel ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Open Space');
$isEmpty = !empty($isEmpty);
$emptyText = $emptyText ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Nothing to show yet.');
?>
<div class="ep-space-panel ep-space-panel--<?= Html::encode($kind) ?>">
    <header class="ep-space-panel__header">
        <div class="ep-space-panel__intro">
            <span class="ep-space-panel__eyebrow"><?= Html::encode($space->getDisplayName()) ?></span>
            <?php if ($heading !== ''): ?>
                <h4 class="ep-space-panel__heading"><?= Html::encode($heading) ?></h4>
            <?php endif; ?>
        </div>
        <a class="ep-space-panel__cta" href="<?= Html::encode($spaceUrl) ?>">
            <?= Html::encode($ctaLabel) ?>
            <i class="fa fa-arrow-right" aria-hidden="true"></i>
        </a>
    </header>
    <?php if ($isEmpty): ?>
        <p class="ep-space-panel__empty"><?= Html::encode($emptyText) ?></p>
    <?php else: ?>
        <?= $bodyHtml ?>
    <?php endif; ?>
</div>
