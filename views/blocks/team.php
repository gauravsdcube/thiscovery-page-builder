<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\TeamBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var TeamBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$people = $settings['people'] ?? [];
?>
<section class="ep-block ep-team">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <?php if (($settings['intro'] ?? '') !== ''): ?>
        <p class="ep-team__intro"><?= Html::encode($settings['intro']) ?></p>
    <?php endif; ?>
    <?php if ($people !== []): ?>
        <ul class="ep-team__list">
            <?php foreach ($people as $person): ?>
                <li class="ep-team__person">
                    <strong class="ep-team__name"><?= Html::encode($person['name']) ?></strong>
                    <?php if (($person['role'] ?? '') !== ''): ?>
                        <div class="ep-team__role"><?= Html::encode($person['role']) ?></div>
                    <?php endif; ?>
                    <?php if (($person['email'] ?? '') !== ''): ?>
                        <div class="ep-team__email">
                            <a href="mailto:<?= Html::encode($person['email']) ?>"><?= Html::encode($person['email']) ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if (($person['phone'] ?? '') !== ''): ?>
                        <div class="ep-team__phone"><?= Html::encode($person['phone']) ?></div>
                    <?php endif; ?>
                    <?php if (($person['bio'] ?? '') !== ''): ?>
                        <p class="ep-team__bio"><?= Html::encode($person['bio']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
