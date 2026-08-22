<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\PageFollow;
use humhub\widgets\bootstrap\Button;

/** @var PageFollow[] $follows */
/** @var int|null $pageId */
/** @var int $totalCount */
/** @var array<int, string> $pageOptions */

$this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Subscriptions');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Html::encode($this->title) ?></strong>
        <span class="text-muted" style="margin-left:8px;font-weight:normal;">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', '{n,plural,=1{# email} other{# emails}}', ['n' => (int) $totalCount]) ?>
        </span>
        <?= Button::defaultType(Yii::t('ThiscoveryPageBuilderModule.base', 'Back to pages'))
            ->link(Url::toGlobalIndex())
            ->sm()
            ->right() ?>
        <?= Button::defaultType(Yii::t('ThiscoveryPageBuilderModule.base', 'Help'))
            ->link(Url::toHelp(null, 'creators-engagement'))
            ->icon('question-circle')
            ->sm()
            ->right() ?>
        <?php if ($follows !== []): ?>
            <?= Button::primary(Yii::t('ThiscoveryPageBuilderModule.base', 'Export CSV'))
                ->link(Url::toGlobalExportSubscriptions($pageId))
                ->icon('download')
                ->sm()
                ->right() ?>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <p class="text-muted ep-comment-history-hint">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Emails collected from the Get updates form on network-level public pages. Export downloads the current filter as a CSV file.') ?>
        </p>

        <?php if ($pageOptions !== []): ?>
            <form method="get" action="<?= Html::encode(Url::toGlobalSubscriptions()) ?>" class="ep-subscription-filters mb-3">
                <label class="ep-label" for="ep-sub-page"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page') ?></label>
                <select class="form-control" id="ep-sub-page" name="page_id" style="max-width:320px;display:inline-block;margin-right:8px;"
                        onchange="this.form.submit()">
                    <option value=""><?= Yii::t('ThiscoveryPageBuilderModule.base', 'All pages') ?></option>
                    <?php foreach ($pageOptions as $id => $title): ?>
                        <option value="<?= (int) $id ?>" <?= (int) $pageId === (int) $id ? 'selected' : '' ?>>
                            <?= Html::encode($title) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <?php if ($follows === []): ?>
            <p class="text-muted"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'No subscriptions yet.') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover ep-subscription-table">
                    <thead>
                        <tr>
                            <th><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Email') ?></th>
                            <th><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page') ?></th>
                            <th><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Subscribed') ?></th>
                            <th class="ep-subscription-table__actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($follows as $follow): ?>
                            <?php $page = $follow->page; ?>
                            <tr>
                                <td>
                                    <a href="mailto:<?= Html::encode($follow->email) ?>"><?= Html::encode($follow->email) ?></a>
                                </td>
                                <td>
                                    <?php if ($page): ?>
                                        <a href="<?= Html::encode(Url::toGlobalEdit($page)) ?>">
                                            <?= Html::encode($page->title) ?>
                                        </a>
                                        <div class="text-muted" style="font-size:12px;">
                                            <?= Html::encode($page->getPublicPath()) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Deleted page') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $follow->created_at
                                        ? Html::encode(Yii::$app->formatter->asDatetime($follow->created_at, 'medium'))
                                        : '' ?>
                                </td>
                                <td class="ep-subscription-table__actions">
                                    <?= Html::beginForm(Url::toGlobalDeleteSubscription((int) $follow->id), 'post', ['class' => 'ep-inline-form']) ?>
                                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                                        <?= Html::hiddenInput('filter_page_id', $pageId !== null ? (string) $pageId : '') ?>
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm(<?= Html::encode(json_encode(Yii::t('ThiscoveryPageBuilderModule.base', 'Remove this email from the subscription list?'))) ?>);">
                                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Delete') ?>
                                        </button>
                                    <?= Html::endForm() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
