<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;

/** @var string $namePrefix */
/** @var array $item */
?>
<div class="ep-repeat-item" data-ep-repeat-item>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Event title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[title]"
               value="<?= Html::encode($item['title'] ?? '') ?>">
    </div>
    <div class="row g-2">
        <div class="col-md-4 form-group">
            <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Date') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[date]"
                   placeholder="YYYY-MM-DD" value="<?= Html::encode($item['date'] ?? '') ?>">
        </div>
        <div class="col-md-4 form-group">
            <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Time') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[time]"
                   value="<?= Html::encode($item['time'] ?? '') ?>">
        </div>
        <div class="col-md-4 form-group">
            <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Location') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[location]"
                   value="<?= Html::encode($item['location'] ?? '') ?>">
        </div>
    </div>
    <div class="row g-2">
        <div class="col-md-8 form-group">
            <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Registration URL') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[url]"
                   value="<?= Html::encode($item['url'] ?? '') ?>">
        </div>
        <div class="col-md-4 form-group">
            <label class="ep-label"><?= Yii::t('EngagementPagesModule.base', 'Button label') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[cta_label]"
                   value="<?= Html::encode($item['cta_label'] ?? Yii::t('EngagementPagesModule.base', 'Register')) ?>">
        </div>
    </div>
    <button type="button" class="btn btn-light btn-sm" data-ep-repeat-remove>
        <i class="fa fa-times"></i> <?= Yii::t('EngagementPagesModule.base', 'Remove') ?>
    </button>
    <hr>
</div>
