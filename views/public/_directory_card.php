<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var EngagementPage $page */
/** @var bool $featured */

$url = Url::toPublic($page);
$imageUrl = $page->getCardImageUrl();
$blurb = $page->getDirectoryBlurb();
$featured = !empty($featured);
?>
<article class="ep-collection-card<?= $featured ? ' is-featured' : '' ?><?= $imageUrl ? ' has-media' : ' no-media' ?>">
    <a class="ep-collection-card__media" href="<?= Html::encode($url) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($imageUrl): ?>
            <img src="<?= Html::encode($imageUrl) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="ep-collection-card__media-fallback" aria-hidden="true">
                <i class="fa fa-file-text-o"></i>
            </span>
        <?php endif; ?>
        <?php if ($featured): ?>
            <span class="ep-collection-card__badge"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Featured') ?></span>
        <?php endif; ?>
    </a>
    <div class="ep-collection-card__body">
        <?php if ($page->category): ?>
            <div class="ep-collection-card__eyebrow"><?= Html::encode($page->category) ?></div>
        <?php endif; ?>
        <h3 class="ep-collection-card__title">
            <a href="<?= Html::encode($url) ?>"><?= Html::encode($page->title) ?></a>
        </h3>
        <?php if ($blurb !== ''): ?>
            <p class="ep-collection-card__summary"><?= Html::encode($blurb) ?></p>
        <?php endif; ?>
        <div class="ep-collection-card__footer">
            <span class="ep-collection-card__meta">
                <?php if ($page->closes_at): ?>
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Closes') ?>:
                    <?= Html::encode(Yii::$app->formatter->asDate($page->closes_at, 'medium')) ?>
                <?php else: ?>
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Open') ?>
                <?php endif; ?>
            </span>
            <a class="ep-collection-card__cta" href="<?= Html::encode($url) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'View') ?>
                <i class="fa fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</article>
