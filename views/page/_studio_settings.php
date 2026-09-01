<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageHome;
use humhub\modules\thiscoveryEditor\widgets\EditorField;

/** @var ContentContainerActiveRecord|null $contentContainer */
/** @var EngagementPage $page */
/** @var bool $isDirectory */
/** @var bool $isTemplate */
/** @var array $collectionOptions */
/** @var array $spaceOptions */
/** @var array $groupOptions */
/** @var PageHome[] $pageHomes */
/** @var string $publicPrefix */
/** @var string|null $parentSlug */

$view = $this;
$guide = static function (string $text) use ($view) {
    return $view->render('_setting_guide', ['text' => $text]);
};
?>
<div class="ep-studio__settings">
    <p class="ep-studio__hint">
        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Settings are grouped into collapsible sections. Basics opens first. Use Expand all / Collapse all as needed. Click ? next to a label for a short explanation.') ?>
    </p>
    <div class="ep-set-toolbar">
        <button type="button" class="btn btn-sm btn-light" data-ep-acc-all="open"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Expand all') ?></button>
        <button type="button" class="btn btn-sm btn-light" data-ep-acc-all="close"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Collapse all') ?></button>
    </div>

    <details class="ep-set-acc" open>
        <summary>
            <span class="ep-set-acc__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Basics') ?></span>
            <span class="ep-set-acc__summary"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title, URL, summary, status, width') ?></span>
        </summary>
        <div class="ep-set-acc__body">
            <div class="form-group ep-field">
                <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Title') ?></label>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Shown at the top of the public page and in admin lists. Keep it short and clear.')) ?>
                <input type="text" class="form-control form-control-lg" name="EngagementPage[title]" value="<?= Html::encode($page->title) ?>" required>
                <?php if ($page->hasErrors('title')): ?>
                    <div class="help-block help-block-error"><?= Html::encode(implode(' ', $page->getErrors('title'))) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group ep-field">
                <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'URL slug') ?></label>
                <?php if ($page->isCollection() || $page->isTopLevel()): ?>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Top-level public URL for this collection or page (for example /consultations or /about). Use lowercase letters, numbers, and hyphens.')) ?>
                    <div class="input-group">
                        <span class="input-group-text">/</span>
                        <input type="text" class="form-control" name="EngagementPage[slug]"
                               value="<?= Html::encode($page->slug ?: ($page->isCollection() ? EngagementPage::DEFAULT_PUBLIC_PREFIX : '')) ?>"
                               required
                               data-ep-home-slug>
                    </div>
                <?php else: ?>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Custom URL path under the parent collection. Use lowercase letters, numbers, and hyphens.')) ?>
                    <div class="input-group">
                        <span class="input-group-text">/<?= Html::encode($parentSlug ?: $publicPrefix) ?>/</span>
                        <input type="text" class="form-control" name="EngagementPage[slug]" value="<?= Html::encode($page->slug) ?>" required>
                    </div>
                <?php endif; ?>
                <?php if ($page->hasErrors('slug')): ?>
                    <div class="help-block help-block-error"><?= Html::encode(implode(' ', $page->getErrors('slug'))) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!$isTemplate && $page->hasAttribute('is_collection')): ?>
                <?php if ($page->isDirectoryHome()): ?>
                    <input type="hidden" name="EngagementPage[is_collection]" value="1">
                    <input type="hidden" name="EngagementPage[is_directory]" value="1">
                    <input type="hidden" name="EngagementPage[parent_id]" value="">
                <?php elseif ($page->isCollection()): ?>
                    <input type="hidden" name="EngagementPage[is_collection]" value="1">
                    <input type="hidden" name="EngagementPage[parent_id]" value="">
                    <p class="ep-hint text-muted">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'This is a collection. Child pages can nest under its slug.') ?>
                    </p>
                <?php else: ?>
                    <input type="hidden" name="EngagementPage[is_collection]" value="0">
                    <div class="form-group ep-field">
                        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Parent collection') ?></label>
                        <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Choose a parent collection to nest this page under its URL, or leave empty for a top-level page.')) ?>
                        <select class="form-control" name="EngagementPage[parent_id]" style="max-width:420px">
                            <?php foreach ($collectionOptions as $value => $labelOpt): ?>
                                <option value="<?= Html::encode($value) ?>" <?= (string) ($page->parent_id ?? '') === (string) $value ? 'selected' : '' ?>>
                                    <?= Html::encode($labelOpt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($page->hasErrors('parent_id')): ?>
                            <div class="help-block help-block-error"><?= Html::encode(implode(' ', $page->getErrors('parent_id'))) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="form-group ep-field">
                <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Summary') ?>
                    <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
                </label>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Short description used in directories and cards. Keep it to one or two sentences.')) ?>
                <div class="ep-rich-editor" data-ep-rich-editor>
                    <?= EditorField::widget([
                        'id' => 'ep-page-summary',
                        'name' => 'EngagementPage[summary]',
                        'value' => (string) $page->summary,
                        'placeholder' => Yii::t('ThiscoveryPageBuilderModule.base', 'Short summary for directories…'),
                        'height' => 180,
                        'profile' => 'simple',
                    ]) ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4 form-group mb-0 ep-field">
                    <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Status') ?></label>
                    <?php if ($isTemplate): ?>
                        <input type="hidden" name="EngagementPage[status]" value="<?= (int) EngagementPage::STATUS_DRAFT ?>">
                        <input type="hidden" name="EngagementPage[is_template]" value="1">
                        <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Templates stay as drafts and are not published publicly.')) ?>
                        <p class="ep-hint text-muted mb-0">
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Templates stay as drafts and are not published publicly.') ?>
                        </p>
                    <?php else: ?>
                        <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Draft is only for managers and preview. Published pages are visible according to Who can view. Archived hides the page from the public site.')) ?>
                        <select class="form-control" name="EngagementPage[status]">
                            <?php foreach (EngagementPage::statusOptions() as $value => $label): ?>
                                <option value="<?= (int) $value ?>" <?= (int) $page->status === (int) $value ? 'selected' : '' ?>>
                                    <?= Html::encode($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 form-group mb-0 ep-field">
                    <label class="ep-label" for="ep-width-select-settings">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page width') ?>
                    </label>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Controls how wide this page appears publicly. Wide is 1440px; Full uses the browser width with side padding.')) ?>
                    <select id="ep-width-select-settings" class="form-control" name="EngagementPage[page_width]"
                            data-ep-page-width>
                        <?php foreach (EngagementPage::pageWidthOptions() as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>" <?= $page->getPageWidthKey() === $value ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 form-group mb-0 ep-field">
                    <?php if (!$isTemplate): ?>
                        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Who can view') ?></label>
                        <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Public pages are open to guests. Community members only requires sign-in.')) ?>
                        <select class="form-control" name="EngagementPage[audience]">
                            <?php foreach (EngagementPage::audienceOptions() as $value => $label): ?>
                                <option value="<?= Html::encode($value) ?>" <?= $page->getAudienceKey() === $value ? 'selected' : '' ?>>
                                    <?= Html::encode($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="EngagementPage[audience]" value="<?= Html::encode(EngagementPage::AUDIENCE_PUBLIC) ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </details>

    <?php if (!$isTemplate && $page->hasAttribute('bound_space_id')): ?>
    <details class="ep-set-acc">
        <summary>
            <span class="ep-set-acc__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Bound Space') ?></span>
            <span class="ep-set-acc__summary"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Space used by stream, tasks, files, gallery, calendar') ?></span>
        </summary>
        <div class="ep-set-acc__body">
            <div class="form-group ep-field mb-0">
                <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Bound Space') ?></label>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Set once for this page. Space stream, tasks, files, gallery, and calendar widgets all use this Space.')) ?>
                <select class="form-control" name="EngagementPage[bound_space_id]" style="max-width:420px">
                    <?php foreach ($spaceOptions as $value => $labelOpt): ?>
                        <option value="<?= Html::encode($value) ?>" <?= (string) ($page->bound_space_id ?? '') === (string) $value ? 'selected' : '' ?>>
                            <?= Html::encode($labelOpt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <details class="ep-set-acc">
        <summary>
            <span class="ep-set-acc__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Directory listing') ?></span>
            <span class="ep-set-acc__summary"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Listing, featured, category, closing date') ?></span>
        </summary>
        <div class="ep-set-acc__body">
            <?php if ($isTemplate): ?>
                <input type="hidden" name="EngagementPage[listed]" value="0">
                <input type="hidden" name="EngagementPage[featured]" value="0">
                <p class="ep-hint text-muted mb-0">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Templates are never listed on the public homepage. Use “Create from template” on the page list.') ?>
                </p>
            <?php elseif ($isDirectory): ?>
                <input type="hidden" name="EngagementPage[listed]" value="0">
                <input type="hidden" name="EngagementPage[featured]" value="0">
                <input type="hidden" name="EngagementPage[is_directory]" value="1">
                <p class="ep-hint text-muted mb-0">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Add a Collection section in the builder to list pages, forms, or spaces. This homepage is never listed as a card on itself.') ?>
                </p>
            <?php else: ?>
                <div class="ep-check-setting">
                    <div>
                        <input type="hidden" name="EngagementPage[listed]" value="0">
                        <label class="form-check-label">
                            <input class="form-check-input" type="checkbox" value="1" name="EngagementPage[listed]" id="ep-listed"
                                <?= !empty($page->listed) || $page->isNewRecord ? 'checked' : '' ?>>
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Show in public directory') ?>
                        </label>
                    </div>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'When enabled, this page can appear in Collection blocks that list pages.')) ?>
                </div>
                <div class="ep-check-setting">
                    <div>
                        <input type="hidden" name="EngagementPage[featured]" value="0">
                        <label class="form-check-label">
                            <input class="form-check-input" type="checkbox" value="1" name="EngagementPage[featured]" id="ep-featured"
                                <?= !empty($page->featured) ? 'checked' : '' ?>>
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Featured on directory') ?>
                        </label>
                    </div>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Featured pages are sorted first in Collection blocks that prefer featured items.')) ?>
                </div>
            <?php endif; ?>

            <?php if (!$isDirectory): ?>
                <div class="form-group ep-field">
                    <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Category') ?>
                        <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
                    </label>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Optional label shown on directory cards (for example Consultation or Survey).')) ?>
                    <input type="text" class="form-control" name="EngagementPage[category]"
                           value="<?= Html::encode((string) $page->category) ?>"
                           placeholder="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'e.g. Consultation, Survey') ?>"
                           style="max-width:320px">
                </div>
                <div class="form-group ep-field mb-0">
                    <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Closes at') ?>
                        <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
                    </label>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Optional closing date shown on directory cards. Leave empty if there is no deadline.')) ?>
                    <input type="date" class="form-control" name="EngagementPage[closes_at]"
                           value="<?= Html::encode($page->closes_at ? date('Y-m-d', strtotime((string) $page->closes_at)) : '') ?>"
                           style="max-width:220px">
                </div>
            <?php endif; ?>
        </div>
    </details>

    <?php if (!$isTemplate && $page->hasAttribute('show_in_top_menu') && $contentContainer === null): ?>
    <?php $navManaged = class_exists(\humhub\modules\thiscoveryNavigation\helpers\Navigation::class)
        && \humhub\modules\thiscoveryNavigation\helpers\Navigation::isActive(); ?>
    <details class="ep-set-acc">
        <summary>
            <span class="ep-set-acc__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Navigation') ?></span>
            <span class="ep-set-acc__summary"><?= $navManaged
                ? Yii::t('ThiscoveryPageBuilderModule.base', 'Managed in Site navigation')
                : Yii::t('ThiscoveryPageBuilderModule.base', 'Top menu label, order, and visibility') ?></span>
        </summary>
        <div class="ep-set-acc__body">
            <?php if ($navManaged): ?>
                <p class="help-block mb-0">
                    <?= Yii::t(
                        'ThiscoveryPageBuilderModule.base',
                        'This page can be added to the site top bar in <a href="{url}">Site navigation</a>.',
                        ['url' => \yii\helpers\Url::to(['/thiscovery-navigation/admin/index'])]
                    ) ?>
                </p>
            <?php else: ?>
            <div class="ep-check-setting">
                <div>
                    <input type="hidden" name="EngagementPage[show_in_top_menu]" value="0">
                    <label class="form-check-label">
                        <input class="form-check-input" type="checkbox" value="1" name="EngagementPage[show_in_top_menu]" id="ep-top-menu"
                            <?= !empty($page->show_in_top_menu) ? 'checked' : '' ?>>
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Show in top menu') ?>
                    </label>
                </div>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Adds this page to the site top navigation when enabled.')) ?>
            </div>
            <div class="form-group ep-field">
                <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Menu label') ?></label>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Text shown in the top menu. Defaults to the page title if left as the title.')) ?>
                <input type="text" class="form-control" name="EngagementPage[top_menu_label]"
                       value="<?= Html::encode((string) ($page->top_menu_label ?: $page->title)) ?>"
                       style="max-width:280px">
            </div>
            <div class="form-group ep-field">
                <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Menu order') ?></label>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Lower numbers appear earlier in the top menu.')) ?>
                <input type="number" class="form-control" name="EngagementPage[top_menu_sort_order]"
                       value="<?= (int) ($page->top_menu_sort_order ?: 400) ?>" style="max-width:120px">
            </div>
            <div class="form-group ep-field mb-0">
                <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Menu visibility') ?></label>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Control whether guests, members, or everyone see this menu item.')) ?>
                <select class="form-control" name="EngagementPage[top_menu_visibility]" style="max-width:280px">
                    <?php foreach (EngagementPage::topMenuVisibilityOptions() as $value => $labelOpt): ?>
                        <option value="<?= Html::encode($value) ?>" <?= ($page->top_menu_visibility ?? 'all') === $value ? 'selected' : '' ?>>
                            <?= Html::encode($labelOpt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </details>

    <details class="ep-set-acc">
        <summary>
            <span class="ep-set-acc__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Site homepage') ?></span>
            <span class="ep-set-acc__summary"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Guest, logged-in, and group homepage targets') ?></span>
        </summary>
        <div class="ep-set-acc__body">
            <p class="ep-set-acc__intro">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Use these assignments instead of the Homepage module. Disable Homepage after configuring here.') ?>
            </p>
            <?php if (!$page->isPublished()): ?>
                <div class="alert alert-warning">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'This page is a draft. Publish it before homepage assignments can redirect users here.') ?>
                </div>
            <?php endif; ?>
            <?php
            $homesByKey = [];
            foreach ($pageHomes as $home) {
                $key = $home->target . ':' . (int) ($home->group_id ?? 0);
                $homesByKey[$key] = $home;
            }
            $guestHome = $homesByKey['guest:0'] ?? null;
            $regHome = $homesByKey['registered:0'] ?? null;
            ?>
            <div class="ep-check-setting">
                <div>
                    <input type="hidden" name="PageHome[guest][enabled]" value="0">
                    <input type="hidden" name="PageHome[guest][target]" value="guest">
                    <label class="form-check-label">
                        <input class="form-check-input" type="checkbox" value="1" name="PageHome[guest][enabled]" id="ep-home-guest"
                            <?= $guestHome ? 'checked' : '' ?>>
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Homepage for guests') ?>
                    </label>
                    <input type="number" class="form-control form-control-sm d-inline-block ms-2" style="width:90px"
                           name="PageHome[guest][priority]" value="<?= (int) ($guestHome->priority ?? 100) ?>"
                           title="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Priority') ?>">
                </div>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'When enabled, signed-out visitors are sent here instead of the default dashboard. Lower priority numbers win if several pages are assigned.')) ?>
            </div>
            <div class="ep-check-setting">
                <div>
                    <input type="hidden" name="PageHome[registered][enabled]" value="0">
                    <input type="hidden" name="PageHome[registered][target]" value="registered">
                    <label class="form-check-label">
                        <input class="form-check-input" type="checkbox" value="1" name="PageHome[registered][enabled]" id="ep-home-reg"
                            <?= $regHome ? 'checked' : '' ?>>
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Default homepage for logged-in users') ?>
                    </label>
                    <input type="number" class="form-control form-control-sm d-inline-block ms-2" style="width:90px"
                           name="PageHome[registered][priority]" value="<?= (int) ($regHome->priority ?? 100) ?>"
                           title="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Priority') ?>">
                </div>
                <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Default post-login and logo home for members who do not match a group homepage below.')) ?>
            </div>
            <?php
            $groupHomes = array_values(array_filter($pageHomes, static fn ($h) => $h->target === PageHome::TARGET_GROUP));
            if ($groupHomes === []) {
                $groupHomes = [null];
            }
            foreach ($groupHomes as $i => $gHome):
            ?>
                <div class="border rounded p-2 mb-2" data-ep-group-home>
                    <input type="hidden" name="PageHome[group<?= $i ?>][target]" value="group">
                    <div class="ep-check-setting">
                        <div>
                            <input type="hidden" name="PageHome[group<?= $i ?>][enabled]" value="0">
                            <label class="form-check-label">
                                <input class="form-check-input" type="checkbox" value="1" name="PageHome[group<?= $i ?>][enabled]"
                                       id="ep-home-group-<?= $i ?>" <?= $gHome ? 'checked' : '' ?>>
                                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Homepage for group') ?>
                            </label>
                        </div>
                        <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Overrides the default logged-in homepage for members of the selected group. Lower priority wins when a user is in several groups.')) ?>
                    </div>
                    <select class="form-control mb-2" name="PageHome[group<?= $i ?>][group_id]" style="max-width:320px">
                        <?php foreach ($groupOptions as $value => $labelOpt): ?>
                            <option value="<?= Html::encode($value) ?>" <?= (string) ($gHome->group_id ?? '') === (string) $value ? 'selected' : '' ?>>
                                <?= Html::encode($labelOpt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" class="form-control" style="max-width:120px"
                           name="PageHome[group<?= $i ?>][priority]" value="<?= (int) ($gHome->priority ?? 50) ?>"
                           placeholder="<?= Yii::t('ThiscoveryPageBuilderModule.base', 'Priority') ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif; ?>
</div>
