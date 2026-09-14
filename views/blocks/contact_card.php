<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\ContactCardBlock;
use humhub\modules\thiscoveryPageBuilder\helpers\FileHelper;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var ContactCardBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$people = $settings['people'] ?? [];
if ($people === [] && ($settings['title'] ?? '') === '' && ($settings['intro'] ?? '') === '') {
    return;
}
?>
<section class="ep-block ep-contact-card">
    <?php if (($settings['title'] ?? '') !== ''): ?>
        <h2><?= Html::encode($settings['title']) ?></h2>
    <?php endif; ?>
    <?php if (($settings['intro'] ?? '') !== ''): ?>
        <p class="ep-contact-card__intro"><?= Html::encode($settings['intro']) ?></p>
    <?php endif; ?>
    <?php if ($people !== []): ?>
        <ul class="ep-contact-card__grid">
            <?php foreach ($people as $person): ?>
                <?php
                $name = (string) ($person['name'] ?? '');
                $photoUrl = FileHelper::url($person['image_guid'] ?? null);
                $alt = (string) ($person['image_alt'] ?? '');
                if ($alt === '') {
                    $alt = $name !== '' ? $name : Yii::t('ThiscoveryPageBuilderModule.base', 'Portrait');
                }
                ?>
                <li class="ep-contact-card__person">
                    <div class="ep-contact-card__photo-wrap">
                        <?php if ($photoUrl): ?>
                            <?= Html::img($photoUrl, [
                                'alt' => $alt,
                                'class' => 'ep-contact-card__photo',
                                'loading' => 'lazy',
                            ]) ?>
                        <?php else: ?>
                            <span class="ep-contact-card__photo ep-contact-card__photo--empty" aria-hidden="true">
                                <?= Html::encode(ContactCardBlock::initials($name)) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($name !== ''): ?>
                        <h3 class="ep-contact-card__name"><?= Html::encode($name) ?></h3>
                    <?php endif; ?>
                    <?php if (($person['role'] ?? '') !== ''): ?>
                        <p class="ep-contact-card__role"><?= Html::encode($person['role']) ?></p>
                    <?php endif; ?>
                    <?php if (($person['organisation'] ?? '') !== ''): ?>
                        <p class="ep-contact-card__org"><?= Html::encode($person['organisation']) ?></p>
                    <?php endif; ?>
                    <?php if (($person['email'] ?? '') !== ''): ?>
                        <p class="ep-contact-card__email">
                            <a href="mailto:<?= Html::encode($person['email']) ?>"><?= Html::encode($person['email']) ?></a>
                        </p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
