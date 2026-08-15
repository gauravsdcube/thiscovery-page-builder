<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\PhasesBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var PhasesBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$items = $settings['items'] ?? [];
if ($items === []) {
    return;
}
$style = ($settings['style'] ?? 'linear') === 'plan' ? 'plan' : 'linear';
$statusLabel = static function (string $status): string {
    return match ($status) {
        'done' => Yii::t('ThiscoveryPageBuilderModule.base', 'Completed'),
        'current' => Yii::t('ThiscoveryPageBuilderModule.base', 'Current'),
        default => Yii::t('ThiscoveryPageBuilderModule.base', 'Upcoming'),
    };
};
?>
<section class="ep-block ep-phases ep-phases--<?= Html::encode($style) ?>">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>

    <?php if ($style === 'plan'): ?>
        <ol class="ep-phases-plan" style="--ep-phase-count: <?= (int) count($items) ?>">
            <?php foreach ($items as $i => $item): ?>
                <li class="ep-phases-plan__step ep-phases-plan__step--<?= Html::encode($item['status']) ?>">
                    <div class="ep-phases-plan__rail" aria-hidden="true">
                        <span class="ep-phases-plan__num"><?= (int) ($i + 1) ?></span>
                    </div>
                    <div class="ep-phases-plan__card">
                        <div class="ep-phases-plan__label"><?= Html::encode($item['label']) ?></div>
                        <?php if (($item['description'] ?? '') !== ''): ?>
                            <div class="ep-phases-plan__desc"><?= Html::encode($item['description']) ?></div>
                        <?php endif; ?>
                        <span class="ep-phases-plan__status"><?= Html::encode($statusLabel($item['status'])) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <ol class="ep-phases__list">
            <?php foreach ($items as $item): ?>
                <li class="ep-phases__item ep-phases__item--<?= Html::encode($item['status']) ?>">
                    <div class="ep-phases__marker" aria-hidden="true"></div>
                    <div class="ep-phases__content">
                        <div class="ep-phases__label"><?= Html::encode($item['label']) ?></div>
                        <?php if (($item['description'] ?? '') !== ''): ?>
                            <div class="ep-phases__desc"><?= Html::encode($item['description']) ?></div>
                        <?php endif; ?>
                        <span class="ep-phases__status"><?= Html::encode($statusLabel($item['status'])) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
