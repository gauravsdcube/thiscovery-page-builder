<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\helpers\Html;

/** @var string|null $text */
/** @var string|null $html */

$text = trim((string) ($text ?? ''));
$html = $html ?? null;
if ($html === null && $text === '') {
    return;
}
$id = 'ep-guide-' . str_replace('.', '', uniqid('', true));
$label = Yii::t('ThiscoveryPageBuilderModule.base', 'Guidance');
?>
<div class="ep-guide">
    <button type="button" class="ep-guide__toggle" data-ep-guide-toggle
            aria-expanded="false" aria-controls="<?= Html::encode($id) ?>"
            title="<?= Html::encode($label) ?>" aria-label="<?= Html::encode($label) ?>">
        <i class="fa fa-question-circle" aria-hidden="true"></i>
    </button>
    <div id="<?= Html::encode($id) ?>" class="ep-guide__panel" hidden>
        <?php if ($html !== null): ?>
            <?= $html ?>
        <?php else: ?>
            <p><?= Html::encode($text) ?></p>
        <?php endif; ?>
    </div>
</div>
