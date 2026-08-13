/**
 * Thiscovery Page Builder — builder UI
 * Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * License: AGPL-3.0-or-later
 */
humhub.module('engagementPages', function (module, require, $) {
    var initBuilder = function (root) {
        var $root = $(root);
        if (!$root.length) {
            return;
        }

        var sectionIndex = 0;
        var REGION_ORDER = ['full', 'left', 'main', 'right'];

        var applyLayoutVisibility = function () {
            var layout = String($root.find('[data-ep-layout-select]').val() || 'main');
            var showLeft = (layout === 'left' || layout === 'both');
            var showRight = (layout === 'right' || layout === 'both');

            $root.attr('data-ep-layout', layout);
            $root.find('[data-ep-columns]').attr('data-layout', layout);

            $root.find('[data-ep-region="left"]').toggleClass('is-hidden', !showLeft);
            $root.find('[data-ep-region="right"]').toggleClass('is-hidden', !showRight);
            $root.find('[data-ep-region="left"]').prop('hidden', !showLeft);
            $root.find('[data-ep-region="right"]').prop('hidden', !showRight);

            // Keep full + main always available
            $root.find('[data-ep-region="full"], [data-ep-region="main"]').removeClass('is-hidden').prop('hidden', false);
        };

        var topLevelCards = function () {
            var cards = [];
            REGION_ORDER.forEach(function (region) {
                $root.find('[data-ep-region-list="' + region + '"]').children('[data-ep-section], [data-ep-placeholder]').each(function () {
                    if ($(this).is('[data-ep-placeholder]')) {
                        var editing = $root.find('[data-ep-edit-stage-body] > [data-ep-section]')[0];
                        if (editing) {
                            cards.push(editing);
                        }
                    } else {
                        cards.push(this);
                    }
                });
            });
            return $(cards);
        };

        var cardRegion = function ($card) {
            var $list = $card.closest('[data-ep-region-list]');
            if ($list.length) {
                return $list.attr('data-ep-region-list') || 'main';
            }
            var $ph = $root.find('[data-ep-placeholder]');
            if ($ph.length) {
                return $ph.closest('[data-ep-region-list]').attr('data-ep-region-list') || 'main';
            }
            return 'main';
        };

        var refreshIndexes = function () {
            var i = 0;
            topLevelCards().each(function () {
                var $card = $(this);
                var region = cardRegion($card);
                $card.find('[data-ep-section-region]').first().val(region);
                $card.find('> .ep-section-card__header [data-ep-index]').text(String(i + 1));

                var prefix = 'sections[' + i + ']';
                // Rewrite top-level field names (not nested child cards)
                $card.find('[name^="sections["]').each(function () {
                    var $el = $(this);
                    if ($el.closest('[data-ep-child]').length && !$card.is('[data-ep-child]')) {
                        return;
                    }
                    var name = $el.attr('name');
                    if (!name) {
                        return;
                    }
                    var rest = name.replace(/^sections\[[^\]]+\]/, '');
                    if (rest.indexOf('[children]') === 0 && !$el.closest('[data-ep-container-children]').length) {
                        return;
                    }
                    if ($el.closest('[data-ep-container-children]').length) {
                        return;
                    }
                    $el.attr('name', prefix + rest);
                });

                // Children inside container columns (flat children[] + column field)
                var childIndex = 0;
                $card.find('[data-ep-container-children]').each(function () {
                    var col = parseInt($(this).attr('data-ep-container-col'), 10);
                    if (isNaN(col)) {
                        col = 0;
                    }
                    $(this).children('[data-ep-section], [data-ep-placeholder]').each(function () {
                        var $child = $(this).is('[data-ep-placeholder]')
                            ? $root.find('[data-ep-edit-stage-body] > [data-ep-section]')
                            : $(this);
                        if (!$child.length) {
                            return;
                        }
                        $child.find('> .ep-section-card__header [data-ep-index]').text(String(childIndex + 1));
                        var $colInput = $child.find('[data-ep-child-column]').first();
                        if ($colInput.length) {
                            $colInput.val(String(col));
                        } else {
                            $child.prepend(
                                $('<input type="hidden" data-ep-child-column>').attr('name', 'sections[tmp][column]').val(String(col))
                            );
                        }
                        var childPrefix = prefix + '[children][' + childIndex + ']';
                        $child.find('[name^="sections["]').each(function () {
                            var name = $(this).attr('name');
                            if (!name) {
                                return;
                            }
                            var rest = name.replace(/^sections\[[^\]]+\](?:\[children\]\[[^\]]+\])?/, '');
                            $(this).attr('name', childPrefix + rest);
                        });
                        $child.find('[data-ep-download-item]').each(function (k) {
                            $(this).find('[name*="[settings][items]["]').each(function () {
                                var n = $(this).attr('name');
                                if (!n) {
                                    return;
                                }
                                $(this).attr('name', n.replace(/\[settings\]\[items\]\[(?:\d+|__ROW__|[^\]]+)\]/, '[settings][items][' + k + ']'));
                            });
                        });
                        childIndex += 1;
                    });
                });

                $card.find('> .ep-section-card__body [data-ep-download-item]').each(function (k) {
                    if ($(this).closest('[data-ep-child]').length) {
                        return;
                    }
                    $(this).find('[name*="[settings][items]["]').each(function () {
                        var n = $(this).attr('name');
                        if (!n) {
                            return;
                        }
                        $(this).attr('name', n.replace(/\[settings\]\[items\]\[(?:\d+|__ROW__|[^\]]+)\]/, '[settings][items][' + k + ']'));
                    });
                });

                // Re-index repeatable nested rows (phases / events / team / accordion)
                $card.find('[data-ep-repeat]').each(function () {
                    var kind = $(this).attr('data-ep-repeat');
                    var key = kind === 'team' ? 'people' : 'items';
                    $(this).find('[data-ep-repeat-items] > [data-ep-repeat-item]').each(function (k) {
                        $(this).find('[name*="[settings][' + key + ']["]').each(function () {
                            var n = $(this).attr('name');
                            if (!n) {
                                return;
                            }
                            var re = new RegExp('\\[settings\\]\\[' + key + '\\]\\[(?:\\d+|__ROW__|[^\\]]+)\\]');
                            $(this).attr('name', n.replace(re, '[settings][' + key + '][' + k + ']'));
                        });
                    });
                });

                i += 1;
            });
            sectionIndex = Math.max(sectionIndex, i);
            $root.find('[data-ep-empty]').toggleClass('d-none', topLevelCards().length > 0 || $root.find('[data-ep-child]').length > 0);
        };

        var initRichEditors = function ($scope) {
            try {
                var additions = require('ui.additions');
                if ($scope && $scope.length) {
                    additions.applyTo($scope);
                }
            } catch (e) {
                // Rich text assets may not be ready on first paint.
            }
        };

        var bindUploadFields = function ($scope) {
            var $ctx = ($scope && $scope.length) ? $scope : $root;
            $ctx.find('[data-ep-upload]').each(function () {
                var $box = $(this);
                if ($box.data('ep-upload-bound')) {
                    return;
                }
                $box.data('ep-upload-bound', true);

                var setGuid = function (guid, fileName) {
                    var $guid = $box.find('[data-ep-file-guid]').first();
                    var $meta = $box.find('[data-ep-upload-meta]');
                    if ($guid.length) {
                        $guid.val(guid || '');
                    }
                    if (!$meta.length) {
                        return;
                    }
                    if (guid) {
                        $meta.text(fileName || guid).removeClass('d-none');
                    } else {
                        $meta.text('').addClass('d-none');
                    }
                };

                // Delegated: blueimp may replace the file input after upload
                $box.off('.epUpload');
                $box.on('uploadEnd.epUpload', 'input[type="file"]', function (evt, response) {
                    try {
                        var files = [];
                        if (response && response.result && response.result.files) {
                            files = response.result.files;
                        } else if (response && response.files) {
                            files = response.files;
                        }
                        if (files.length && files[0].guid && !files[0].error) {
                            setGuid(files[0].guid, files[0].name || files[0].file_name || '');
                        }
                    } catch (e) {}
                });

                $box.on('fileDeleted.epUpload', 'input[type="file"]', function () {
                    setGuid('');
                });

                $box.on('click.epUpload', '[data-action-click="file.delete"], .file_upload_remove_link, .delete-file', function () {
                    setTimeout(function () {
                        if (!$box.find('[data-preview-guid]').length) {
                            setGuid('');
                        }
                    }, 80);
                });
            });
        };

        /**
         * Ensure section-scoped GUID inputs are filled before POST.
         * HumHub file.Upload appends temp ep_upload_* fields to the form root for preview;
         * those must not be relied on as the sections[] payload.
         */
        var syncUploadGuids = function ($form) {
            $root.find('[data-ep-upload]').each(function () {
                var $box = $(this);
                var $guid = $box.find('[data-ep-file-guid]').first();
                if (!$guid.length) {
                    return;
                }
                var val = String($guid.val() || '').trim();
                if (!val) {
                    var tmp = $box.attr('data-ep-upload-tmp');
                    if (tmp && $form && $form.length) {
                        var $tmpInput = $form.find('input[type="hidden"][name="' + tmp + '"]').last();
                        if ($tmpInput.length) {
                            val = String($tmpInput.val() || '').trim();
                        }
                    }
                }
                if (!val) {
                    var previewGuid = $box.find('[data-preview-guid]').first().attr('data-preview-guid');
                    if (previewGuid) {
                        val = String(previewGuid).trim();
                    }
                }
                $guid.val(val);
            });
            if ($form && $form.length) {
                $form.find('input[type="hidden"][name^="ep_upload_"]').remove();
            }
        };

        var syncRichEditors = function () {
            $root.find('.ProsemirrorEditor, [data-ui-widget="ui.richtext.prosemirror.RichTextEditor"]').trigger('focusout');
        };

        /**
         * ProseMirror breaks when cards are dragged between columns.
         * Remount editors with the current markdown so they are editable again.
         */
        var remountRichEditors = function ($scope) {
            if (!$scope || !$scope.length) {
                return;
            }
            $scope.find('.ProsemirrorEditor').each(function () {
                var $ed = $(this);
                var id = $ed.attr('id');
                if (!id) {
                    return;
                }
                var $input = $('#' + id + '_input');
                var value = $input.length ? ($input.val() || '') : '';

                try {
                    var existing = $ed.data('humhub-ui-richtexteditor');
                    if (existing && existing.editor && typeof existing.editor.serialize === 'function') {
                        value = existing.editor.serialize();
                        if ($input.length) {
                            $input.val(value);
                        }
                    }
                } catch (e) {
                    // ignore broken instance
                }

                if (!value) {
                    value = $.trim($ed.find('[data-ui-richtext]').text() || '');
                }

                $ed.removeData('humhub-ui-richtexteditor');
                // Keep upload controls / non-editor chrome; strip ProseMirror DOM.
                $ed.children().each(function () {
                    var $child = $(this);
                    if ($child.is('[data-ui-richtext]') || $child.is('.btn-group') || $child.find('input[type="file"]').length) {
                        return;
                    }
                    $child.remove();
                });

                var $holder = $ed.find('[data-ui-richtext]');
                if (!$holder.length) {
                    $holder = $('<div data-ui-richtext style="display:none"></div>').appendTo($ed);
                }
                $holder.text(value);

                if (!$ed.attr('data-ui-widget')) {
                    $ed.attr('data-ui-widget', 'ui.richtext.prosemirror.RichTextEditor');
                }
            });
        };

        var refreshCardTitle = function ($card) {
            var type = $card.find('[data-ep-section-type]').val() || $card.data('ep-type');
            var typeLabels = module.config.types || {};
            var fromField = $.trim($card.find('[data-ep-card-title-source]').first().val() || '');
            $card.find('> .ep-section-card__header [data-ep-title]').text(fromField || typeLabels[type] || module.text('untitled'));
            $card.find('> .ep-section-card__header [data-ep-type-label]').text(typeLabels[type] || type);
        };

        var closeEditor = function () {
            syncRichEditors();

            // Return any card left in the edit stage (legacy path)
            var $stage = $root.find('[data-ep-edit-stage]');
            var $staged = $stage.find('[data-ep-edit-stage-body] > [data-ep-section]');
            var $ph = $root.find('[data-ep-placeholder]');
            if ($staged.length) {
                if ($ph.length) {
                    $ph.replaceWith($staged);
                } else {
                    getListForRegion('main').append($staged);
                }
            }
            $ph.remove();
            $stage.addClass('d-none').attr('aria-hidden', 'true');
            $stage.find('[data-ep-edit-stage-body]').empty();

            $root.find('[data-ep-section].is-editing, [data-ep-section].is-expanded')
                .removeClass('is-editing is-expanded')
                .addClass('is-collapsed');
            $root.removeClass('is-editing-section');
            refreshIndexes();
        };

        var openEditor = function ($card) {
            if (!$card || !$card.length) {
                return;
            }
            // Toggle off if already editing this card
            if ($card.hasClass('is-editing')) {
                closeEditor();
                return;
            }

            closeEditor();

            // Expand in place — avoids pointer-events traps when the stage move fails
            $root.find('[data-ep-section]').addClass('is-collapsed').removeClass('is-expanded is-editing');
            $card.removeClass('is-collapsed').addClass('is-expanded is-editing');
            $root.addClass('is-editing-section');

            // Optional: also show in edit stage for wider editing surface
            var $stage = $root.find('[data-ep-edit-stage]');
            var $body = $stage.find('[data-ep-edit-stage-body]');
            if ($stage.length && $body.length) {
                var $ph = $('<div class="ep-section-placeholder" data-ep-placeholder aria-hidden="true"></div>');
                $card.after($ph);
                $body.append($card);
                $stage.removeClass('d-none').attr('aria-hidden', 'false');
            }

            try {
                remountRichEditors($card);
            } catch (e) {}
            $card.find('[data-ep-upload]').each(function () {
                $(this).off('.epUpload').removeData('ep-upload-bound');
            });
            setTimeout(function () {
                try {
                    initRichEditors($card);
                    bindUploadFields($card);
                } catch (e) {}
                var $focus = $card.find('[data-ep-card-title-source], input.form-control, textarea.form-control').first();
                if ($focus.length) {
                    $focus.trigger('focus');
                }
            }, 50);
        };

        var expandCard = openEditor;

        var defaultRegionForType = function (type) {
            if (type === 'hero') {
                return 'full';
            }
            if (type === 'team' || type === 'contact') {
                var layout = String($root.find('[data-ep-layout-select]').val() || 'main');
                if (layout === 'left' || layout === 'both') {
                    return 'left';
                }
            }
            return 'main';
        };

        var getListForRegion = function (region) {
            var $list = $root.find('[data-ep-region-list="' + region + '"]');
            var $region = $list.closest('[data-ep-region]');
            if (!$list.length || $region.hasClass('is-hidden') || $region.prop('hidden')) {
                $list = $root.find('[data-ep-region-list="main"]');
            }
            return $list;
        };

        var findInsertBefore = function ($list, clientY) {
            var $before = null;
            $list.children('[data-ep-section], [data-ep-placeholder]').each(function () {
                var rect = this.getBoundingClientRect();
                if (clientY < rect.top + rect.height / 2) {
                    $before = $(this);
                    return false;
                }
            });
            return $before;
        };

        var addSection = function (type, $list, $beforeEl, asChild) {
            if (asChild && type === 'container') {
                module.log.warn(module.config.noContainerInContainer || 'Containers cannot be nested.');
                return null;
            }
            var typed = $root.find('#ep-section-template-' + type).html();
            var template = typed || $root.find('#ep-section-template').html();
            if (!template) {
                return null;
            }
            var idx = String(sectionIndex++);
            var $card = $(template.replace(/__INDEX__/g, idx));
            $card.attr('data-ep-type', type);
            $card.find('[data-ep-section-type]').val(type);

            if (asChild) {
                $card.attr('data-ep-child', '');
                $card.addClass('ep-section-card--child');
                $card.find('[data-ep-section-region]').remove();
                $card.find('[data-ep-container-zone]').remove();
                var col = parseInt(($list && $list.attr('data-ep-container-col')) || '0', 10);
                if (isNaN(col)) {
                    col = 0;
                }
                if (!$card.find('[data-ep-child-column]').length) {
                    $card.prepend(
                        $('<input type="hidden" data-ep-child-column>')
                            .attr('name', 'sections[' + idx + '][column]')
                            .val(String(col))
                    );
                } else {
                    $card.find('[data-ep-child-column]').val(String(col));
                }
            } else {
                $card.removeAttr('data-ep-child');
                var region = $list.attr('data-ep-region-list') || defaultRegionForType(type);
                if (!$card.find('[data-ep-section-region]').length) {
                    $card.prepend('<input type="hidden" name="sections[' + idx + '][region]" value="' + region + '" data-ep-section-region>');
                } else {
                    $card.find('[data-ep-section-region]').val(region);
                }
            }

            if (!$list || !$list.length) {
                $list = getListForRegion(defaultRegionForType(type));
            }

            if ($beforeEl && $beforeEl.length) {
                $card.insertBefore($beforeEl);
            } else {
                $list.append($card);
            }

            refreshCardTitle($card);
            refreshIndexes();
            syncCollectionSourceFields($card);
            expandCard($card);
            setTimeout(function () {
                initRichEditors($card);
                bindUploadFields($card);
            }, 50);
            return $card;
        };

        // Tabs
        $root.on('click', '[data-ep-tab]', function (e) {
            e.preventDefault();
            var tab = $(this).data('ep-tab');
            $root.find('[data-ep-tab]').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');
            $root.find('[data-ep-panel]').removeClass('is-active');
            $root.find('[data-ep-panel="' + tab + '"]').addClass('is-active');
            if (tab === 'settings' || tab === 'builder') {
                setTimeout(function () {
                    initRichEditors($root.find('[data-ep-panel="' + tab + '"]'));
                }, 30);
            }
        });

        $root.on('change', '[data-ep-page-width]', function () {
            var val = String($(this).val() || 'wide');
            $root.find('[data-ep-page-width]').val(val);
        });

        var normalizeHex = function (raw, fallback) {
            var v = String(raw || '').trim().toLowerCase();
            if (!v) {
                return fallback || '';
            }
            if (v.charAt(0) !== '#') {
                v = '#' + v;
            }
            if (/^#[0-9a-f]{3}$/.test(v)) {
                return '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
            }
            if (/^#[0-9a-f]{6}$/.test(v)) {
                return v;
            }
            return fallback || '';
        };

        $root.on('input change', '[data-ep-color-picker]', function () {
            var $field = $(this).closest('[data-ep-color-field]');
            var hex = normalizeHex($(this).val(), '#000000');
            $field.find('[data-ep-color-text]').val(hex);
        });

        $root.on('change blur', '[data-ep-color-text]', function () {
            var $input = $(this);
            var $field = $input.closest('[data-ep-color-field]');
            var fallback = String($field.find('[data-ep-color-picker]').val() || '#000000');
            var raw = String($input.val() || '').trim();
            if (!raw) {
                return;
            }
            var hex = normalizeHex(raw, '');
            if (!hex) {
                $input.val('');
                return;
            }
            $input.val(hex);
            $field.find('[data-ep-color-picker]').val(hex);
        });

        $root.on('click', '[data-ep-color-clear]', function (e) {
            e.preventDefault();
            var $field = $(this).closest('[data-ep-color-field]');
            $field.find('[data-ep-color-text]').val('');
        });

        $root.on('click', '[data-ep-color-preset]', function (e) {
            e.preventDefault();
            var hex = normalizeHex($(this).data('ep-color-preset'), '');
            if (!hex) {
                return;
            }
            var $wrap = $(this).closest('.ep-color-fields');
            var $bg = $wrap.find('[name$="[background_color]"]');
            $bg.val(hex);
            $bg.closest('[data-ep-color-field]').find('[data-ep-color-picker]').val(hex);
        });

        $root.on('change', '[data-ep-show-border]', function () {
            var on = $(this).is(':checked');
            $(this).closest('.ep-color-fields').find('[data-ep-border-color-wrap]').prop('hidden', !on);
        });

        $root.on('change', '[data-ep-card-image]', function () {
            if (!this.checked) {
                return;
            }
            var $this = $(this);
            $root.find('[data-ep-card-image]').not($this).prop('checked', false);
        });

        var syncCollectionSourceFields = function ($scope) {
            var $card = $scope && $scope.length ? $scope : $root;
            $card.find('[data-ep-collection-source]').each(function () {
                var $select = $(this);
                var source = String($select.val() || 'pages');
                var $wrap = $select.closest('[data-ep-section], .ep-section-card, form');
                $wrap.find('[data-ep-collection-pages-only]').prop('hidden', source !== 'pages');
                $wrap.find('[data-ep-collection-calendar-only]').prop('hidden', source !== 'calendar');
            });
        };

        $root.on('change', '[data-ep-collection-source]', function () {
            syncCollectionSourceFields($(this).closest('[data-ep-section]'));
        });

        $root.on('submit', 'form.ep-studio__form', function (e) {
            var $form = $(this);
            syncRichEditors();
            closeEditor();
            refreshIndexes();
            syncUploadGuids($form);
            // Avoid duplicate page_width fields (builder + settings) confusing PHP
            var $widths = $form.find('[name="EngagementPage[page_width]"]');
            if ($widths.length > 1) {
                var val = String($widths.filter('[data-ep-page-width]').last().val() || $widths.last().val() || 'wide');
                $widths.prop('disabled', true);
                $form.append($('<input type="hidden" name="EngagementPage[page_width]">').val(val));
            }

            // Title/slug live on the Settings tab (often hidden). Validate here so Save
            // does not silently fail with no visible browser tooltip.
            var title = String($form.find('[name="EngagementPage[title]"]').val() || '').trim();
            var $slug = $form.find('[name="EngagementPage[slug]"]').filter(function () {
                return this.type !== 'hidden' || !$form.find('[name="EngagementPage[slug]"]').not('[type=hidden]').length;
            }).first();
            if (!$slug.length) {
                $slug = $form.find('[name="EngagementPage[slug]"]').first();
            }
            var slug = String($slug.val() || '').trim();
            var missing = [];
            if (!title) {
                missing.push('title');
            }
            if ($slug.length && $slug.attr('type') !== 'hidden' && !slug) {
                missing.push('slug');
            }
            if (missing.length) {
                e.preventDefault();
                $root.find('[data-ep-tab="settings"]').trigger('click');
                setTimeout(function () {
                    var $focus = !title
                        ? $form.find('[name="EngagementPage[title]"]')
                        : $form.find('[name="EngagementPage[slug]"]').not('[type=hidden]').first();
                    if ($focus.length) {
                        $focus.trigger('focus');
                    }
                }, 40);
                var msg = !title
                    ? (module.config.needTitle || 'Please enter a page title before saving.')
                    : (module.config.needSlug || 'Please enter a URL slug before saving.');
                if (window.humhub && humhub.modules && humhub.modules.ui && humhub.modules.ui.status) {
                    humhub.modules.ui.status.error(msg);
                } else {
                    window.alert(msg);
                }
                return false;
            }
        });

        $root.on('click', '[data-ep-edit-done]', function (e) {
            e.preventDefault();
            closeEditor();
        });

        $root.on('change', '[data-ep-layout-select]', function () {
            closeEditor();
            applyLayoutVisibility();
            // Move sections from hidden side columns into main
            ['left', 'right'].forEach(function (region) {
                var $region = $root.find('[data-ep-region="' + region + '"]');
                if ($region.hasClass('is-hidden')) {
                    var $main = $root.find('[data-ep-region-list="main"]');
                    $region.find('[data-ep-region-list]').children('[data-ep-section], [data-ep-placeholder]').appendTo($main);
                }
            });
            refreshIndexes();
        });

        $root.on('click', '[data-ep-copy-url]', function (e) {
            e.preventDefault();
            var $input = $root.find('[data-ep-share-url]');
            var url = $input.val() || '';
            var done = function () {
                var $fb = $root.find('[data-ep-copy-feedback]');
                $fb.removeClass('d-none');
                setTimeout(function () {
                    $fb.addClass('d-none');
                }, 1800);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done).catch(function () {
                    $input.trigger('select');
                    try { document.execCommand('copy'); } catch (err) {}
                    done();
                });
            } else {
                $input.trigger('select');
                try { document.execCommand('copy'); } catch (err) {}
                done();
            }
        });

        // Palette click → main (hero → full)
        $root.on('click', '[data-ep-palette-type]', function (e) {
            e.preventDefault();
            var type = String($(this).data('ep-palette-type'));
            addSection(type, getListForRegion(defaultRegionForType(type)));
        });

        // Palette HTML5 drag
        $root.on('dragstart', '[data-ep-palette-type]', function (e) {
            var type = String($(this).data('ep-palette-type'));
            e.originalEvent.dataTransfer.setData('text/ep-section-type', type);
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
            $(this).addClass('is-dragging');
            $root.find('[data-ep-drop-zone]').addClass('is-drop-target');
        });
        $root.on('dragend', '[data-ep-palette-type]', function () {
            $(this).removeClass('is-dragging');
            $root.find('[data-ep-drop-zone]').removeClass('is-drop-target is-drag-over');
        });

        $root.on('dragover', '[data-ep-drop-zone]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.originalEvent.dataTransfer.dropEffect = 'copy';
            $root.find('[data-ep-drop-zone]').removeClass('is-drag-over');
            $(this).addClass('is-drag-over');
        });
        $root.on('dragleave', '[data-ep-drop-zone]', function (e) {
            if (e.target === this) {
                $(this).removeClass('is-drag-over');
            }
        });
        $root.on('drop', '[data-ep-drop-zone]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $zone = $(this);
            $root.find('[data-ep-drop-zone]').removeClass('is-drag-over is-drop-target');
            var type = e.originalEvent.dataTransfer.getData('text/ep-section-type');
            if (!type) {
                return;
            }
            var asChild = $zone.is('[data-ep-container-children]');
            if (asChild && type === 'container') {
                module.log.warn(module.config.noContainerInContainer || 'Containers cannot be nested.');
                return;
            }
            var $before = findInsertBefore($zone, e.originalEvent.clientY);
            addSection(type, $zone, $before, asChild);
        });

        // Reorder via handle (within same list / container)
        (function () {
            var dragging = null;
            var $list = null;
            var moved = false;

            var stop = function () {
                if (!dragging) {
                    return;
                }
                $(dragging).removeClass('is-reordering');
                if ($list) {
                    $list.removeClass('is-sorting');
                }
                dragging = null;
                $list = null;
                refreshIndexes();
            };

            var onMove = function (clientY, clientX) {
                if (!dragging || !$list) {
                    return;
                }
                moved = true;

                // Allow dropping into another visible region / container under pointer
                var el = document.elementFromPoint(clientX, clientY);
                if (el) {
                    var $targetZone = $(el).closest('[data-ep-drop-zone]');
                    if ($targetZone.length && $targetZone[0] !== $list[0]) {
                        var isContainerZone = $targetZone.is('[data-ep-container-children]');
                        var isContainerCard = $(dragging).data('ep-type') === 'container' || $(dragging).find('[data-ep-section-type]').val() === 'container';
                        var isChild = $(dragging).is('[data-ep-child]');
                        if (isContainerZone && isContainerCard) {
                            // skip nest
                        } else if (!isContainerZone && isChild) {
                            $(dragging).removeAttr('data-ep-child').removeClass('ep-section-card--child');
                            if (!$(dragging).find('[data-ep-section-region]').length) {
                                $(dragging).prepend('<input type="hidden" name="sections[tmp][region]" value="main" data-ep-section-region>');
                            }
                            $list = $targetZone;
                            $list.addClass('is-sorting');
                        } else if (isContainerZone && !isChild) {
                            $(dragging).attr('data-ep-child', '').addClass('ep-section-card--child');
                            $(dragging).find('[data-ep-section-region]').remove();
                            $list = $targetZone;
                            $list.addClass('is-sorting');
                        } else if (!isContainerZone) {
                            $list = $targetZone;
                            $list.addClass('is-sorting');
                        }
                    }
                }

                var $others = $list.children('[data-ep-section]').filter(function () {
                    return this !== dragging;
                });
                var placed = false;
                $others.each(function () {
                    var rect = this.getBoundingClientRect();
                    if (clientY < rect.top + rect.height / 2) {
                        if (dragging.nextElementSibling !== this) {
                            this.parentNode.insertBefore(dragging, this);
                        }
                        placed = true;
                        return false;
                    }
                });
                if (!placed && $list.length) {
                    $list[0].appendChild(dragging);
                }
            };

            $root.on('mousedown touchstart', '[data-ep-drag-handle]', function (e) {
                if (e.type === 'mousedown' && e.which !== 1) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                closeEditor();
                moved = false;
                var $item = $(this).closest('[data-ep-section]');
                if (!$item.length || !$item.parent().length) {
                    return;
                }
                dragging = $item[0];
                $list = $item.parent();
                $item.addClass('is-reordering');
                $list.addClass('is-sorting');
            });

            $(document).on('mousemove.epBuilder touchmove.epBuilder', function (e) {
                if (!dragging) {
                    return;
                }
                var y, x;
                if (e.type.indexOf('touch') === 0) {
                    var t = e.originalEvent.touches[0];
                    y = t && t.clientY;
                    x = t && t.clientX;
                } else {
                    y = e.clientY;
                    x = e.clientX;
                }
                onMove(y, x);
            });

            $(document).on('mouseup.epBuilder touchend.epBuilder', function () {
                stop();
            });

            $root.on('click', '[data-ep-drag-handle]', function (e) {
                if (moved) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        })();

        // Header / pencil opens the editor; body field clicks do not toggle it closed
        $root.on('click', '[data-ep-toggle-card-btn]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $card = $(this).closest('[data-ep-section]');
            if ($card.hasClass('is-editing')) {
                closeEditor();
            } else {
                openEditor($card);
            }
        });

        $root.on('click', '[data-ep-toggle-card]', function (e) {
            if ($(e.target).closest('[data-ep-drag-handle], [data-ep-remove-section], [data-ep-toggle-card-btn]').length) {
                return;
            }
            // Ignore clicks that bubbled from the card body / uploads / editors
            if ($(e.target).closest('.ep-section-card__body, [data-ep-container-zone]').length) {
                return;
            }
            e.preventDefault();
            var $card = $(this).closest('[data-ep-section]');
            if ($card.hasClass('is-editing')) {
                closeEditor();
            } else {
                openEditor($card);
            }
        });

        $root.on('click', '[data-ep-remove-section]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $card = $(this).closest('[data-ep-section]');
            if ($card.hasClass('is-editing')) {
                $root.find('[data-ep-placeholder]').remove();
                $root.find('[data-ep-edit-stage]').addClass('d-none').attr('aria-hidden', 'true');
                $root.removeClass('is-editing-section');
            }
            $card.remove();
            refreshIndexes();
        });

        $root.on('click', '[data-ep-clear-sections]', function (e) {
            e.preventDefault();
            if (!window.confirm(module.config.clearConfirm || 'Clear all sections?')) {
                return;
            }
            $root.find('[data-ep-region-list]').empty();
            refreshIndexes();
        });

        $root.on('input change', '[data-ep-card-title-source]', function () {
            refreshCardTitle($(this).closest('[data-ep-section]'));
        });

        $root.on('click', '[data-ep-add-download]', function (e) {
            e.preventDefault();
            var $card = $(this).closest('[data-ep-section]');
            var rowId = 'r' + Date.now();
            var template = $root.find('#ep-download-item-template').html();
            if (!template) {
                return;
            }
            var html = template
                .replace(/__SEC__/g, '0')
                .replace(/__ROW__/g, rowId);
            var $row = $(html);
            $card.find('[data-ep-download-items]').first().append($row);
            refreshIndexes();
            setTimeout(function () {
                initRichEditors($row);
                bindUploadFields($row);
            }, 40);
        });

        $root.on('click', '[data-ep-remove-download]', function (e) {
            e.preventDefault();
            $(this).closest('[data-ep-download-item]').remove();
            refreshIndexes();
        });

        $root.on('click', '[data-ep-repeat-add]', function (e) {
            e.preventDefault();
            var kind = String($(this).data('ep-repeat-add') || '');
            var $wrap = $(this).closest('[data-ep-repeat]');
            var template = $root.find('#ep-repeat-template-' + kind).html();
            if (!template || !$wrap.length) {
                return;
            }
            var rowId = 'r' + Date.now();
            var html = template
                .replace(/__SEC__/g, '0')
                .replace(/__ROW__/g, rowId);
            var $row = $(html);
            $wrap.find('[data-ep-repeat-items]').first().append($row);
            refreshIndexes();
            setTimeout(function () {
                initRichEditors($row);
            }, 40);
        });

        $root.on('click', '[data-ep-repeat-remove]', function (e) {
            e.preventDefault();
            var $items = $(this).closest('[data-ep-repeat-items]');
            $(this).closest('[data-ep-repeat-item]').remove();
            if ($items.length && !$items.children('[data-ep-repeat-item]').length) {
                $items.closest('[data-ep-repeat]').find('[data-ep-repeat-add]').trigger('click');
            }
            refreshIndexes();
        });

        var rebuildContainerColumns = function ($card, cols) {
            cols = Math.max(1, Math.min(4, parseInt(cols, 10) || 1));
            var $zone = $card.find('[data-ep-container-zone]').first();
            if (!$zone.length) {
                return;
            }
            var $children = $();
            $zone.find('[data-ep-container-children]').each(function () {
                $children = $children.add($(this).children('[data-ep-section]'));
            });
            var $grid = $('<div class="ep-container-zone__grid" data-ep-container-grid>').attr('data-cols', String(cols));
            var buckets = [];
            var i;
            for (i = 0; i < cols; i += 1) {
                buckets[i] = [];
            }
            $children.each(function () {
                var $child = $(this);
                var col = parseInt($child.find('[data-ep-child-column]').val(), 10);
                if (isNaN(col) || col < 0) {
                    col = 0;
                }
                if (col >= cols) {
                    col = cols - 1;
                }
                buckets[col].push($child);
            });
            for (i = 0; i < cols; i += 1) {
                var $col = $('<div class="ep-container-col" data-ep-container-col="' + i + '">');
                $col.append(
                    $('<div class="ep-container-col__label">').text(
                        module.config.columnLabel
                            ? String(module.config.columnLabel).replace('{n}', String(i + 1))
                            : ('Column ' + (i + 1))
                    )
                );
                var $list = $('<div class="ep-container-children" data-ep-container-children data-ep-drop-zone="container">')
                    .attr('data-ep-container-col', String(i));
                buckets[i].forEach(function ($child) {
                    $child.find('[data-ep-child-column]').val(String(i));
                    $list.append($child);
                });
                $col.append($list);
                $grid.append($col);
            }
            $zone.attr('data-ep-cols', String(cols));
            $zone.find('[data-ep-container-grid]').remove();
            $zone.append($grid);
        };

        $root.on('change', '[data-ep-container-columns]', function () {
            var $card = $(this).closest('[data-ep-section]');
            rebuildContainerColumns($card, $(this).val());
            refreshIndexes();
        });

        applyLayoutVisibility();
        // Relocate sections from columns that aren't visible in the current layout
        ['left', 'right'].forEach(function (region) {
            var $region = $root.find('[data-ep-region="' + region + '"]');
            if ($region.hasClass('is-hidden')) {
                var $main = $root.find('[data-ep-region-list="main"]');
                $region.find('[data-ep-region-list] > [data-ep-section]').appendTo($main);
            }
        });
        refreshIndexes();
        $root.find('[data-ep-section]').each(function () {
            refreshCardTitle($(this));
        });
        syncCollectionSourceFields($root);
        // Only init rich editors that are already visible (settings tab / expanded).
        // Collapsed column cards are remounted when opened in the edit stage.
        setTimeout(function () {
            initRichEditors($root.find('[data-ep-panel="settings"]'));
            bindUploadFields($root);
        }, 80);

        if ($root.find('[data-ep-form-errors]').length) {
            $root.find('[data-ep-tab="settings"]').trigger('click');
        }
    };

    var init = function () {
        // Legacy no-op for data-ui-widget; builder is started via registerJs.
    };

    module.export({
        init: init,
        initBuilder: initBuilder,
        initOnAjaxLoad: true
    });
});
