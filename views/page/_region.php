<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;

/** @var string $region */
/** @var string $label */
/** @var array $items */
/** @var array $formOptions */
/** @var array $pollOptions */
/** @var array $blockLabels */
/** @var bool $hidden */
/** @var \humhub\modules\engagementPages\models\EngagementPage|null $page */

$hidden = !empty($hidden);
$page = $page ?? null;
$pollOptions = $pollOptions ?? [];
?>
<div class="ep-region<?= $hidden ? ' is-hidden' : '' ?>"
     data-ep-region="<?= Html::encode($region) ?>"
     <?= $hidden ? 'hidden' : '' ?>>
    <div class="ep-region__head">
        <span class="ep-region__label"><?= Html::encode($label) ?></span>
        <span class="ep-region__hint"><?= Yii::t('EngagementPagesModule.base', 'Drop here') ?></span>
    </div>
    <div class="ep-region__list" data-ep-region-list="<?= Html::encode($region) ?>" data-ep-drop-zone="region">
        <?php foreach ($items as $i => $section): ?>
            <?= $this->render('_section_card', [
                'index' => $region . '-' . $i,
                'section' => array_merge($section, ['region' => $region]),
                'formOptions' => $formOptions,
                'pollOptions' => $pollOptions ?? [],
                'blockLabels' => $blockLabels,
                'collapsed' => true,
                'isChild' => false,
                'page' => $page,
            ]) ?>
        <?php endforeach; ?>
    </div>
</div>
