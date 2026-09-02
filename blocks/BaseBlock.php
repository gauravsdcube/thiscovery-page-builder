<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;
use yii\base\BaseObject;

abstract class BaseBlock extends BaseObject
{
    public const ALIGN_START = 'start';
    public const ALIGN_CENTER = 'center';
    public const ALIGN_END = 'end';

    public array $settings = [];

    public function __construct(array $settings = [], array $config = [])
    {
        $this->settings = $settings;
        parent::__construct($config);
    }

    abstract public function getType(): string;

    abstract public function getLabel(): string;

    abstract public function normalizeSettings(): array;

    public static function alignOptions(): array
    {
        return [
            self::ALIGN_START => Yii::t('ThiscoveryPageBuilderModule.base', 'Full width'),
            self::ALIGN_CENTER => Yii::t('ThiscoveryPageBuilderModule.base', 'Centre'),
            self::ALIGN_END => Yii::t('ThiscoveryPageBuilderModule.base', 'Right'),
        ];
    }

    /**
     * Default horizontal position within the page shell.
     * Collections override this to centre so card grids read better on wide pages.
     */
    protected function getDefaultAlign(): string
    {
        return self::ALIGN_START;
    }

    public function getAlign(): string
    {
        $align = (string) ($this->settings['align'] ?? $this->getDefaultAlign());
        if (!isset(self::alignOptions()[$align])) {
            return $this->getDefaultAlign();
        }
        return $align;
    }

    /**
     * Settings persisted to sections_json (block-specific + shared layout/colours).
     */
    public function getPersistedSettings(): array
    {
        return array_merge($this->normalizeSettings(), [
            'align' => $this->getAlign(),
            'background_color' => $this->getBackgroundColor(),
            'text_color' => $this->getTextColor(),
            'border_color' => $this->getBorderColor(),
            'show_border' => $this->getShowBorder(),
        ]);
    }

    /**
     * Optional block background (#rgb / #rrggbb). Empty = theme default.
     */
    public function getBackgroundColor(): string
    {
        return self::sanitizeColor($this->settings['background_color'] ?? '');
    }

    /**
     * Optional block text colour. Empty = theme default.
     */
    public function getTextColor(): string
    {
        return self::sanitizeColor($this->settings['text_color'] ?? '');
    }

    /**
     * Optional block border colour. Empty = theme default.
     */
    public function getBorderColor(): string
    {
        return self::sanitizeColor($this->settings['border_color'] ?? '');
    }

    /**
     * Whether the block should render a border (Hero). Default true.
     */
    public function getShowBorder(): bool
    {
        if (!array_key_exists('show_border', $this->settings)) {
            return true;
        }
        return !empty($this->settings['show_border']);
    }

    public static function sanitizeColor($value): string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return '';
        }
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $value)) {
            return $value;
        }
        return '';
    }

    /**
     * Inline CSS variables applied to the block wrapper.
     */
    public function colorStyleAttribute(): string
    {
        $parts = [];
        $bg = $this->getBackgroundColor();
        $fg = $this->getTextColor();
        $border = $this->getBorderColor();
        if ($bg !== '') {
            $parts[] = '--ep-bg:' . $bg;
        }
        if ($fg !== '') {
            $parts[] = '--ep-fg:' . $fg;
        }
        if (!$this->getShowBorder()) {
            $parts[] = '--ep-border-width:0px';
            $parts[] = '--ep-border:transparent';
        } elseif ($border !== '') {
            $parts[] = '--ep-border:' . $border;
            $parts[] = '--ep-border-width:2px';
        }
        return implode(';', $parts);
    }

    /**
     * Suggested swatches for the colour picker UI. Prefers live Thiscovery theme colours when that module is enabled.
     *
     * @return string[]
     */
    public static function colorPresets(): array
    {
        $defaults = [
            '#003078',
            '#1d70b8',
            '#00703c',
            '#d4351c',
            '#f47738',
            '#0b0c0c',
            '#505a5f',
            '#f3f2f1',
            '#ffffff',
        ];

        $module = Yii::$app->getModule('thiscovery-theme');
        if ($module === null) {
            return $defaults;
        }

        $fromTheme = [];
        foreach ([
            'themePrimaryColor',
            'themeAccentColor',
            'buttonBackgroundColor',
            'topMenuBackgroundColor',
            'themeSuccessColor',
            'themeDangerColor',
            'themeWarningColor',
            'themeInfoColor',
            'textColorMain',
            'textColorSecondary',
            'backgroundColorPage',
            'cardBackgroundColor',
        ] as $key) {
            $value = strtolower(trim((string) $module->settings->get($key, '')));
            if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $value)) {
                $fromTheme[] = $value;
            }
        }

        return array_values(array_unique(array_merge($fromTheme, $defaults)));
    }

    public function render(EngagementPage $page): string
    {
        $settings = $this->getPersistedSettings();
        $settings = self::maybeTranslateSettings($page, $this->getType(), $settings);

        $html = Yii::$app->view->render(
            '@thiscovery-page-builder/views/blocks/' . $this->getType(),
            [
                'block' => $this,
                'page' => $page,
                'settings' => $settings,
            ]
        );

        return $this->wrapAligned($html);
    }

    /**
     * Soft-dep on thiscovery-translate: translate display copy without mutating stored JSON.
     */
    protected static function maybeTranslateSettings(EngagementPage $page, string $blockType, array $settings): array
    {
        try {
            $module = Yii::$app->getModule('thiscovery-translate');
            if ($module === null || !method_exists($module, 'getIsEnabled') || !$module->getIsEnabled()) {
                return $settings;
            }
            $hook = \humhub\modules\thiscoveryTranslate\services\PageBuilderHook::class;
            if (!class_exists($hook)) {
                return $settings;
            }
            return $hook::translateBlockSettings($page, $blockType, $settings);
        } catch (\Throwable $e) {
            return $settings;
        }
    }

    protected function wrapAligned(string $html): string
    {
        $align = $this->getAlign();
        $style = $this->colorStyleAttribute();
        $attrs = 'class="ep-align ep-align--' . htmlspecialchars($align, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            . ' data-ep-align="' . htmlspecialchars($align, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        if ($style !== '') {
            $attrs .= ' style="' . htmlspecialchars($style, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
                . ' data-ep-colored="1"';
        }
        return '<div ' . $attrs . '>' . $html . '</div>';
    }

    protected function string(string $key, string $default = ''): string
    {
        $value = $this->settings[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    protected function intOrNull(string $key): ?int
    {
        if (!isset($this->settings[$key]) || $this->settings[$key] === '' || $this->settings[$key] === null) {
            return null;
        }
        return (int) $this->settings[$key];
    }
}
