<?php

use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\PageTheme;
use yii\helpers\Html;

/** @var PageTheme[] $themes */

$this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Page appearance themes');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Html::encode($this->title) ?>
        <span class="pull-right">
            <a href="<?= Html::encode(Url::toGlobalIndex()) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Back to pages') ?>
            </a>
        </span>
    </div>
    <div class="panel-body">
        <p class="help-block">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Named themes can be applied on any page. Updating a theme updates every page that uses it (page-level overrides still win).') ?>
        </p>
        <p>
            <a class="btn btn-default" href="<?= Html::encode(Url::toThemeEdit()) ?>">
                <i class="fa fa-plus" aria-hidden="true"></i>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'New theme') ?>
            </a>
            <a class="btn btn-default" href="<?= Html::encode(Url::toThemeImport()) ?>">
                <i class="fa fa-upload" aria-hidden="true"></i>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Import theme') ?>
            </a>
        </p>
        <?php if (!$themes): ?>
            <p class="text-muted"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'No themes yet.') ?></p>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Name') ?></th>
                    <th><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Default') ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($themes as $theme): ?>
                    <tr>
                        <td><?= Html::encode($theme->name) ?></td>
                        <td><?= $theme->is_default ? Yii::t('ThiscoveryPageBuilderModule.base', 'Yes') : '' ?></td>
                        <td class="text-right">
                            <a href="<?= Html::encode(Url::toThemeEdit((int) $theme->id)) ?>">
                                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Edit') ?>
                            </a>
                            ·
                            <a href="<?= Html::encode(Url::toThemeExport((int) $theme->id)) ?>">
                                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Export') ?>
                            </a>
                            <?php if (!$theme->is_default): ?>
                                ·
                                <?= Html::beginForm(Url::toThemeDelete((int) $theme->id), 'post', ['style' => 'display:inline']) ?>
                                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                                    <?= Html::submitButton(Yii::t('ThiscoveryPageBuilderModule.base', 'Delete'), [
                                        'class' => 'btn btn-link btn-sm text-danger',
                                        'onclick' => 'return confirm(' . json_encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Delete this theme?')) . ');',
                                    ]) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
