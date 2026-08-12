<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\blocks\ContactBlock;
use humhub\modules\engagementPages\models\EngagementPage;

/** @var ContactBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$hasDetails = ($settings['name'] ?? '') !== ''
    || ($settings['email'] ?? '') !== ''
    || ($settings['phone'] ?? '') !== ''
    || ($settings['address'] ?? '') !== ''
    || ($settings['organisation'] ?? '') !== ''
    || ($settings['website'] ?? '') !== ''
    || ($settings['notes'] ?? '') !== '';

if (!$hasDetails && ($settings['title'] ?? '') === '') {
    return;
}
?>
<section class="ep-block ep-contact">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>

    <div class="ep-contact__card">
        <?php if (($settings['organisation'] ?? '') !== ''): ?>
            <div class="ep-contact__org"><?= Html::encode($settings['organisation']) ?></div>
        <?php endif; ?>

        <?php if (($settings['name'] ?? '') !== ''): ?>
            <div class="ep-contact__name"><?= Html::encode($settings['name']) ?></div>
        <?php endif; ?>

        <?php if (($settings['role'] ?? '') !== ''): ?>
            <div class="ep-contact__role"><?= Html::encode($settings['role']) ?></div>
        <?php endif; ?>

        <dl class="ep-contact__details">
            <?php if (($settings['email'] ?? '') !== ''): ?>
                <div class="ep-contact__row">
                    <dt><?= Yii::t('EngagementPagesModule.base', 'Email') ?></dt>
                    <dd>
                        <?php if (!empty($settings['show_email_link'])): ?>
                            <a href="mailto:<?= Html::encode($settings['email']) ?>"><?= Html::encode($settings['email']) ?></a>
                        <?php else: ?>
                            <?= Html::encode($settings['email']) ?>
                        <?php endif; ?>
                    </dd>
                </div>
            <?php endif; ?>

            <?php if (($settings['phone'] ?? '') !== ''): ?>
                <div class="ep-contact__row">
                    <dt><?= Yii::t('EngagementPagesModule.base', 'Phone') ?></dt>
                    <dd>
                        <a href="tel:<?= Html::encode(preg_replace('/\s+/', '', $settings['phone'])) ?>">
                            <?= Html::encode($settings['phone']) ?>
                        </a>
                    </dd>
                </div>
            <?php endif; ?>

            <?php if (($settings['address'] ?? '') !== ''): ?>
                <div class="ep-contact__row">
                    <dt><?= Yii::t('EngagementPagesModule.base', 'Address') ?></dt>
                    <dd><?= nl2br(Html::encode($settings['address'])) ?></dd>
                </div>
            <?php endif; ?>

            <?php if (($settings['website'] ?? '') !== ''): ?>
                <div class="ep-contact__row">
                    <dt><?= Yii::t('EngagementPagesModule.base', 'Website') ?></dt>
                    <dd>
                        <a href="<?= Html::encode($settings['website']) ?>" target="_blank" rel="noopener">
                            <?= Html::encode($settings['website_label'] ?: $settings['website']) ?>
                        </a>
                    </dd>
                </div>
            <?php endif; ?>
        </dl>

        <?php if (($settings['notes'] ?? '') !== ''): ?>
            <p class="ep-contact__notes"><?= nl2br(Html::encode($settings['notes'])) ?></p>
        <?php endif; ?>

        <?php if (($settings['email'] ?? '') !== '' && !empty($settings['show_email_link'])): ?>
            <a class="btn btn-primary btn-sm ep-contact__cta"
               href="mailto:<?= Html::encode($settings['email']) ?>">
                <?= Yii::t('EngagementPagesModule.base', 'Send email') ?>
            </a>
        <?php endif; ?>
    </div>
</section>
