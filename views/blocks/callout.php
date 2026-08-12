<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\engagementPages\blocks\CalloutBlock;
use humhub\modules\engagementPages\models\EngagementPage;

/** @var CalloutBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

if (($settings['title'] ?? '') === '' && ($settings['body'] ?? '') === '') {
    return;
}
$tone = $settings['tone'] ?? 'info';
?>
<aside class="ep-block ep-callout ep-callout--<?= Html::encode($tone) ?>">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <strong class="ep-callout__title"><?= Html::encode($settings['title']) ?></strong>
    <?php endif; ?>
    <?php if (($settings['body'] ?? '') !== ''): ?>
        <div class="ep-callout__body richtext-output">
            <?= RichText::convert($settings['body'], RichText::FORMAT_HTML) ?>
        </div>
    <?php endif; ?>
</aside>
