<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\helpers\SpaceWidgets;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var EngagementPage $page */
/** @var array $settings */

$title = (string) ($settings['title'] ?? '');
?>
<section class="ep-block ep-space-embed">
    <?php if ($title !== ''): ?>
        <h3 class="ep-section__title"><?= Html::encode($title) ?></h3>
    <?php endif; ?>
    <?= SpaceWidgets::renderTasks($page) ?>
</section>
