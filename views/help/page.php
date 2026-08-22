<?php

use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/** @var array $article */
/** @var $contentContainer */
/** @var array $sections */
/** @var array $pages */

ThiscoveryPageBuilderAsset::register($this);
$current = $article['slug'];
?>

<div class="ep-help-page ep-help-article">
    <div class="ep-help-header">
        <div>
            <div class="ep-help-kicker"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Help') ?></div>
            <h1 class="ep-help-title"><?= Html::encode($article['title']) ?></h1>
        </div>
        <div class="ep-help-header__actions">
            <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'All help'))
                ->link(Url::toHelp($contentContainer))
                ->icon('book')
                ->loader(false) ?>
            <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Back to pages'))
                ->link(Url::toIndex($contentContainer))
                ->icon('arrow-left')
                ->loader(false) ?>
        </div>
    </div>

    <div class="ep-help-layout">
        <nav class="ep-help-nav" aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Help')) ?>">
            <?php foreach ($sections as $section): ?>
                <div class="ep-help-nav__group"><?= Html::encode($section['title']) ?></div>
                <?php foreach ($section['pages'] as $slug): ?>
                    <?php $meta = $pages[$slug] ?? null; ?>
                    <?php if (!$meta) { continue; } ?>
                    <a class="ep-help-nav__link<?= $slug === $current ? ' is-active' : '' ?>"
                       href="<?= Html::encode(Url::toHelp($contentContainer, $slug)) ?>">
                        <?= Html::encode($meta['title']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <article class="ep-help-body">
            <?= $article['html'] ?>
        </article>
    </div>
</div>
