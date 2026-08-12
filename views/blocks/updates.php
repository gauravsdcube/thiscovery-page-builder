<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\blocks\UpdatesBlock;
use humhub\modules\engagementPages\helpers\Url as PageUrl;
use humhub\modules\engagementPages\models\EngagementPage;
use yii\helpers\Url;

/** @var UpdatesBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$followUrl = Url::to(['/engagement-pages/public/follow', 'slug' => $page->slug]);
$flashKey = 'ep-follow-' . $page->id;
$success = Yii::$app->session->getFlash($flashKey);
?>
<section class="ep-block ep-updates" id="ep-updates-<?= (int) $page->id ?>">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <?php if (($settings['intro'] ?? '') !== ''): ?>
        <p class="ep-updates__intro"><?= Html::encode($settings['intro']) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="ep-updates__success" role="status">
            <?= Html::encode($settings['success_message'] ?: Yii::t('EngagementPagesModule.base', 'Thanks — we will keep you updated.')) ?>
        </div>
    <?php else: ?>
        <?= Html::beginForm($followUrl, 'post', ['class' => 'ep-updates__form']) ?>
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <label class="sr-only" for="ep-follow-email-<?= (int) $page->id ?>">
                <?= Yii::t('EngagementPagesModule.base', 'Email address') ?>
            </label>
            <div class="ep-updates__row">
                <input type="email"
                       class="form-control"
                       required
                       name="email"
                       id="ep-follow-email-<?= (int) $page->id ?>"
                       placeholder="<?= Html::encode(Yii::t('EngagementPagesModule.base', 'Email address')) ?>"
                       autocomplete="email">
                <button type="submit" class="btn btn-primary">
                    <?= Html::encode($settings['button_label'] ?: Yii::t('EngagementPagesModule.base', 'Subscribe')) ?>
                </button>
            </div>
        <?= Html::endForm() ?>
    <?php endif; ?>
</section>
