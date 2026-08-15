<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\EventsBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var EventsBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$items = $settings['items'] ?? [];
if ($items === []) {
    return;
}
?>
<section class="ep-block ep-events">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <ul class="ep-events__list">
        <?php foreach ($items as $item): ?>
            <li class="ep-events__item">
                <div class="ep-events__meta">
                    <?php if (($item['date'] ?? '') !== ''): ?>
                        <time class="ep-events__date" datetime="<?= Html::encode($item['date']) ?>">
                            <?= Html::encode($item['date']) ?>
                        </time>
                    <?php endif; ?>
                    <?php if (($item['time'] ?? '') !== ''): ?>
                        <span class="ep-events__time"><?= Html::encode($item['time']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="ep-events__body">
                    <strong class="ep-events__title"><?= Html::encode($item['title']) ?></strong>
                    <?php if (($item['location'] ?? '') !== ''): ?>
                        <div class="ep-events__location"><?= Html::encode($item['location']) ?></div>
                    <?php endif; ?>
                    <?php if (($item['url'] ?? '') !== ''): ?>
                        <a class="btn btn-default btn-sm" href="<?= Html::encode($item['url']) ?>" target="_blank" rel="noopener">
                            <?= Html::encode($item['cta_label'] ?: Yii::t('ThiscoveryPageBuilderModule.base', 'Register')) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
