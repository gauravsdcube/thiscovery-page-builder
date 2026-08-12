<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\blocks\ContainerBlock;
use humhub\modules\engagementPages\models\EngagementPage;

/** @var ContainerBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */
/** @var array $children */

$title = $settings['title'] ?? '';
$showTitle = !empty($settings['show_title']);
$columns = ContainerBlock::clampColumns($settings['columns'] ?? 1);
$byColumn = $block->childrenByColumn();
?>
<section class="ep-block ep-container ep-container--cols-<?= (int) $columns ?>" data-ep-cols="<?= (int) $columns ?>">
    <?php if ($showTitle && $title !== ''): ?>
        <h2 class="ep-container__title"><?= Html::encode($title) ?></h2>
    <?php endif; ?>
    <div class="ep-container__body ep-container__body--cols-<?= (int) $columns ?>">
        <?php foreach ($byColumn as $colChildren): ?>
            <div class="ep-container__col">
                <?php foreach ($colChildren as $child): ?>
                    <?php
                    /** @var \humhub\modules\engagementPages\blocks\BaseBlock|null $childBlock */
                    $childBlock = $child['block'] ?? null;
                    if ($childBlock) {
                        echo $childBlock->render($page);
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
