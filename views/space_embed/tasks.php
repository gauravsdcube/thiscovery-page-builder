<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\space\models\Space;
use humhub\modules\tasks\models\Task;

/** @var Space $space */
/** @var Task[] $tasks */
/** @var string $spaceUrl */

$tasks = $tasks ?? [];
$statusLabels = [
    Task::STATUS_PENDING => Yii::t('ThiscoveryPageBuilderModule.base', 'Pending'),
    Task::STATUS_IN_PROGRESS => Yii::t('ThiscoveryPageBuilderModule.base', 'In progress'),
    Task::STATUS_PENDING_REVIEW => Yii::t('ThiscoveryPageBuilderModule.base', 'Review'),
    Task::STATUS_COMPLETED => Yii::t('ThiscoveryPageBuilderModule.base', 'Done'),
];

ob_start();
?>
<ul class="ep-space-list">
    <?php foreach ($tasks as $task): ?>
        <?php
        $status = (int) $task->status;
        $statusKey = match ($status) {
            Task::STATUS_IN_PROGRESS => 'progress',
            Task::STATUS_PENDING_REVIEW => 'review',
            Task::STATUS_COMPLETED => 'done',
            default => 'pending',
        };
        $when = '';
        try {
            $when = $task->schedule?->getFormattedDateTime() ?? '';
        } catch (\Throwable $e) {
        }
        ?>
        <li class="ep-space-list__item">
            <a class="ep-space-list__link" href="<?= Html::encode($task->getUrl()) ?>">
                <span class="ep-space-list__icon ep-space-list__icon--<?= Html::encode($statusKey) ?>" aria-hidden="true">
                    <i class="fa fa-check-square-o"></i>
                </span>
                <span class="ep-space-list__main">
                    <span class="ep-space-list__title"><?= Html::encode($task->title) ?></span>
                    <span class="ep-space-list__meta">
                        <span class="ep-space-pill ep-space-pill--<?= Html::encode($statusKey) ?>">
                            <?= Html::encode($statusLabels[$status] ?? $statusLabels[Task::STATUS_PENDING]) ?>
                        </span>
                        <?php if ($when !== ''): ?>
                            <span><?= Html::encode($when) ?></span>
                        <?php endif; ?>
                    </span>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php
$body = ob_get_clean();

echo $this->render('_panel', [
    'space' => $space,
    'kind' => 'tasks',
    'heading' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open tasks'),
    'spaceUrl' => $spaceUrl,
    'ctaLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'View tasks'),
    'bodyHtml' => $body,
    'isEmpty' => $tasks === [],
    'emptyText' => Yii::t('ThiscoveryPageBuilderModule.base', 'No tasks to show.'),
]);
