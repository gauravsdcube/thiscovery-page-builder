<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\PageHome;

/** @var array $guest */
/** @var array $registered */
/** @var array $groups */
/** @var int $extraGuest */
/** @var int $extraRegistered */
/** @var array $pageOptions */
/** @var array $groupOptions */
/** @var string|null $error */

$this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Site homepage');

$renderSlot = static function (string $name, string $legend, string $hint, array $slot) use ($pageOptions): string {
    $kind = ($slot['kind'] ?? 'page') === 'url' ? 'url' : 'page';
    $enabled = !empty($slot['enabled']);
    $id = 'ep-site-home-' . $name;
    ob_start();
    ?>
    <fieldset class="ep-site-home__slot" data-home-slot>
        <legend><?= Html::encode($legend) ?></legend>
        <p class="help-block"><?= Html::encode($hint) ?></p>
        <div class="checkbox">
            <label>
                <input type="hidden" name="SiteHome[<?= Html::encode($name) ?>][enabled]" value="0">
                <input type="checkbox" value="1" name="SiteHome[<?= Html::encode($name) ?>][enabled]" <?= $enabled ? 'checked' : '' ?>>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Enabled') ?>
            </label>
        </div>
        <div class="form-group">
            <label class="control-label" for="<?= Html::encode($id) ?>-kind"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Send people to') ?></label>
            <select class="form-control" id="<?= Html::encode($id) ?>-kind" name="SiteHome[<?= Html::encode($name) ?>][kind]" data-home-kind style="max-width:280px">
                <option value="page" <?= $kind === 'page' ? 'selected' : '' ?>><?= Yii::t('ThiscoveryPageBuilderModule.base', 'A published page') ?></option>
                <option value="url" <?= $kind === 'url' ? 'selected' : '' ?>><?= Yii::t('ThiscoveryPageBuilderModule.base', 'A path or URL') ?></option>
            </select>
        </div>
        <div class="form-group" data-home-page <?= $kind === 'url' ? 'hidden' : '' ?>>
            <label class="control-label" for="<?= Html::encode($id) ?>-page"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page') ?></label>
            <select class="form-control" id="<?= Html::encode($id) ?>-page" name="SiteHome[<?= Html::encode($name) ?>][page_id]" style="max-width:520px">
                <?php foreach ($pageOptions as $value => $label): ?>
                    <option value="<?= Html::encode((string) $value) ?>" <?= (string) ($slot['page_id'] ?? '') === (string) $value ? 'selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" data-home-url <?= $kind === 'url' ? '' : 'hidden' ?>>
            <label class="control-label" for="<?= Html::encode($id) ?>-url"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'URL') ?></label>
            <input type="text" class="form-control" id="<?= Html::encode($id) ?>-url"
                   name="SiteHome[<?= Html::encode($name) ?>][url]"
                   value="<?= Html::encode((string) ($slot['url'] ?? '')) ?>"
                   placeholder="/dashboard" style="max-width:520px">
            <p class="help-block"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'A site path such as /dashboard, or a full http(s) URL.') ?></p>
        </div>
        <div class="form-group">
            <label class="control-label" for="<?= Html::encode($id) ?>-priority"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Priority') ?></label>
            <input type="number" class="form-control" id="<?= Html::encode($id) ?>-priority"
                   name="SiteHome[<?= Html::encode($name) ?>][priority]"
                   value="<?= (int) ($slot['priority'] ?? 100) ?>" style="max-width:120px">
            <p class="help-block"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Lower numbers win when more than one homepage matches.') ?></p>
        </div>
    </fieldset>
    <?php
    return (string) ob_get_clean();
};

