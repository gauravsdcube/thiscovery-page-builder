<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;

/** @var string $namePrefix */
/** @var array $item */
/** @var string $safeIndex */
?>
<div class="ep-repeat-item" data-ep-repeat-item>
    <div class="row g-2">
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Phase label') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[label]"
                   value="<?= Html::encode($item['label'] ?? '') ?>">
        </div>
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Status') ?></label>
            <select class="form-control" name="<?= $namePrefix ?>[status]">
                <?php foreach ([
                    'done' => Yii::t('ThiscoveryPageBuilderModule.base', 'Completed'),
                    'current' => Yii::t('ThiscoveryPageBuilderModule.base', 'Current'),
                    'upcoming' => Yii::t('ThiscoveryPageBuilderModule.base', 'Upcoming'),
                ] as $value => $label): ?>
                    <option value="<?= Html::encode($value) ?>" <?= ($item['status'] ?? 'upcoming') === $value ? 'selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Description') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[description]"
               value="<?= Html::encode($item['description'] ?? '') ?>">
    </div>
    <button type="button" class="btn btn-light btn-sm" data-ep-repeat-remove>
        <i class="fa fa-times"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Remove') ?>
    </button>
    <hr>
</div>
