<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var EngagementPage[] $pages */

$this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Engagements');
?>
<div class="ep-directory">
    <header class="ep-directory__hero">
        <h1><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Shape local health services') ?></h1>
        <p><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Browse open consultations and surveys. Tell us what matters to you.') ?></p>
    </header>

    <?php if ($pages === []): ?>
        <p class="text-muted"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'No open engagements are listed yet.') ?></p>
    <?php else: ?>
        <?php
        $featured = array_values(array_filter($pages, static fn(EngagementPage $p) => (bool) $p->featured));
        $rest = array_values(array_filter($pages, static fn(EngagementPage $p) => !(bool) $p->featured));
        ?>

        <?php if ($featured !== []): ?>
            <h2 class="ep-directory__heading"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Open for feedback') ?></h2>
            <div class="ep-directory__grid">
                <?php foreach ($featured as $page): ?>
                    <?= $this->render('_directory_card', ['page' => $page, 'featured' => true]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($rest !== []): ?>
            <h2 class="ep-directory__heading"><?= Yii::t('ThiscoveryPageBuilderModule.base', $featured ? 'More engagements' : 'Open for feedback') ?></h2>
            <div class="ep-directory__grid">
                <?php foreach ($rest as $page): ?>
                    <?= $this->render('_directory_card', ['page' => $page, 'featured' => false]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
