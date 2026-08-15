<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/**
 * Extra editor fields for Phase 2+ block types.
 *
 * @var string $type
 * @var array $settings
 * @var string $namePrefix
 * @var string $safeIndex
 * @var array $formOptions
 * @var \humhub\modules\thiscoveryPageBuilder\models\EngagementPage|null $page
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryEditor\widgets\EditorField;

$page = $page ?? null;

if ($type === 'phases'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Project phases')) ?>"
               data-ep-card-title-source>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Display style') ?></label>
        <select class="form-control" name="<?= $namePrefix ?>[settings][style]" style="max-width:280px">
            <option value="linear" <?= ($settings['style'] ?? 'linear') === 'linear' ? 'selected' : '' ?>>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Linear timeline') ?>
            </option>
            <option value="plan" <?= ($settings['style'] ?? '') === 'plan' ? 'selected' : '' ?>>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Plan / roadmap') ?>
            </option>
        </select>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Linear stacks phases vertically. Plan shows them as a horizontal roadmap.') ?>
        </div>
    </div>
    <div class="ep-repeat" data-ep-repeat="phases">
        <div data-ep-repeat-items>
            <?php
            $items = $settings['items'] ?? [
                ['label' => '', 'description' => '', 'status' => 'upcoming'],
            ];
            if ($items === []) {
                $items = [['label' => '', 'description' => '', 'status' => 'upcoming']];
            }
            foreach ($items as $j => $item) {
                echo $this->render('_item_phase', [
                    'namePrefix' => $namePrefix . '[settings][items][' . $j . ']',
                    'item' => $item,
                    'safeIndex' => $safeIndex . '-p' . $j,
                ]);
            }
            ?>
        </div>
        <button type="button" class="btn btn-sm btn-light" data-ep-repeat-add="phases">
            <i class="fa fa-plus"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Add phase') ?>
        </button>
    </div>

<?php elseif ($type === 'events'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Upcoming events')) ?>"
               data-ep-card-title-source>
    </div>
    <div class="ep-repeat" data-ep-repeat="events">
        <div data-ep-repeat-items>
            <?php
            $items = $settings['items'] ?? [['title' => '', 'date' => '', 'time' => '', 'location' => '', 'url' => '', 'cta_label' => '']];
            if ($items === []) {
                $items = [['title' => '', 'date' => '', 'time' => '', 'location' => '', 'url' => '', 'cta_label' => '']];
            }
            foreach ($items as $j => $item) {
                echo $this->render('_item_event', [
                    'namePrefix' => $namePrefix . '[settings][items][' . $j . ']',
                    'item' => $item,
                ]);
            }
            ?>
        </div>
        <button type="button" class="btn btn-sm btn-light" data-ep-repeat-add="events">
            <i class="fa fa-plus"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Add event') ?>
        </button>
    </div>

<?php elseif ($type === 'team'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Section title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Meet the team')) ?>"
               data-ep-card-title-source>
        <div class="ep-hint text-muted"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Editable label — e.g. Meet the team or Your contacts. Defaults to the left column.') ?></div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Intro') ?>
            <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
        </label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][intro]"
               value="<?= Html::encode($settings['intro'] ?? '') ?>">
    </div>
    <div class="ep-repeat" data-ep-repeat="team">
        <div data-ep-repeat-items>
            <?php
            $people = $settings['people'] ?? [['name' => '', 'role' => '', 'email' => '', 'phone' => '', 'bio' => '']];
            if ($people === []) {
                $people = [['name' => '', 'role' => '', 'email' => '', 'phone' => '', 'bio' => '']];
            }
            foreach ($people as $j => $person) {
                echo $this->render('_item_person', [
                    'namePrefix' => $namePrefix . '[settings][people][' . $j . ']',
                    'item' => $person,
                ]);
            }
            ?>
        </div>
        <button type="button" class="btn btn-sm btn-light" data-ep-repeat-add="team">
            <i class="fa fa-plus"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Add contact') ?>
        </button>
    </div>

<?php elseif ($type === 'contact'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Section title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Contact us')) ?>"
               data-ep-card-title-source>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Single contact or enquiry details. Defaults to the left column.') ?>
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Organisation') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][organisation]"
               value="<?= Html::encode($settings['organisation'] ?? '') ?>">
    </div>
    <div class="row g-2">
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Name') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][name]"
                   value="<?= Html::encode($settings['name'] ?? '') ?>">
        </div>
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Role') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][role]"
                   value="<?= Html::encode($settings['role'] ?? '') ?>">
        </div>
    </div>
    <div class="row g-2">
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Email') ?></label>
            <input type="email" class="form-control" name="<?= $namePrefix ?>[settings][email]"
                   value="<?= Html::encode($settings['email'] ?? '') ?>">
        </div>
        <div class="col-md-6 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Phone') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][phone]"
                   value="<?= Html::encode($settings['phone'] ?? '') ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Address') ?></label>
        <textarea class="form-control" rows="2" name="<?= $namePrefix ?>[settings][address]"><?= Html::encode($settings['address'] ?? '') ?></textarea>
    </div>
    <div class="row g-2">
        <div class="col-md-7 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Website URL') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][website]"
                   value="<?= Html::encode($settings['website'] ?? '') ?>">
        </div>
        <div class="col-md-5 form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Website label') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][website_label]"
                   value="<?= Html::encode($settings['website_label'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Visit website')) ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Notes') ?>
            <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
        </label>
        <textarea class="form-control" rows="2" name="<?= $namePrefix ?>[settings][notes]"><?= Html::encode($settings['notes'] ?? '') ?></textarea>
    </div>
    <div class="form-check mb-2">
        <input type="hidden" name="<?= $namePrefix ?>[settings][show_email_link]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][show_email_link]"
               id="ep-contact-mailto-<?= Html::encode($safeIndex) ?>"
            <?= !isset($settings['show_email_link']) || !empty($settings['show_email_link']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-contact-mailto-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Show email link and Send email button') ?>
        </label>
    </div>

<?php elseif ($type === 'updates'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Get updates')) ?>"
               data-ep-card-title-source>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Intro text') ?></label>
        <textarea class="form-control" rows="2" name="<?= $namePrefix ?>[settings][intro]"><?= Html::encode($settings['intro'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Button label') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][button_label]"
               value="<?= Html::encode($settings['button_label'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Subscribe')) ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Success message') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][success_message]"
               value="<?= Html::encode($settings['success_message'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Thanks — we will keep you updated.')) ?>">
    </div>

<?php elseif ($type === 'comments'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Comments')) ?>"
               data-ep-card-title-source>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Intro text') ?></label>
        <textarea class="form-control" rows="2" name="<?= $namePrefix ?>[settings][intro]"><?= Html::encode($settings['intro'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Share your thoughts. Comments are moderated before they appear.')) ?></textarea>
    </div>
    <div class="form-check mb-2">
        <input type="hidden" name="<?= $namePrefix ?>[settings][allow_guests]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][allow_guests]"
               id="ep-comments-guests-<?= Html::encode($safeIndex) ?>"
            <?= !isset($settings['allow_guests']) || !empty($settings['allow_guests']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-comments-guests-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Allow guests to comment (CAPTCHA required)') ?>
        </label>
    </div>
    <div class="form-check mb-2">
        <input type="hidden" name="<?= $namePrefix ?>[settings][ask_name]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][ask_name]"
               id="ep-comments-name-<?= Html::encode($safeIndex) ?>"
            <?= !isset($settings['ask_name']) || !empty($settings['ask_name']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-comments-name-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Ask for name') ?>
        </label>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'If unchecked, comments are submitted anonymously.') ?>
        </div>
    </div>
    <div class="form-check mb-2">
        <input type="hidden" name="<?= $namePrefix ?>[settings][require_email]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][require_email]"
               id="ep-comments-email-<?= Html::encode($safeIndex) ?>"
            <?= !isset($settings['require_email']) || !empty($settings['require_email']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-comments-email-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Ask for email') ?>
        </label>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'If unchecked, the email field is hidden. If checked, guests must enter an email.') ?>
        </div>
    </div>
    <div class="form-check mb-2">
        <input type="hidden" name="<?= $namePrefix ?>[settings][moderate_guests]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][moderate_guests]"
               id="ep-comments-mod-<?= Html::encode($safeIndex) ?>"
            <?= !isset($settings['moderate_guests']) || !empty($settings['moderate_guests']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-comments-mod-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Hold guest comments for moderation') ?>
        </label>
    </div>
    <div class="form-check mb-2">
        <input type="hidden" name="<?= $namePrefix ?>[settings][show_comments]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][show_comments]"
               id="ep-comments-show-<?= Html::encode($safeIndex) ?>"
            <?= !isset($settings['show_comments']) || !empty($settings['show_comments']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-comments-show-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Show approved comments on the page') ?>
        </label>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'If unchecked, people can still submit comments for moderation, but approved comments are not listed publicly.') ?>
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Success message') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][success_message]"
               value="<?= Html::encode($settings['success_message'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Thanks — your comment has been submitted for review.')) ?>">
    </div>

<?php elseif ($type === 'accordion'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Frequently asked questions')) ?>"
               data-ep-card-title-source>
    </div>
    <div class="ep-repeat" data-ep-repeat="accordion">
        <div data-ep-repeat-items>
            <?php
            $items = $settings['items'] ?? [['heading' => '', 'body' => '']];
            if ($items === []) {
                $items = [['heading' => '', 'body' => '']];
            }
            foreach ($items as $j => $item) {
                echo $this->render('_item_accordion', [
                    'namePrefix' => $namePrefix . '[settings][items][' . $j . ']',
                    'item' => $item,
                    'safeIndex' => $safeIndex . '-a' . $j,
                ]);
            }
            ?>
        </div>
        <button type="button" class="btn btn-sm btn-light" data-ep-repeat-add="accordion">
            <i class="fa fa-plus"></i> <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Add item') ?>
        </button>
    </div>

<?php elseif ($type === 'callout'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? '') ?>" data-ep-card-title-source>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Tone') ?></label>
        <select class="form-control" name="<?= $namePrefix ?>[settings][tone]">
            <?php foreach ([
                'info' => Yii::t('ThiscoveryPageBuilderModule.base', 'Information'),
                'success' => Yii::t('ThiscoveryPageBuilderModule.base', 'Success'),
                'warning' => Yii::t('ThiscoveryPageBuilderModule.base', 'Warning'),
                'neutral' => Yii::t('ThiscoveryPageBuilderModule.base', 'Neutral'),
            ] as $value => $label): ?>
                <option value="<?= Html::encode($value) ?>" <?= ($settings['tone'] ?? 'info') === $value ? 'selected' : '' ?>>
                    <?= Html::encode($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Body') ?></label>
        <div class="ep-rich-editor" data-ep-rich-editor>
            <?= EditorField::widget([
                'id' => 'ep-callout-body-' . $safeIndex,
                'name' => $namePrefix . '[settings][body]',
                'value' => (string) ($settings['body'] ?? ''),
                'height' => 220,
                'profile' => 'simple',
            ]) ?>
        </div>
    </div>

<?php elseif ($type === 'image'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Image') ?></label>
        <?= $this->render('_upload_field', [
            'inputName' => $namePrefix . '[settings][image_guid]',
            'guid' => $settings['image_guid'] ?? '',
            'widgetId' => 'ep-img-' . $safeIndex,
            'imagesOnly' => true,
            'buttonLabel' => Yii::t('ThiscoveryPageBuilderModule.base', 'Upload image'),
            'page' => $page,
        ]) ?>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Image alt text') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][alt]"
               value="<?= Html::encode($settings['alt'] ?? '') ?>" data-ep-card-title-source>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Caption') ?>
            <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
        </label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][caption]"
               value="<?= Html::encode($settings['caption'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Link URL') ?>
            <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
        </label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][link_url]"
               value="<?= Html::encode($settings['link_url'] ?? '') ?>">
    </div>
    <div class="form-check mb-2">
        <input type="hidden" name="<?= $namePrefix ?>[settings][use_as_card_image]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][use_as_card_image]"
               id="ep-card-img-<?= Html::encode($safeIndex) ?>"
               data-ep-card-image
            <?= !empty($settings['use_as_card_image']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-card-img-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Use as collection card image') ?>
        </label>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Shown on collection listings and the public directory. If none is selected, the hero image is used.') ?>
        </div>
    </div>

<?php elseif ($type === 'collection' || $type === 'directory'): ?>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Section title') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][title]"
               value="<?= Html::encode($settings['title'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Open for feedback')) ?>"
               data-ep-card-title-source>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Source') ?></label>
        <select class="form-control" name="<?= $namePrefix ?>[settings][source]" style="max-width:280px"
                data-ep-collection-source>
            <?php foreach (\humhub\modules\thiscoveryPageBuilder\blocks\CollectionBlock::sourceOptions() as $value => $label): ?>
                <option value="<?= Html::encode($value) ?>" <?= ($settings['source'] ?? 'pages') === $value ? 'selected' : '' ?>>
                    <?= Html::encode($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="ep-hint text-muted">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Choose what this collection lists. Use Position (above) to centre the cards.') ?>
        </div>
    </div>
    <div class="form-group">
        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Empty state message') ?></label>
        <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][empty_message]"
               value="<?= Html::encode($settings['empty_message'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'Nothing to show yet.')) ?>">
    </div>
    <div class="form-check mb-2" data-ep-collection-pages-only<?= ($settings['source'] ?? 'pages') === 'pages' ? '' : ' hidden' ?>>
        <input type="hidden" name="<?= $namePrefix ?>[settings][show_featured_first]" value="0">
        <input class="form-check-input" type="checkbox" value="1"
               name="<?= $namePrefix ?>[settings][show_featured_first]"
               id="ep-dir-feat-<?= Html::encode($safeIndex) ?>"
            <?= !isset($settings['show_featured_first']) || !empty($settings['show_featured_first']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ep-dir-feat-<?= Html::encode($safeIndex) ?>">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Show featured pages first') ?>
        </label>
    </div>
    <div data-ep-collection-calendar-only<?= ($settings['source'] ?? 'pages') === 'calendar' ? '' : ' hidden' ?>>
        <div class="form-group">
            <label class="ep-label" for="ep-event-limit-<?= Html::encode($safeIndex) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Number of upcoming events') ?>
            </label>
            <input type="number" class="form-control" style="max-width:120px"
                   id="ep-event-limit-<?= Html::encode($safeIndex) ?>"
                   name="<?= $namePrefix ?>[settings][event_limit]"
                   min="1" max="30"
                   value="<?= (int) ($settings['event_limit'] ?? 5) ?>">
        </div>
        <div class="form-group">
            <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Calendar link label') ?></label>
            <input type="text" class="form-control" name="<?= $namePrefix ?>[settings][more_label]"
                   value="<?= Html::encode($settings['more_label'] ?? Yii::t('ThiscoveryPageBuilderModule.base', 'View calendar')) ?>">
            <div class="ep-hint text-muted">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Shown under the events as a link to the full calendar.') ?>
            </div>
        </div>
    </div>
    <p class="ep-hint text-muted">
        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Pages: published items with “Show in public directory”. Forms: open global Thiscovery Forms. Spaces: visible spaces. Calendar: upcoming events (requires the Calendar module).') ?>
    </p>
<?php endif; ?>
