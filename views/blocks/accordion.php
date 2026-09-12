<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\AccordionBlock;
use humhub\modules\thiscoveryPageBuilder\helpers\RichHtml;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var AccordionBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$items = $settings['items'] ?? [];
if ($items === []) {
    return;
}
$exclusive = !empty($settings['exclusive']);
$uid = 'ep-acc-' . (int) $page->id . '-' . substr(md5(json_encode($items)), 0, 8);
?>
<section class="ep-block ep-accordion"<?php if ($exclusive): ?> data-ep-exclusive="1"<?php endif; ?>>
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <div class="ep-accordion__list">
        <?php foreach ($items as $i => $item): ?>
            <?php
            $itemId = $uid . '-' . $i;
            $heading = ($item['heading'] ?? '') !== '' ? $item['heading'] : Yii::t('ThiscoveryPageBuilderModule.base', 'Details');
            $open = !$exclusive && $i === 0;
            ?>
            <details class="ep-accordion__item" <?= $open ? 'open' : '' ?>>
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
