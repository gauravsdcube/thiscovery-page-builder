<?php

use humhub\modules\thiscoveryPageBuilder\services\PageStyleService;
use yii\helpers\Html;

/**
 * Native colour + text field (does not depend on Forms JS).
 *
 * @var string $name
 * @var string $id
 * @var string $value
 * @var string|null $placeholder
 */

$placeholder = $placeholder ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Theme default');
$value = (string) $value;
$swatch = PageStyleService::swatchHex($value);
?>
<div class="ep-style-color" data-ep-style-color>
    <input type="color"
           class="ep-style-color__swatch"
           value="<?= Html::encode($swatch) ?>"
           data-ep-style-swatch
           aria-label="<?= Html::encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Pick colour')) ?>">
    <?= Html::textInput($name, $value, [
        'id' => $id,
        'class' => 'form-control',
        'placeholder' => $placeholder,
        'autocomplete' => 'off',
        'data-ep-style-text' => true,
        'spellcheck' => 'false',
    ]) ?>
</div>