if ($groups === []) {
    $groups = [PageHome::emptySlot(50)];
}
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Html::encode($this->title) ?>
        <span class="pull-right">
            <a href="<?= Html::encode(Url::toGlobalIndex()) ?>">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Back to pages') ?>
            </a>
        </span>
    </div>
    <div class="panel-body">
        <p class="help-block">
            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Choose where the logo, Home, and post-login redirect go for guests, logged-in users, and groups. A destination can be a published page or any path or URL.') ?>
        </p>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= Html::encode($error) ?></div>
        <?php endif; ?>
        <?php if ($extraGuest > 0 || $extraRegistered > 0): ?>
            <div class="alert alert-warning">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'More than one guest or logged-in homepage is saved from individual pages. This screen shows the one that currently wins. Saving here replaces all of those assignments.') ?>
            </div>
        <?php endif; ?>

        <?= Html::beginForm(Url::toSiteHome(), 'post') ?>
            <?= $renderSlot(
                'guest',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Guests'),
                Yii::t('ThiscoveryPageBuilderModule.base', 'Signed-out visitors are sent here instead of the default dashboard.'),
                $guest
            ) ?>
            <?= $renderSlot(
                'registered',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Logged-in users'),
                Yii::t('ThiscoveryPageBuilderModule.base', 'Default home for members who do not match a group below.'),
                $registered
            ) ?>

            <h4><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Groups') ?></h4>
            <p class="help-block">
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Overrides the logged-in homepage for members of the selected group.') ?>
            </p>
            <div data-home-groups>
                <?php foreach ($groups as $i => $slot): ?>
                    <?php
                    $kind = ($slot['kind'] ?? 'page') === 'url' ? 'url' : 'page';
                    $prefix = 'SiteHome[group][' . (int) $i . ']';
                    ?>
                    <div class="well well-sm" data-home-group data-home-slot>
                        <div class="checkbox">
                            <label>
                                <input type="hidden" name="<?= Html::encode($prefix) ?>[enabled]" value="0">
                                <input type="checkbox" value="1" name="<?= Html::encode($prefix) ?>[enabled]" <?= !empty($slot['enabled']) ? 'checked' : '' ?>>
                                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Enabled') ?>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="control-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Group') ?></label>
                            <select class="form-control" name="<?= Html::encode($prefix) ?>[group_id]" style="max-width:320px">
                                <?php foreach ($groupOptions as $value => $label): ?>
                                    <option value="<?= Html::encode((string) $value) ?>" <?= (string) ($slot['group_id'] ?? '') === (string) $value ? 'selected' : '' ?>>
                                        <?= Html::encode($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Send people to') ?></label>
                            <select class="form-control" name="<?= Html::encode($prefix) ?>[kind]" data-home-kind style="max-width:280px">
                                <option value="page" <?= $kind === 'page' ? 'selected' : '' ?>><?= Yii::t('ThiscoveryPageBuilderModule.base', 'A published page') ?></option>
                                <option value="url" <?= $kind === 'url' ? 'selected' : '' ?>><?= Yii::t('ThiscoveryPageBuilderModule.base', 'A path or URL') ?></option>
                            </select>
                        </div>
                        <div class="form-group" data-home-page <?= $kind === 'url' ? 'hidden' : '' ?>>
                            <label class="control-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Page') ?></label>
                            <select class="form-control" name="<?= Html::encode($prefix) ?>[page_id]" style="max-width:520px">
                                <?php foreach ($pageOptions as $value => $label): ?>
                                    <option value="<?= Html::encode((string) $value) ?>" <?= (string) ($slot['page_id'] ?? '') === (string) $value ? 'selected' : '' ?>>
                                        <?= Html::encode($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" data-home-url <?= $kind === 'url' ? '' : 'hidden' ?>>
                            <label class="control-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'URL') ?></label>
                            <input type="text" class="form-control" name="<?= Html::encode($prefix) ?>[url]"
                                   value="<?= Html::encode((string) ($slot['url'] ?? '')) ?>"
                                   placeholder="/dashboard" style="max-width:520px">
                        </div>
                        <div class="form-group">
                            <label class="control-label"><?= Yii::t('ThiscoveryPageBuilderModule.base', 'Priority') ?></label>
                            <input type="number" class="form-control" name="<?= Html::encode($prefix) ?>[priority]"
                                   value="<?= (int) ($slot['priority'] ?? 50) ?>" style="max-width:120px">
                        </div>
                        <button type="button" class="btn btn-default btn-sm" data-home-remove>
                            <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Remove') ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <p>
                <button type="button" class="btn btn-default" data-home-add>
                    <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Add group') ?>
                </button>
            </p>
            <button type="submit" class="btn btn-primary" data-ui-loader>
                <?= Yii::t('ThiscoveryPageBuilderModule.base', 'Save') ?>
            </button>
        <?= Html::endForm() ?>
    </div>
</div>

<script>
(function () {
    function syncKind(select) {
        var slot = select.closest('[data-home-slot]');
        if (!slot) {
            return;
        }
        var url = slot.querySelector('[data-home-url]');
        var page = slot.querySelector('[data-home-page]');
        var isUrl = select.value === 'url';
        if (url) {
            url.hidden = !isUrl;
        }
        if (page) {
            page.hidden = isUrl;
        }
    }

    document.querySelectorAll('[data-home-kind]').forEach(function (select) {
        select.addEventListener('change', function () {
            syncKind(select);
        });
    });

    var groups = document.querySelector('[data-home-groups]');
    var add = document.querySelector('[data-home-add]');
    if (groups && add) {
        add.addEventListener('click', function () {
            var rows = groups.querySelectorAll('[data-home-group]');
            var last = rows[rows.length - 1];
            if (!last) {
                return;
            }
            var copy = last.cloneNode(true);
            var next = rows.length;
            copy.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/SiteHome\[group]\[\d+]/, 'SiteHome[group][' + next + ']');
                if (field.type === 'checkbox') {
                    field.checked = false;
                } else if (field.type === 'hidden' && /\[enabled]/.test(field.name)) {
                    field.value = '0';
                } else if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else if (field.type === 'number') {
                    field.value = '50';
                } else {
                    field.value = '';
                }
            });
            var kind = copy.querySelector('[data-home-kind]');
            if (kind) {
                kind.value = 'page';
                kind.addEventListener('change', function () {
                    syncKind(kind);
                });
                syncKind(kind);
            }
            groups.appendChild(copy);
        });
        groups.addEventListener('click', function (event) {
            var button = event.target.closest('[data-home-remove]');
            if (!button || !groups.contains(button)) {
                return;
            }
            var rows = groups.querySelectorAll('[data-home-group]');
            if (rows.length < 2) {
                var only = rows[0];
                only.querySelectorAll('input[type="checkbox"]').forEach(function (field) {
                    field.checked = false;
                });
                only.querySelectorAll('input[type="text"]').forEach(function (field) {
                    field.value = '';
                });
                only.querySelectorAll('select').forEach(function (field) {
                    field.selectedIndex = 0;
                });
                return;
            }
            button.closest('[data-home-group]').remove();
        });
    }
})();
</script>
