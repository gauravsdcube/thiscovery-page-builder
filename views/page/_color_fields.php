<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/**
 * Shared appearance fields (colours and corner radius) for section cards.
 *
 * @var array $settings
 * @var string $namePrefix
 * @var string $safeIndex
 * @var string $type
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\BaseBlock;

$bg = BaseBlock::sanitizeColor($settings['background_color'] ?? '');
$fg = BaseBlock::sanitizeColor($settings['text_color'] ?? '');
$border = BaseBlock::sanitizeColor($settings['border_color'] ?? '');
$showBorder = !array_key_exists('show_border', $settings) || !empty($settings['show_border']);
$bgPicker = $bg !== '' ? $bg : ($type === 'hero' ? '#003078' : '#ffffff');
$fgPicker = $fg !== '' ? $fg : ($type === 'hero' ? '#ffffff' : '#0b0c0c');
$borderPicker = $border !== '' ? $border : '#b1b4b6';
$presets = BaseBlock::colorPresets();
$isHero = ($type === 'hero');
$radius = array_key_exists('border_radius', $settings) && $settings['border_radius'] !== '' && $settings['border_radius'] !== null
    ? (string) (int) $settings['border_radius']
    : '';
$radiusPresets = [0, 4, 8, 12, 16, 24];
?>
<div class="ep-color-fields">
    <div class="ep-color-fields__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Appearance') ?></div>
    <p class="ep-hint text-muted mb-2">
        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Leave blank to use the default theme colours for this section.') ?>
    </p>
    <div class="row g-3">
        <div class="col-md-6 form-group mb-2">
            <label class="ep-label" for="ep-bg-<?= Html::encode($safeIndex) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Background colour') ?>
            </label>
            <div class="ep-color-input" data-ep-color-field>
                <input type="color"
                       class="ep-color-input__picker"
                       value="<?= Html::encode($bgPicker) ?>"
                       data-ep-color-picker
                       aria-label="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Pick background colour') ?>">
                <input type="text"
                       class="form-control ep-color-input__text"
                       id="ep-bg-<?= Html::encode($safeIndex) ?>"
                       name="<?= $namePrefix ?>[settings][background_color]"
                       value="<?= Html::encode($bg) ?>"
                       placeholder="<?= $type === 'hero' ? '#003078' : Yii::t('ThiscoveryPageBuilderModule.base', 'Default') ?>"
                       maxlength="7"
                       pattern="#?[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?"
                       data-ep-color-text
                       autocomplete="off"
                       spellcheck="false">
                <button type="button" class="btn btn-sm btn-light" data-ep-color-clear
                        title="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset to default') ?>">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset') ?>
                </button>
            </div>
            <div class="ep-color-presets" role="list" aria-label="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Background colour presets') ?>">
                <?php foreach ($presets as $preset): ?>
                    <button type="button"
                            class="ep-color-presets__swatch"
                            style="background: <?= Html::encode($preset) ?>"
                            data-ep-color-preset="<?= Html::encode($preset) ?>"
                            data-ep-color-target="background_color"
                            title="<?= Html::encode($preset) ?>"
                            aria-label="<?= Html::encode($preset) ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-6 form-group mb-2">
            <label class="ep-label" for="ep-fg-<?= Html::encode($safeIndex) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Text colour') ?>
            </label>
            <div class="ep-color-input" data-ep-color-field>
                <input type="color"
                       class="ep-color-input__picker"
                       value="<?= Html::encode($fgPicker) ?>"
                       data-ep-color-picker
                       aria-label="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Pick text colour') ?>">
                <input type="text"
                       class="form-control ep-color-input__text"
                       id="ep-fg-<?= Html::encode($safeIndex) ?>"
                       name="<?= $namePrefix ?>[settings][text_color]"
                       value="<?= Html::encode($fg) ?>"
                       placeholder="<?= $type === 'hero' ? '#ffffff' : Yii::t('ThiscoveryPageBuilderModule.base', 'Default') ?>"
                       maxlength="7"
                       pattern="#?[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?"
                       data-ep-color-text
                       autocomplete="off"
                       spellcheck="false">
                <button type="button" class="btn btn-sm btn-light" data-ep-color-clear
                        title="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset to default') ?>">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset') ?>
                </button>
            </div>
        </div>
        <?php if ($isHero): ?>
            <div class="col-12 form-group mb-1">
                <div class="form-check">
                    <input type="hidden" name="<?= $namePrefix ?>[settings][show_border]" value="0">
                    <input class="form-check-input" type="checkbox" value="1"
                           name="<?= $namePrefix ?>[settings][show_border]"
                           id="ep-show-border-<?= Html::encode($safeIndex) ?>"
                           data-ep-show-border
                        <?= $showBorder ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ep-show-border-<?= Html::encode($safeIndex) ?>">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Show border') ?>
                    </label>
                </div>
            </div>
            <div class="col-md-6 form-group mb-2" data-ep-border-color-wrap<?= $showBorder ? '' : ' hidden' ?>>
                <label class="ep-label" for="ep-border-<?= Html::encode($safeIndex) ?>">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Border colour') ?>
                </label>
                <div class="ep-color-input" data-ep-color-field>
                    <input type="color"
                           class="ep-color-input__picker"
                           value="<?= Html::encode($borderPicker) ?>"
                           data-ep-color-picker
                           aria-label="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Pick border colour') ?>">
                    <input type="text"
                           class="form-control ep-color-input__text"
                           id="ep-border-<?= Html::encode($safeIndex) ?>"
                           name="<?= $namePrefix ?>[settings][border_color]"
                           value="<?= Html::encode($border) ?>"
                           placeholder="#b1b4b6"
                           maxlength="7"
                           pattern="#?[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?"
                           data-ep-color-text
                           autocomplete="off"
                           spellcheck="false">
                    <button type="button" class="btn btn-sm btn-light" data-ep-color-clear
                            title="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset to default') ?>">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset') ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="form-group mb-0 mt-2">
        <label class="ep-label" for="ep-radius-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Corner radius') ?>
            <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
        </label>
        <div class="ep-radius-input" data-ep-radius-field>
            <input type="number"
                   class="form-control ep-radius-input__number"
                   id="ep-radius-<?= Html::encode($safeIndex) ?>"
                   name="<?= $namePrefix ?>[settings][border_radius]"
                   value="<?= Html::encode($radius) ?>"
                   min="<?= (int) BaseBlock::RADIUS_MIN ?>"
                   max="<?= (int) BaseBlock::RADIUS_MAX ?>"
                   step="1"
                   inputmode="numeric"
                   placeholder="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Default') ?>"
                   data-ep-radius-text
                   autocomplete="off">
            <span class="ep-radius-input__unit"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'px') ?></span>
            <button type="button" class="btn btn-sm btn-light" data-ep-radius-clear
                    title="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset to default') ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Reset') ?>
            </button>
        </div>
        <div class="ep-radius-presets" role="list" aria-label="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Corner radius presets') ?>">
            <?php foreach ($radiusPresets as $preset): ?>
                <button type="button"
                        class="ep-radius-presets__chip"
                        data-ep-radius-preset="<?= (int) $preset ?>"
                        title="<?= $preset === 0
                            ? Yii::t('ThiscoveryPageBuilderModule.base', 'Square')
                            : ((int) $preset . 'px') ?>">
                    <?= $preset === 0
                        ? Yii::t('ThiscoveryPageBuilderModule.base', 'Square')
                        : ((int) $preset . 'px') ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="ep-hint text-muted mt-1">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Leave blank for the theme default. 0 is square corners. Applies to this section and its cards.') ?>
        </div>
    </div>
</div>
