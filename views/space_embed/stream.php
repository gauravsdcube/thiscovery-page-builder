<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\space\models\Space;

/** @var Space $space */
/** @var array $items */
/** @var string $spaceUrl */

$items = $items ?? [];
ob_start();
?>
<ul class="ep-space-list">
    <?php foreach ($items as $item): ?>
        <?php
        $commentCount = (int) ($item['comment_count'] ?? 0);
        $comments = $item['comments'] ?? [];
        ?>
        <li class="ep-space-list__item">
            <a class="ep-space-list__link" href="<?= Html::encode($item['url'] ?: $spaceUrl) ?>">
                <span class="ep-space-list__icon" aria-hidden="true"><i class="fa fa-comment-o"></i></span>
                <span class="ep-space-list__main">
                    <span class="ep-space-list__title"><?= Html::encode($item['title']) ?></span>
                    <?php if (!empty($item['summary'])): ?>
                        <span class="ep-space-list__summary"><?= Html::encode($item['summary']) ?></span>
                    <?php endif; ?>
                    <span class="ep-space-list__meta">
                        <?php if (!empty($item['author'])): ?>
                            <?= Html::encode($item['author']) ?>
                            ·
                        <?php endif; ?>
                        <?php if (!empty($item['created_at'])): ?>
                            <?= Html::encode(Yii::$app->formatter->asRelativeTime($item['created_at'])) ?>
                        <?php endif; ?>
                        <?php if ($commentCount > 0): ?>
                            ·
                            <span class="ep-space-list__comments-count">
                                <i class="fa fa-comments-o" aria-hidden="true"></i>
                                <?= Yii::t(
                                    'ThiscoveryPageBuilderModule.base',
                                    '{n,plural,=1{# comment} other{# comments}}',
                                    ['n' => $commentCount]
                                ) ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </span>
            </a>
            <?php if ($comments !== []): ?>
                <div class="ep-space-comments">
                    <?php foreach ($comments as $comment): ?>
                        <div class="ep-space-comments__item">
                            <div class="ep-space-comments__meta">
                                <?php if (!empty($comment['author'])): ?>
                                    <strong><?= Html::encode($comment['author']) ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($comment['created_at'])): ?>
                                    <span><?= Html::encode(Yii::$app->formatter->asRelativeTime($comment['created_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($comment['body'])): ?>
                                <div class="ep-space-comments__body"><?= Html::encode($comment['body']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($commentCount > count($comments)): ?>
                        <a class="ep-space-comments__more" href="<?= Html::encode($item['url'] ?: $spaceUrl) ?>">
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'View all comments') ?>
                            <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
<?php
$body = ob_get_clean();

echo $this->render('_panel', [
    'space' => $space,
    'kind' => 'stream',
    'heading' => Yii::t('ThiscoveryPageBuilderModule.base', 'Latest updates'),
    'spaceUrl' => $spaceUrl,
    'ctaLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open Space'),
    'bodyHtml' => $body,
    'isEmpty' => $items === [],
    'emptyText' => Yii::t('ThiscoveryPageBuilderModule.base', 'No posts yet.'),
]);
