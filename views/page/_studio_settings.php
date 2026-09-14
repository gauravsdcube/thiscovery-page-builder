<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageHome;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionService;
use humhub\modules\thiscoveryEditor\widgets\EditorField;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;

/** @var ContentContainerActiveRecord|null $contentContainer */
/** @var EngagementPage $page */
/** @var bool $isDirectory */
/** @var bool $isTemplate */
/** @var bool $isNew */
/** @var array $collectionOptions */
/** @var array $spaceOptions */
/** @var array $groupOptions */
/** @var PageHome[] $pageHomes */
/** @var string $publicPrefix */
/** @var string|null $parentSlug */
/** @var string $activeSection */
/** @var string $shareUrl */
/** @var array $folderOptions */

$view = $this;
$guide = static function (string $text) use ($view) {
    return $view->render('_setting_guide', ['text' => $text]);
};

$isNew = !empty($isNew);
$activeSection = $activeSection ?? 'basics';
$shareUrl = $shareUrl ?? '';
$showBoundSpace = !$isTemplate && $page->hasAttribute('bound_space_id');
$showNavHome = !$isTemplate && $page->hasAttribute('show_in_top_menu') && $contentContainer === null;
$showVersions = !$isNew && !$isTemplate && PageVersionService::isAvailable();
$navManaged = class_exists(\humhub\modules\thiscoveryNavigation\helpers\Navigation::class)
    && \humhub\modules\thiscoveryNavigation\helpers\Navigation::isActive();
$pane = static function (string $section, string $title) use ($activeSection): string {
    $active = $section === $activeSection;
    return '<section class="ep-settings-pane' . ($active ? ' is-active' : '') . '" data-ep-settings-pane="'
        . Html::encode($section) . '" role="tabpanel"' . ($active ? '' : ' hidden') . '>'
        . '<h3 class="ep-settings-pane__title">' . Html::encode($title) . '</h3>';
};
?>
<div class="ep-settings-workspace">
    <?= $this->render('_studio_rail', [
        'page' => $page,
        'isNew' => $isNew,
        'isTemplate' => $isTemplate,
        'contentContainer' => $contentContainer,
        'activeSection' => $activeSection,
    ]) ?>
    <div class="ep-settings-main">

