<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\PollEmbedBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryForms\widgets\PollEmbed;

/** @var PollEmbedBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$form = $block->getForm();
?>
<section class="ep-block ep-poll-embed">
    <?php if ($form): ?>
        <?= PollEmbed::widget(['form' => $form, 'compact' => false]) ?>
    <?php else: ?>
        <p class="text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', $settings['form_id'] ? 'Form unavailable' : 'No poll selected') ?>
        </p>
    <?php endif; ?>
</section>
