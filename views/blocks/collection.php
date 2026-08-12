<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\blocks\CollectionBlock;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryForms\models\CustomForm;

/** @var CollectionBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$source = (string) ($settings['source'] ?? CollectionBlock::SOURCE_PAGES);
$title = (string) ($settings['title'] ?? '');
$emptyMessage = (string) ($settings['empty_message'] ?? Yii::t('EngagementPagesModule.base', 'Nothing to show yet.'));
$showFeaturedFirst = !empty($settings['show_featured_first']);

$renderHeading = static function (string $text) {
    if ($text === '') {
        return '';
    }
    return '<h2 class="ep-directory__heading">' . Html::encode($text) . '</h2>';
};
?>
<section class="ep-block ep-collection ep-directory-list" data-ep-collection-source="<?= Html::encode($source) ?>">
<?php if ($source === CollectionBlock::SOURCE_PAGES): ?>
    <?php
    $pages = EngagementPage::findDirectoryPages();
    $featured = [];
    $rest = $pages;
    if ($showFeaturedFirst) {
        $featured = array_values(array_filter($pages, static fn(EngagementPage $p) => (bool) $p->featured));
        $rest = array_values(array_filter($pages, static fn(EngagementPage $p) => !(bool) $p->featured));
    }
    ?>
    <?php if ($pages === []): ?>
        <?= $renderHeading($title) ?>
        <p class="text-muted"><?= Html::encode($emptyMessage) ?></p>
    <?php else: ?>
        <?php if ($featured !== []): ?>
            <?= $renderHeading($title !== '' ? $title : Yii::t('EngagementPagesModule.base', 'Open for feedback')) ?>
            <div class="ep-directory__grid">
                <?php foreach ($featured as $listedPage): ?>
                    <?= $this->render('@engagement-pages/views/public/_directory_card', [
                        'page' => $listedPage,
                        'featured' => true,
                    ]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($rest !== []): ?>
            <?= $renderHeading($featured !== []
                ? Yii::t('EngagementPagesModule.base', 'More engagements')
                : ($title !== '' ? $title : Yii::t('EngagementPagesModule.base', 'Open for feedback'))) ?>
            <div class="ep-directory__grid">
                <?php foreach ($rest as $listedPage): ?>
                    <?= $this->render('@engagement-pages/views/public/_directory_card', [
                        'page' => $listedPage,
                        'featured' => false,
                    ]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php elseif ($source === CollectionBlock::SOURCE_FORMS): ?>
    <?= $renderHeading($title !== '' ? $title : Yii::t('EngagementPagesModule.base', 'Surveys')) ?>
    <?php
    $forms = [];
    if (class_exists(CustomForm::class)) {
        try {
            $forms = CustomForm::find()
                ->joinWith('content')
                ->andWhere(['content.contentcontainer_id' => null])
                ->andWhere(['custom_form.status' => CustomForm::STATUS_OPEN])
                ->orderBy(['custom_form.title' => SORT_ASC])
                ->all();
        } catch (\Throwable $e) {
            $forms = [];
        }
    }
    ?>
    <?php if ($forms === []): ?>
        <p class="text-muted"><?= Html::encode($emptyMessage) ?></p>
    <?php else: ?>
        <div class="ep-directory__grid">
            <?php foreach ($forms as $form): ?>
                <?php $formUrl = $form->getUrl(); ?>
                <article class="ep-collection-card no-media ep-collection-card--form">
                    <a class="ep-collection-card__media" href="<?= Html::encode($formUrl) ?>" tabindex="-1" aria-hidden="true">
                        <span class="ep-collection-card__media-fallback" aria-hidden="true">
                            <i class="fa fa-wpforms"></i>
                        </span>
                    </a>
                    <div class="ep-collection-card__body">
                        <div class="ep-collection-card__eyebrow"><?= Yii::t('EngagementPagesModule.base', 'Form') ?></div>
                        <h3 class="ep-collection-card__title">
                            <a href="<?= Html::encode($formUrl) ?>"><?= Html::encode($form->title) ?></a>
                        </h3>
                        <div class="ep-collection-card__footer">
                            <span class="ep-collection-card__meta"><?= Yii::t('EngagementPagesModule.base', 'Open') ?></span>
                            <a class="ep-collection-card__cta" href="<?= Html::encode($formUrl) ?>">
                                <?= Yii::t('EngagementPagesModule.base', 'Open form') ?>
                                <i class="fa fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($source === CollectionBlock::SOURCE_SPACES): ?>
    <?= $renderHeading($title !== '' ? $title : Yii::t('EngagementPagesModule.base', 'Communities')) ?>
    <?php
    $spaces = Space::find()
        ->where(['visibility' => [Space::VISIBILITY_REGISTERED_ONLY, Space::VISIBILITY_ALL]])
        ->orderBy(['name' => SORT_ASC])
        ->limit(50)
        ->all();
    ?>
    <?php if ($spaces === []): ?>
        <p class="text-muted"><?= Html::encode($emptyMessage) ?></p>
    <?php else: ?>
        <div class="ep-directory__grid">
            <?php foreach ($spaces as $space): ?>
                <?php
                $spaceUrl = $space->getUrl();
                $banner = $space->getProfileBannerImage();
                $bannerUrl = ($banner && $banner->hasImage()) ? $banner->getUrl() : null;
                $desc = trim(strip_tags((string) $space->description));
                ?>
                <article class="ep-collection-card<?= $bannerUrl ? ' has-media' : ' no-media' ?> ep-collection-card--space">
                    <a class="ep-collection-card__media" href="<?= Html::encode($spaceUrl) ?>" tabindex="-1" aria-hidden="true">
                        <?php if ($bannerUrl): ?>
                            <img src="<?= Html::encode($bannerUrl) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <span class="ep-collection-card__media-fallback" aria-hidden="true">
                                <i class="fa fa-users"></i>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="ep-collection-card__body">
                        <div class="ep-collection-card__eyebrow"><?= Yii::t('EngagementPagesModule.base', 'Space') ?></div>
                        <h3 class="ep-collection-card__title">
                            <a href="<?= Html::encode($spaceUrl) ?>"><?= Html::encode($space->name) ?></a>
                        </h3>
                        <?php if ($desc !== ''): ?>
                            <p class="ep-collection-card__summary"><?= Html::encode(mb_strimwidth($desc, 0, 140, '…')) ?></p>
                        <?php endif; ?>
                        <div class="ep-collection-card__footer">
                            <span class="ep-collection-card__meta">&nbsp;</span>
                            <a class="ep-collection-card__cta" href="<?= Html::encode($spaceUrl) ?>">
                                <?= Yii::t('EngagementPagesModule.base', 'View space') ?>
                                <i class="fa fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?= $renderHeading($title) ?>
    <p class="text-muted"><?= Html::encode($emptyMessage) ?></p>
<?php endif; ?>
</section>
