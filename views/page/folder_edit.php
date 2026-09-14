<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\PageFolder;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/** @var PageFolder $folder */
/** @var bool $isNew */
/** @var $contentContainer */
/** @var array $parentOptions */

ThiscoveryPageBuilderAsset::register($this);
$parentOptions = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'No parent (top level)')] + ($parentOptions ?? []);
$backParams = !$isNew && $folder->id
    ? ['folder' => (int) $folder->id]
    : ($folder->parent_id ? ['folder' => (int) $folder->parent_id] : []);
?>

<div class="ep-list-page" id="ep-folder-edit">
    <div class="ep-list-header">
        <div>
            <h1 class="ep-list-title">
                <?= $isNew
                    ? Yii::t('ThiscoveryPageBuilderModule.base', 'New folder')
                    : Yii::t('ThiscoveryPageBuilderModule.base', 'Edit folder') ?>
            </h1>
            <p class="ep-list-sub">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Folders group pages and collections in the admin list. They do not change public URLs.') ?>
            </p>
        </div>
        <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Back to pages'))
            ->link(Url::toIndex($contentContainer, $backParams))
            ->icon('arrow-left')
            ->loader(false) ?>
    </div>

    <?= Html::beginForm(Url::toFolderEdit($contentContainer, $isNew ? null : (int) $folder->id, $isNew && $folder->parent_id ? ['parent' => (int) $folder->parent_id] : []), 'post') ?>

    <div class="ep-folder-form">
        <div class="form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Folder name') ?></label>
            <?= Html::activeTextInput($folder, 'name', [
                'class' => 'form-control',
                'required' => true,
                'placeholder' => Yii::t('ThiscoveryPageBuilderModule.base', 'e.g. SPARCS2 or Recruitment'),
            ]) ?>
        </div>
        <div class="form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Description') ?>
                <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
            </label>
            <?= Html::activeTextarea($folder, 'description', ['class' => 'form-control', 'rows' => 2]) ?>
        </div>
        <div class="form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Parent folder') ?></label>
            <?= Html::activeDropDownList($folder, 'parent_id', $parentOptions, ['class' => 'form-control', 'style' => 'max-width:420px']) ?>
        </div>
        <div class="ep-folder-form__actions">
            <?= Button::save($isNew
                ? Yii::t('ThiscoveryPageBuilderModule.base', 'Create folder')
                : Yii::t('ThiscoveryPageBuilderModule.base', 'Save folder'))->submit() ?>
            <?= Button::light(Yii::t('ThiscoveryPageBuilderModule.base', 'Cancel'))
                ->link(Url::toIndex($contentContainer, $backParams))
                ->loader(false) ?>
        </div>
    </div>
    <?= Html::endForm() ?>
</div>