<?= $pane('basics', Yii::t('ThiscoveryPageBuilderModule.base', 'Basics')) ?>
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

            <?php
            $folderOptions = $folderOptions ?? [];
            $showFolder = $page->hasAttribute('folder_id') && !$isTemplate
                && ($page->isCollection() || $page->isTopLevel() || ($isNew && empty($page->parent_id)));
            ?>
            <?php if ($showFolder): ?>
                <div class="form-group ep-field">
                    <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Folder') ?>
                        <span class="ep-optional"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'optional') ?></span>
                    </label>
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Admin grouping on the page list only. Public URLs stay the same. Leave Unfiled to keep this with top-level pages or unfiled collections.')) ?>
                    <select class="form-control" name="EngagementPage[folder_id]" style="max-width:420px">
                        <option value=""><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Unfiled') ?></option>
                        <?php foreach ($folderOptions as $value => $labelOpt): ?>
                            <option value="<?= Html::encode($value) ?>" <?= (string) ($page->folder_id ?? '') === (string) $value ? 'selected' : '' ?>>
                                <?= Html::encode($labelOpt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($page->hasAttribute('folder_id') && !$page->isCollection() && !$page->isTopLevel()): ?>
                <input type="hidden" name="EngagementPage[folder_id]" value="">
                <p class="ep-hint text-muted">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Child pages stay with their collection. File the collection to organise them.') ?>
                </p>
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
                    <?= $guide(Yii::t('ThiscoveryPageBuilderModule.base', 'Controls how wide this page appears publicly. Extra wide is 1600px; Full uses the browser width with side padding.')) ?>
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
        </section>

<?php if ($showBoundSpace): ?>
<?= $pane('space', Yii::t('ThiscoveryPageBuilderModule.base', 'Bound Space')) ?>
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
        </section>
<?php endif; ?>

<?= $pane('directory', Yii::t('ThiscoveryPageBuilderModule.base', 'Directory listing')) ?>
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
        </section>

<?php if ($showNavHome): ?>
<?= $pane('navigation', Yii::t('ThiscoveryPageBuilderModule.base', 'Navigation')) ?>
            <?php
            $isCollectionPage = $page->isCollection();
            $isChildPage = !$isCollectionPage && !$page->isTopLevel();
            if ($isCollectionPage) {
                $navCheckLabel = Yii::t('ThiscoveryPageBuilderModule.base', 'Show this collection in the top bar');
                $navCheckGuide = Yii::t('ThiscoveryPageBuilderModule.base', 'Adds the collection as a top-bar item. Child pages you mark below appear in its dropdown.');
            } elseif ($isChildPage) {
                $navCheckLabel = Yii::t('ThiscoveryPageBuilderModule.base', 'Show under the collection in the top bar');
                $navCheckGuide = Yii::t('ThiscoveryPageBuilderModule.base', 'Adds this page to the collection’s dropdown. The collection must also be shown in the top bar.');
            } else {
                $navCheckLabel = Yii::t('ThiscoveryPageBuilderModule.base', 'Show in top bar');
                $navCheckGuide = Yii::t('ThiscoveryPageBuilderModule.base', 'Adds this page to the site top navigation when enabled.');
            }
            ?>
            <?php if ($navManaged): ?>
                <p class="help-block">
                    <?= Yii::t(
                        'ThiscoveryPageBuilderModule.base',
                        'Collections and their child pages are added to <a href="{url}">Site navigation</a> automatically. Use the option below for the live top bar.',
                        ['url' => \yii\helpers\Url::to(['/thiscovery-navigation/admin/index'])]
                    ) ?>
                </p>
            <?php endif; ?>
            <div class="ep-check-setting">
                <div>
                    <input type="hidden" name="EngagementPage[show_in_top_menu]" value="0">
                    <label class="form-check-label">
                        <input class="form-check-input" type="checkbox" value="1" name="EngagementPage[show_in_top_menu]" id="ep-top-menu"
                            <?= !empty($page->show_in_top_menu) ? 'checked' : '' ?>>
                        <?= Html::encode($navCheckLabel) ?>
                    </label>
                </div>
                <?= $guide($navCheckGuide) ?>
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
        </section>

<?= $pane('homepage', Yii::t('ThiscoveryPageBuilderModule.base', 'Site homepage')) ?>
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
        </section>
<?php endif; ?>

<?= $pane('css', Yii::t('ThiscoveryPageBuilderModule.base', 'CSS')) ?>
            <?= $this->render('_studio_css', ['page' => $page]) ?>
        </section>

<?= $pane('share', Yii::t('ThiscoveryPageBuilderModule.base', 'Share')) ?>
            <h5 class="ep-section__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Public URL') ?></h5>
            <?php if ($isNew): ?>
                <p class="ep-hint text-muted">
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Save the page first to generate a shareable link.') ?>
                </p>
            <?php else: ?>
                <div class="form-group">
                    <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Public link') ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" readonly value="<?= Html::encode($shareUrl) ?>" data-ep-share-url>
                        <button type="button" class="btn btn-primary" data-ep-copy-url>
                            <i class="fa fa-clipboard"></i>
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Copy link') ?>
                        </button>
                    </div>
                    <div class="ep-copy-feedback text-success d-none" data-ep-copy-feedback>
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Copied!') ?>
                    </div>
                </div>
                <p>
                    <a href="<?= Html::encode(Url::toPublic($page)) ?>" target="_blank" rel="noopener">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Open public page') ?>
                        <i class="fa fa-external-link"></i>
                    </a>
                </p>
                <?php if (!$isTemplate && PageVersionService::isAvailable()): ?>
                    <hr>
                    <h5 class="ep-section__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Publish') ?></h5>
                    <p class="ep-hint text-muted">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Visitors use the published edition. Publish current draft saves your latest studio changes first, then freezes that edition for the live page.') ?>
                    </p>
                    <?php $hasEdition = (new PageVersionService())->hasPublishedEdition($page); ?>
                    <button type="submit" name="after_save" value="publish" class="btn btn-primary btn-sm">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Publish current draft') ?>
                    </button>
                    <?php if (!$hasEdition): ?>
                        <div class="alert alert-warning" style="margin-top:12px">
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'No edition published yet. The public URL shows the working draft until you publish.') ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!$isTemplate): ?>
                    <hr>
                    <h5 class="ep-section__title"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Preview') ?></h5>
                    <p class="ep-hint text-muted">
                        <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Preview saves your latest work, then opens the working draft. Visitors still see the published edition until you publish.') ?>
                    </p>
                    <?php $previewUrl = Url::toPreview($page, true); ?>
                    <div class="form-group">
                        <label class="ep-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Preview URL') ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" readonly value="<?= Html::encode($previewUrl) ?>">
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

<?php if ($showVersions): ?>
<?= $pane('versions', Yii::t('ThiscoveryPageBuilderModule.base', 'Versions')) ?>
            <?= $this->render('_studio_versions', ['page' => $page]) ?>
        </section>
<?php endif; ?>

    </div>
</div>
