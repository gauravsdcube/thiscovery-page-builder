<?php

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageTheme;
use humhub\modules\thiscoveryPageBuilder\services\PageStyleService;
use yii\helpers\Html;

/** @var EngagementPage $page */

$groups = (new PageStyleService())->groups();
$style = is_array($page->style) ? $page->style : [];
$themeOptions = PageTheme::dropdownOptions(true);
$defaultTheme = PageTheme::findDefault();
$defaultStyleJson = $defaultTheme
    ? json_encode($defaultTheme->getStyle(), JSON_UNESCAPED_UNICODE)
    : '{}';
$defaultCss = $defaultTheme ? (string) $defaultTheme->custom_css : '';
?>

<div class="ep-studio__settings ep-studio__settings--css">
    <h5 class="ep-section__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Appearance') ?></h5>
    <p class="ep-hint text-muted">
        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Pick a shared theme, or Custom to detach and keep local styles only. Values you set below override the selected theme for this page.') ?>
    </p>

    <div class="form-group">
        <label class="ep-label" for="ep-theme-id"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Theme') ?></label>
        <?= Html::activeDropDownList($page, 'theme_id', $themeOptions, [
            'id' => 'ep-theme-id',
            'class' => 'form-control',
            'data-ep-theme-select' => true,
            'data-ep-default-theme-id' => $defaultTheme ? (string) $defaultTheme->id : '',
            'data-ep-default-theme-style' => $defaultStyleJson,
            'data-ep-default-theme-css' => $defaultCss,
        ]) ?>
    </div>

    <div class="ep-style-accs">
        <?php foreach ($groups as $group): ?>
            <?php $values = is_array($style[$group['id']] ?? null) ? $style[$group['id']] : []; ?>
            <details class="ep-style-acc">
                <summary><?= Html::encode($group['label']) ?></summary>
                <div class="ep-style-acc__body">
                    <div class="ep-style-grid">
                        <?php foreach ($group['fields'] as $field): ?>
                            <?php
                            $name = 'EngagementPage[style][' . $group['id'] . '][' . $field['name'] . ']';
                            $id = 'ep-style-' . $group['id'] . '-' . $field['name'];
                            $value = (string) ($values[$field['name']] ?? '');
                            $type = (string) ($field['type'] ?? 'text');
                            $itemClass = 'form-group mb-0' . ($type === 'color' ? ' ep-style-grid__item--color' : '');
                            ?>
                            <div class="<?= $itemClass ?>">
                                <label class="ep-label" for="<?= Html::encode($id) ?>"><?= Html::encode($field['label']) ?></label>
                                <?php if ($type === 'weight'): ?>
                                    <?= Html::dropDownList($name, $value, $field['options'] ?? ['' => ''], [
                                        'id' => $id,
                                        'class' => 'form-control',
                                    ]) ?>
                                <?php elseif ($type === 'color'): ?>
                                    <?= $this->render('_style_color_field', [
                                        'name' => $name,
                                        'id' => $id,
                                        'value' => $value,
                                    ]) ?>
                                <?php else: ?>
                                    <?= Html::textInput($name, $value, [
                                        'id' => $id,
                                        'class' => 'form-control',
                                        'placeholder' => Yii::t('ThiscoveryPageBuilderModule.base', 'Theme default'),
                                        'autocomplete' => 'off',
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <h5 class="ep-section__title mt-4"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Custom CSS') ?></h5>
    <p class="ep-hint text-muted">
        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Optional extra CSS for this page only. Prefer selectors under #ep-page.') ?>
    </p>
    <?= Html::activeTextarea($page, 'custom_css', [
        'class' => 'form-control ep-css-editor',
        'rows' => 12,
        'spellcheck' => 'false',
        'placeholder' => "#ep-page .ep-hero__title {\n  letter-spacing: .02em;\n}",
    ]) ?>
</div>
