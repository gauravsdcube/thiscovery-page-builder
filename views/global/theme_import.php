<?php

use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use yii\helpers\Html;

/** @var string|null $error */

$this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Import theme');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Html::encode($this->title) ?>
        <span class="pull-right">
            <a href="<?= Html::encode(Url::toThemes()) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Back to themes') ?>
            </a>
        </span>
    </div>
    <div class="panel-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= Html::encode($error) ?></div>
        <?php endif; ?>
        <?= Html::beginForm('', 'post', ['enctype' => 'multipart/form-data']) ?>
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <div class="form-group">
                <label><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Theme JSON file') ?></label>
                <input type="file" name="theme_file" accept="application/json,.json" class="form-control">
            </div>
            <div class="form-group">
                <label><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Or paste JSON') ?></label>
                <textarea name="theme_json" class="form-control" rows="12" spellcheck="false"></textarea>
            </div>
            <?= Html::submitButton(Yii::t('ThiscoveryPageBuilderModule.base', 'Import'), ['class' => 'btn btn-primary']) ?>
        <?= Html::endForm() ?>
    </div>
</div>
