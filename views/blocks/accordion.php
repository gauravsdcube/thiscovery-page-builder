<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\blocks\AccordionBlock;
use humhub\modules\engagementPages\helpers\RichHtml;
use humhub\modules\engagementPages\models\EngagementPage;

/** @var AccordionBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$items = $settings['items'] ?? [];
if ($items === []) {
    return;
}
$uid = 'ep-acc-' . (int) $page->id . '-' . substr(md5(json_encode($items)), 0, 8);
?>
<section class="ep-block ep-accordion">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <div class="ep-accordion__list">
        <?php foreach ($items as $i => $item): ?>
            <?php
            $itemId = $uid . '-' . $i;
            $heading = ($item['heading'] ?? '') !== '' ? $item['heading'] : Yii::t('EngagementPagesModule.base', 'Details');
            ?>
            <details class="ep-accordion__item" <?= $i === 0 ? 'open' : '' ?>>
                <summary class="ep-accordion__summary"><?= Html::encode($heading) ?></summary>
                <div class="ep-accordion__body richtext-output">
                    <?php if (($item['body'] ?? '') !== ''): ?>
                        <?= RichHtml::toHtml($item['body'] ?? '') ?>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>
