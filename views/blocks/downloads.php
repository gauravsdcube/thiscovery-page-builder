<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\DownloadsBlock;
use humhub\modules\thiscoveryPageBuilder\helpers\FileHelper;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var DownloadsBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$items = $settings['items'] ?? [];
if ($items === []) {
    return;
}
?>
<section class="ep-block ep-downloads">
    <h2><?= Html::encode($settings['title'] ?: Yii::t('ThiscoveryPageBuilderModule.base', 'Documents')) ?></h2>
    <ul>
        <?php foreach ($items as $item): ?>
            <?php
            $url = FileHelper::downloadUrl($item['file_guid'] ?? null)
                ?: (($item['url'] ?? '') !== '' ? $item['url'] : null);
            if (!$url) {
                continue;
            }
            $label = ($item['label'] ?? '') !== '' ? $item['label'] : $url;
            $alt = ($item['alt'] ?? '') !== '' ? $item['alt'] : $label;
            ?>
            <li>
                <a href="<?= Html::encode($url) ?>"
                   target="_blank"
                   rel="noopener"
                   aria-label="<?= Html::encode($alt) ?>"
                   title="<?= Html::encode($alt) ?>">
                    <i class="fa fa-file-o" aria-hidden="true"></i>
                    <?= Html::encode($label) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
