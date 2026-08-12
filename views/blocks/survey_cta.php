<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\engagementPages\blocks\SurveyCtaBlock;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\widgets\bootstrap\Button;

/** @var SurveyCtaBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$form = $block->getForm();
$formUrl = $block->getFormUrl();
$label = $settings['button_label'] !== ''
    ? $settings['button_label']
    : Yii::t('EngagementPagesModule.base', 'Take the survey');
?>
<section class="ep-block ep-survey-cta">
    <?php if ($settings['intro'] !== ''): ?>
        <div class="ep-survey-intro richtext-output">
            <?= RichText::convert($settings['intro'], RichText::FORMAT_HTML) ?>
        </div>
    <?php endif; ?>

    <?php if ($formUrl): ?>
        <?= Button::primary($label)->link($formUrl)->lg() ?>
        <?php if ($form): ?>
            <div class="ep-survey-meta"><?= Html::encode($form->title) ?></div>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-muted">
            <?= Yii::t('EngagementPagesModule.base', $settings['form_id'] ? 'Form unavailable' : 'No form selected') ?>
        </p>
    <?php endif; ?>
</section>
