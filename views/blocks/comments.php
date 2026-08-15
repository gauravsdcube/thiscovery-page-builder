<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\CommentsBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageComment;
use humhub\modules\thiscoveryPageBuilder\models\PageCommentForm;
use humhub\widgets\form\CaptchaField;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var CommentsBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$allowGuests = !empty($settings['allow_guests']);
$askName = !empty($settings['ask_name']);
$requireEmail = !empty($settings['require_email']);
$showComments = !empty($settings['show_comments']);
$isGuest = Yii::$app->user->isGuest;
$canComment = !$isGuest || $allowGuests;
$flashKey = 'ep-comment-' . $page->id;
$flash = Yii::$app->session->getFlash($flashKey);
$comments = $showComments ? PageComment::findApprovedForPage((int) $page->id) : [];

/** @var PageCommentForm|null $commentForm */
$commentForm = Yii::$app->view->params['epCommentForm'] ?? null;
if ($commentForm === null) {
    $commentForm = new PageCommentForm();
    $commentForm->requireCaptcha = $isGuest;
    $commentForm->requireEmail = $requireEmail && $isGuest;
    $commentForm->requireName = $askName;
    if (!$isGuest && $askName) {
        $identity = Yii::$app->user->identity;
        $commentForm->author_name = trim((string) ($identity->displayName ?? $identity->username ?? ''));
    }
    if (!$isGuest) {
        $identity = Yii::$app->user->identity;
        $commentForm->author_email = (string) ($identity->email ?? '');
    }
}

$postUrl = Url::to(['/thiscovery-page-builder/public/comment', 'slug' => $page->slug]);
$anonymousLabel = Yii::t('ThiscoveryPageBuilderModule.base', 'Anonymous');
?>
<section class="ep-block ep-comments" id="ep-comments-<?= (int) $page->id ?>">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <?php if (($settings['intro'] ?? '') !== ''): ?>
        <p class="ep-comments__intro"><?= Html::encode($settings['intro']) ?></p>
    <?php endif; ?>

    <?php if ($flash === 'ok'): ?>
        <div class="ep-comments__success" role="status">
            <?= Html::encode($settings['success_message'] ?: Yii::t('ThiscoveryPageBuilderModule.base', 'Thanks — your comment has been submitted for review.')) ?>
        </div>
    <?php elseif ($flash === 'rate'): ?>
        <div class="ep-comments__error" role="alert">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Please wait a few minutes before posting another comment.') ?>
        </div>
    <?php elseif ($flash === 'denied'): ?>
        <div class="ep-comments__error" role="alert">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'You need to sign in to comment on this page.') ?>
        </div>
    <?php elseif ($flash === 'save'): ?>
        <div class="ep-comments__error" role="alert">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Sorry — we could not save your comment. Please try again.') ?>
        </div>
    <?php endif; ?>

    <?php if ($showComments): ?>
        <?php if ($comments !== []): ?>
            <ul class="ep-comments__list">
                <?php foreach ($comments as $comment): ?>
                    <?php
                    $displayName = trim((string) $comment->author_name);
                    if ($displayName === '') {
                        $displayName = $anonymousLabel;
                    }
                    ?>
                    <li class="ep-comments__item">
                        <div class="ep-comments__meta">
                            <strong><?= Html::encode($displayName) ?></strong>
                            <?php if ($comment->created_at): ?>
                                <span class="ep-comments__date">
                                    <?= Html::encode(Yii::$app->formatter->asDatetime($comment->created_at, 'medium')) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="ep-comments__body">
                            <?= nl2br(Html::encode($comment->body)) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="ep-comments__empty text-muted">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'No comments yet.') ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($canComment): ?>
        <div class="ep-comments__form-wrap">
            <h3 class="ep-comments__form-title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Leave a comment') ?></h3>
            <?php $form = ActiveForm::begin([
                'action' => $postUrl,
                'options' => [
                    'class' => 'ep-comments__form',
                    'data-keep-captcha' => '1',
                ],
                'enableClientValidation' => false,
            ]); ?>
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                <?php if ($commentForm->hasErrors()): ?>
                    <div class="ep-comments__error" role="alert">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Please check the form and try again.') ?>
                        <ul class="ep-comments__error-list">
                            <?php foreach ($commentForm->getFirstErrors() as $message): ?>
                                <li><?= Html::encode($message) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($askName): ?>
                    <?= $form->field($commentForm, 'author_name')->textInput([
                        'maxlength' => 120,
                        'autocomplete' => 'name',
                        'readonly' => !$isGuest && $commentForm->author_name !== '',
                    ]) ?>
                <?php endif; ?>
                <?php if ($requireEmail): ?>
                    <?= $form->field($commentForm, 'author_email')->input('email', [
                        'maxlength' => 255,
                        'autocomplete' => 'email',
                        'readonly' => !$isGuest && $commentForm->author_email !== '',
                    ]) ?>
                <?php endif; ?>
                <?= $form->field($commentForm, 'body')->textarea([
                    'rows' => 4,
                    'maxlength' => 4000,
                ]) ?>
                <?php if ($isGuest): ?>
                    <div class="ep-comments__captcha mb-3">
                        <?= $form->field($commentForm, 'captcha')->widget(CaptchaField::class)->label(
                            Yii::t('ThiscoveryPageBuilderModule.base', 'Verification')
                        ) ?>
                    </div>
                <?php endif; ?>
                <div class="ep-comments__actions">
                    <button type="submit" class="btn btn-primary">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Submit comment') ?>
                    </button>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    <?php elseif ($isGuest): ?>
        <p class="ep-comments__signin text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Please sign in to leave a comment.') ?>
        </p>
    <?php endif; ?>
</section>
