<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryEditor\widgets\EditorField;

/** @var string $namePrefix */
/** @var array $item */
/** @var string $safeIndex */
?>
<div class="ep-repeat-item" data-ep-repeat-item>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Heading') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[heading]"
               value="<?= Html::encode($item['heading'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Body') ?></label>
        <div class="ep-rich-editor" data-ep-rich-editor>
            <?= EditorField::widget([
                'id' => 'ep-acc-body-' . $safeIndex,
                'name' => $namePrefix . '[body]',
                'value' => (string) ($item['body'] ?? ''),
                'height' => 220,
                'profile' => 'simple',
            ]) ?>
        </div>
    </div>
    <button type="button" class="btn btn-light btn-sm" data-ep-repeat-remove>
        <i class="fa fa-times"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Remove') ?>
    </button>
    <hr>
</div>
