<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\space\models\Space;

/** @var Space $space */
/** @var CalendarEventIF[] $entries */
/** @var string $spaceUrl */

$entries = $entries ?? [];
ob_start();
?>
<ul class="ep-space-list">
    <?php foreach ($entries as $entry): ?>
        <?php
        $when = '';
        try {
            $when = (new CalendarDateFormatter(['calendarItem' => $entry]))->getFormattedTime('medium');
        } catch (\Throwable $e) {
        }
        $color = method_exists($entry, 'getColor') ? (string) ($entry->getColor() ?: '') : '';
        if ($color === '' && isset($entry->color)) {
            $color = (string) $entry->color;
        }
        ?>
        <li class="ep-space-list__item">
            <a class="ep-space-list__link" href="<?= Html::encode($entry->getUrl()) ?>"
               <?php if ($color !== ''): ?>style="--ep-space-accent: <?= Html::encode($color) ?>"<?php endif; ?>>
                <span class="ep-space-list__icon ep-space-list__icon--event" aria-hidden="true">
                    <i class="fa fa-calendar"></i>
                </span>
                <span class="ep-space-list__main">
                    <span class="ep-space-list__title"><?= Html::encode($entry->getTitle()) ?></span>
                    <?php if ($when !== ''): ?>
                        <span class="ep-space-list__meta"><?= Html::encode($when) ?></span>
                    <?php endif; ?>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php
$body = ob_get_clean();

echo $this->render('_panel', [
    'space' => $space,
    'kind' => 'calendar',
    'heading' => Yii::t('ThiscoveryPageBuilderModule.base', 'Upcoming events'),
    'spaceUrl' => $spaceUrl,
    'ctaLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open calendar'),
    'bodyHtml' => $body,
    'isEmpty' => $entries === [],
    'emptyText' => Yii::t('ThiscoveryPageBuilderModule.base', 'No upcoming events.'),
]);
