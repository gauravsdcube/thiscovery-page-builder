<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\CustomHtmlBlock;
use humhub\modules\thiscoveryPageBuilder\helpers\CustomHtml;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var CustomHtmlBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$title = trim((string) ($settings['title'] ?? ''));
$html = CustomHtml::forPublic((string) ($settings['html'] ?? ''));
if ($html === '') {
    return;
}
?>
<section class="ep-block ep-custom-html">
    <?php if ($title !== ''): ?>
        <h2 class="ep-custom-html__title"><?= Html::encode($title) ?></h2>
    <?php endif; ?>
    <div class="ep-custom-html__body">
        <?= $html ?>
    </div>
</section>
