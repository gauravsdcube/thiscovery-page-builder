<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\engagementPages\helpers\Url;
use humhub\modules\engagementPages\models\PageComment;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;

/** @var PageComment[] $comments */
/** @var string $status */
/** @var int|null $pageId */
/** @var int $pendingCount */
/** @var int $approvedCount */
/** @var int $rejectedCount */
/** @var int $allCount */
/** @var array $statusOptions */

$this->title = Yii::t('EngagementPagesModule.base', 'Page comments');

$filterLabels = [
    'all' => Yii::t('EngagementPagesModule.base', 'All') . ' (' . (int) $allCount . ')',
    'pending' => ($statusOptions[PageComment::STATUS_PENDING] ?? 'Pending') . ' (' . (int) $pendingCount . ')',
    'approved' => ($statusOptions[PageComment::STATUS_APPROVED] ?? 'Approved') . ' (' . (int) $approvedCount . ')',
    'rejected' => ($statusOptions[PageComment::STATUS_REJECTED] ?? 'Rejected') . ' (' . (int) $rejectedCount . ')',
];
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Html::encode($this->title) ?></strong>
        <?php if ($pendingCount > 0): ?>
            <?= Badge::warning($pendingCount . ' ' . Yii::t('EngagementPagesModule.base', 'pending'))->pill() ?>
        <?php endif; ?>
        <?= Button::defaultType(Yii::t('EngagementPagesModule.base', 'Back to pages'))
            ->link(Url::toGlobalIndex())
            ->sm()
            ->right() ?>
    </div>
    <div class="panel-body">
        <p class="text-muted ep-comment-history-hint">
            <?= Yii::t('EngagementPagesModule.base', 'Approved and rejected comments stay in this history. Use Delete only when you need to remove a comment permanently.') ?>
        </p>
        <div class="ep-comment-filters mb-3">
            <?php foreach (['all', 'pending', 'approved', 'rejected'] as $key): ?>
                <a class="btn btn-sm <?= $status === $key ? 'btn-primary' : 'btn-light' ?>"
                   href="<?= Html::encode(Url::toGlobalComments($key, $pageId)) ?>">
                    <?= Html::encode($filterLabels[$key]) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($comments === []): ?>
            <p class="text-muted"><?= Yii::t('EngagementPagesModule.base', 'No comments in this filter.') ?></p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <?php
                $page = $comment->page;
                $badge = match ((int) $comment->status) {
                    PageComment::STATUS_APPROVED => Badge::success($statusOptions[PageComment::STATUS_APPROVED] ?? 'Approved')->pill(),
                    PageComment::STATUS_REJECTED => Badge::danger($statusOptions[PageComment::STATUS_REJECTED] ?? 'Rejected')->pill(),
                    default => Badge::warning($statusOptions[PageComment::STATUS_PENDING] ?? 'Pending')->pill(),
                };
                ?>
                <div class="ep-mod-comment" data-status="<?= (int) $comment->status ?>">
                    <div class="ep-mod-comment__head">
                        <strong><?= Html::encode($comment->author_name) ?></strong>
                        <?php if ($comment->author_email): ?>
                            <span class="text-muted">&lt;<?= Html::encode($comment->author_email) ?>&gt;</span>
                        <?php endif; ?>
                        <?= $badge ?>
                        <?php if ($page): ?>
                            <span class="ep-mod-comment__page">
                                <?= Yii::t('EngagementPagesModule.base', 'on') ?>
                                <a href="<?= Html::encode(Url::toGlobalEdit($page)) ?>">
                                    <?= Html::encode($page->title) ?>
                                </a>
                            </span>
                        <?php endif; ?>
                        <span class="ep-mod-comment__date text-muted">
                            <?= $comment->created_at ? Html::encode(Yii::$app->formatter->asDatetime($comment->created_at, 'medium')) : '' ?>
                        </span>
                    </div>
                    <?php if (!$comment->isPending() && ($comment->moderated_at || $comment->moderated_by)): ?>
                        <div class="ep-mod-comment__meta text-muted">
                            <?php
                            $moderatorName = $comment->moderator->displayName ?? null;
                            if ($comment->moderated_at && $moderatorName) {
                                echo Html::encode(Yii::t('EngagementPagesModule.base', 'Moderated by {name} on {date}', [
                                    'name' => $moderatorName,
                                    'date' => Yii::$app->formatter->asDatetime($comment->moderated_at, 'medium'),
                                ]));
                            } elseif ($comment->moderated_at) {
                                echo Html::encode(Yii::t('EngagementPagesModule.base', 'Moderated on {date}', [
                                    'date' => Yii::$app->formatter->asDatetime($comment->moderated_at, 'medium'),
                                ]));
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="ep-mod-comment__body">
                        <?= nl2br(Html::encode($comment->body)) ?>
                    </div>
                    <div class="ep-mod-comment__actions">
                        <?= Html::beginForm(Url::toGlobalModerateComment((int) $comment->id), 'post', ['class' => 'ep-inline-form']) ?>
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                            <?= Html::hiddenInput('filter_status', $status) ?>
                            <?= Html::hiddenInput('filter_page_id', $pageId !== null ? (string) $pageId : '') ?>
                            <?= Html::hiddenInput('return_url', Url::toGlobalComments($status, $pageId)) ?>
                            <?php if (!$comment->isApproved()): ?>
                                <button type="submit" name="moderate_action" value="approve" class="btn btn-sm btn-primary">
                                    <?= Yii::t('EngagementPagesModule.base', 'Approve') ?>
                                </button>
                            <?php endif; ?>
                            <?php if (!$comment->isRejected()): ?>
                                <button type="submit" name="moderate_action" value="reject" class="btn btn-sm btn-light">
                                    <?= Yii::t('EngagementPagesModule.base', 'Reject') ?>
                                </button>
                            <?php endif; ?>
                            <button type="submit" name="moderate_action" value="delete" class="btn btn-sm btn-danger"
                                    onclick="return confirm(<?= Html::encode(json_encode(Yii::t('EngagementPagesModule.base', 'Permanently delete this comment from history?'))) ?>);">
                                <?= Yii::t('EngagementPagesModule.base', 'Delete') ?>
                            </button>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
