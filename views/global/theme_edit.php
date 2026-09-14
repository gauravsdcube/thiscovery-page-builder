<?php

use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\PageTheme;
use yii\helpers\Html;

/** @var PageTheme $theme */
/** @var array $groups */
/** @var array $style */

ThiscoveryPageBuilderAsset::register($this);
$this->title = $theme->isNewRecord
    ? Yii::t('ThiscoveryPageBuilderModule.base', 'New theme')
    : Yii::t('ThiscoveryPageBuilderModule.base', 'Edit theme');
$this->registerJs('humhub.require("thiscoveryPageBuilder").initStyleFields("#ep-theme-edit");', \yii\web\View::POS_READY);
?>

<div class="panel panel-default" id="ep-theme-edit">
    <div class="panel-heading">
        <?= Html::encode($this->title) ?>
        <span class="pull-right">
            <a href="<?= Html::encode(Url::toThemes()) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Back to themes') ?>
            </a>
        </span>
    </div>
    <div class="panel-body">
        <?= Html::beginForm('', 'post', ['class' => 'ep-theme-form']) ?>
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <div class="form-group">
                <label class="control-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Theme name') ?></label>
                <?= Html::textInput('name', $theme->name, ['class' => 'form-control', 'required' => true]) ?>
            </div>
            <div class="checkbox">
                <label>
                    <?= Html::checkbox('is_default', !empty($theme->is_default), ['value' => 1]) ?>
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Make this the default theme') ?>
                </label>
            </div>

            <h4><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Appearance') ?></h4>
            <p class="help-block">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Leave fields blank to use the site HumHub theme. Pages can still override individual values.') ?>
            </p>
            <div class="ep-style-accs">
                <?php foreach ($groups as $group): ?>
                    <?php $values = is_array($style[$group['id']] ?? null) ? $style[$group['id']] : []; ?>
                    <details class="ep-style-acc">
                        <summary><?= Html::encode($group['label']) ?></summary>
                        <div class="ep-style-acc__body">
                            <div class="ep-style-grid">
                                <?php foreach ($group['fields'] as $field): ?>
                                    <?php
                                    $name = 'style[' . $group['id'] . '][' . $field['name'] . ']';
                                    $id = 'ep-theme-' . $group['id'] . '-' . $field['name'];
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
                                            <?= $this->render('@thiscovery-page-builder/views/page/_style_color_field', [
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

            <h4 class="mt-4"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Custom CSS') ?></h4>
            <?= Html::textarea('custom_css', $theme->custom_css, [
                'class' => 'form-control ep-css-editor',
                'rows' => 10,
                'spellcheck' => 'false',
            ]) ?>

            <div class="mt-3">
                <?= Html::submitButton(Yii::t('ThiscoveryPageBuilderModule.base', 'Save theme'), ['class' => 'btn btn-primary']) ?>
                <a class="btn btn-default" href="<?= Html::encode(Url::toThemes()) ?>">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Cancel') ?>
                </a>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
