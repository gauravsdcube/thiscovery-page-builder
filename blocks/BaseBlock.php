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
     * Settings persisted to sections_json (block-specific + shared align).
     */
    public function getPersistedSettings(): array
    {
        return array_merge($this->normalizeSettings(), [
            'align' => $this->getAlign(),
        ]);
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
        return '<div class="ep-align ep-align--' . htmlspecialchars($align, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            . ' data-ep-align="' . htmlspecialchars($align, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
            . $html
            . '</div>';
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
