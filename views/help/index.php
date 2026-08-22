<?php

use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/** @var $contentContainer */
/** @var array $sections */
/** @var array $pages */

ThiscoveryPageBuilderAsset::register($this);
?>

<div class="ep-help-page">
    <div class="ep-help-header">
        <div>
            <div class="ep-help-kicker"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder') ?></div>
            <h1 class="ep-help-title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Help') ?></h1>
            <p class="ep-help-sub">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Guides for administrators and page creators. People viewing a public page do not see these pages.') ?>
            </p>
        </div>
        <div class="ep-help-header__actions">
            <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Back to pages'))
                ->link(Url::toIndex($contentContainer))
                ->icon('arrow-left')
                ->loader(false) ?>
        </div>
    </div>

    <?php foreach ($sections as $section): ?>
        <section class="ep-help-section">
            <h2 class="ep-help-section__title"><?= Html::encode($section['title']) ?></h2>
            <p class="ep-help-section__intro"><?= Html::encode($section['intro']) ?></p>
            <div class="ep-help-cards">
                <?php foreach ($section['pages'] as $slug): ?>
                    <?php $meta = $pages[$slug] ?? null; ?>
                    <?php if (!$meta) { continue; } ?>
                    <a class="ep-help-card" href="<?= Html::encode(Url::toHelp($contentContainer, $slug)) ?>">
                        <span class="ep-help-card__icon"><i class="fa fa-<?= Html::encode($meta['icon']) ?>" aria-hidden="true"></i></span>
                        <span class="ep-help-card__body">
                            <span class="ep-help-card__title"><?= Html::encode($meta['title']) ?></span>
                            <span class="ep-help-card__summary"><?= Html::encode($meta['summary']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
