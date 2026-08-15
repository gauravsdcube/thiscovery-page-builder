<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\RichTextBlock;
use humhub\modules\thiscoveryPageBuilder\helpers\RichHtml;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var RichTextBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */
?>
<section class="ep-block ep-rich-text">
    <?php if ($settings['title'] !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <?php if ($settings['body'] !== ''): ?>
        <div class="ep-rich-text-body richtext-output">
            <?= RichHtml::toHtml($settings['body']) ?>
        </div>
    <?php endif; ?>
</section>
