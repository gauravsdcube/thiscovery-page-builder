<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use Yii;

/**
 * Reusable collection of related items (pages, forms, spaces, …).
 */
class CollectionBlock extends BaseBlock
{
    public const TYPE = 'collection';

    /** @deprecated legacy section type stored before rename */
    public const LEGACY_TYPE = 'directory';

    public const SOURCE_PAGES = 'pages';
    public const SOURCE_FORMS = 'forms';
    public const SOURCE_SPACES = 'spaces';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Collection');
    }

    protected function getDefaultAlign(): string
    {
        return self::ALIGN_CENTER;
    }

    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_PAGES => Yii::t('EngagementPagesModule.base', 'Pages'),
            self::SOURCE_FORMS => Yii::t('EngagementPagesModule.base', 'Forms'),
            self::SOURCE_SPACES => Yii::t('EngagementPagesModule.base', 'Spaces'),
        ];
    }

    public function normalizeSettings(): array
    {
        $source = (string) ($this->settings['source'] ?? self::SOURCE_PAGES);
        if (!isset(self::sourceOptions()[$source])) {
            $source = self::SOURCE_PAGES;
        }

        return [
            'title' => $this->string(
                'title',
                Yii::t('EngagementPagesModule.base', 'Open for feedback')
            ),
            'source' => $source,
            'empty_message' => $this->string(
                'empty_message',
                Yii::t('EngagementPagesModule.base', 'Nothing to show yet.')
            ),
            'show_featured_first' => !isset($this->settings['show_featured_first'])
                || !empty($this->settings['show_featured_first']),
        ];
    }
}
