<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use humhub\modules\engagementPages\models\EngagementPage;
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
            self::ALIGN_START => Yii::t('EngagementPagesModule.base', 'Full width'),
            self::ALIGN_CENTER => Yii::t('EngagementPagesModule.base', 'Centre'),
            self::ALIGN_END => Yii::t('EngagementPagesModule.base', 'Right'),
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
     * Suggested swatches (NHS-oriented + neutrals) for the colour picker UI.
     *
     * @return string[]
     */
    public static function colorPresets(): array
    {
        return [
            '#003078', // NHS dark blue (default hero)
            '#1d70b8', // NHS blue
            '#00703c', // NHS green
            '#d4351c', // NHS red
            '#f47738', // NHS orange
            '#0b0c0c', // near-black
            '#505a5f', // grey
            '#f3f2f1', // light grey
            '#ffffff', // white
        ];
    }

    public function render(EngagementPage $page): string
    {
        $html = Yii::$app->view->render(
            '@engagement-pages/views/blocks/' . $this->getType(),
            [
                'block' => $this,
                'page' => $page,
                'settings' => $this->getPersistedSettings(),
            ]
        );

        return $this->wrapAligned($html);
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
