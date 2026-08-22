<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\cfiles\models\File as CFile;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryPageBuilder\helpers\SpaceWidgets;

/** @var Space $space */
/** @var Folder|null $folder */
/** @var Folder[] $folders */
/** @var CFile[] $files */
/** @var string $spaceUrl */

$folders = $folders ?? [];
$files = $files ?? [];
$isEmpty = $folders === [] && $files === [];

ob_start();
?>
<div class="ep-space-files">
    <?php if ($folders !== []): ?>
        <ul class="ep-space-list ep-space-list--compact">
            <?php foreach ($folders as $child): ?>
                <li class="ep-space-list__item">
                    <a class="ep-space-list__link" href="<?= Html::encode($child->getUrl()) ?>">
                        <span class="ep-space-list__icon" aria-hidden="true"><i class="fa fa-folder-o"></i></span>
                        <span class="ep-space-list__main">
                            <span class="ep-space-list__title"><?= Html::encode($child->title) ?></span>
                            <span class="ep-space-list__meta"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Folder') ?></span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($files !== []): ?>
        <ul class="ep-space-list">
            <?php foreach ($files as $file): ?>
                <?php
                $url = SpaceWidgets::fileDownloadUrl($file);
                $size = SpaceWidgets::fileSizeLabel($file);
                $ext = strtoupper((string) pathinfo((string) $file->title, PATHINFO_EXTENSION));
                ?>
                <li class="ep-space-list__item">
                    <a class="ep-space-list__link" href="<?= Html::encode($url) ?>"<?= $url !== '#' ? ' download' : '' ?>>
                        <span class="ep-space-list__icon" aria-hidden="true"><i class="fa fa-file-o"></i></span>
                        <span class="ep-space-list__main">
                            <span class="ep-space-list__title"><?= Html::encode($file->title) ?></span>
                            <span class="ep-space-list__meta">
                                <?php if ($ext !== ''): ?>
                                    <span class="ep-space-pill"><?= Html::encode($ext) ?></span>
                                <?php endif; ?>
                                <?php if ($size !== ''): ?>
                                    <span><?= Html::encode($size) ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php
$body = ob_get_clean();

echo $this->render('_panel', [
    'space' => $space,
    'kind' => 'files',
    'heading' => Yii::t('ThiscoveryPageBuilderModule.base', 'Files'),
    'spaceUrl' => $spaceUrl,
    'ctaLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open files'),
    'bodyHtml' => $body,
    'isEmpty' => $isEmpty,
    'emptyText' => Yii::t('ThiscoveryPageBuilderModule.base', 'No files to show.'),
]);
