<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/** @var string $content */

use humhub\helpers\Html;

/** @var \humhub\modules\thiscoveryPageBuilder\models\EngagementPage|null $engagementPage */
$engagementPage = $this->params['engagementPage'] ?? null;
$widthKey = $engagementPage ? $engagementPage->getPageWidthKey() : 'wide';
$maxCss = $engagementPage ? $engagementPage->getPageWidthCssMax() : 'var(--yg-container-max-width, 1600px)';
$style = $maxCss !== null
    ? 'max-width:' . $maxCss . ';width:100%;margin-left:auto;margin-right:auto;'
    : 'max-width:none;width:100%;';
?>
<div class="thiscovery-page-builder-public-shell engagement-pages-public-shell"
     data-ep-width="<?= Html::encode($widthKey) ?>"
     style="<?= Html::encode($style) ?>">
    <?= $content ?>
</div>
