<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;

/**
 * Reusable collection of related items (pages, forms, spaces, calendar).
 */
class CollectionBlock extends BaseBlock
{
    public const TYPE = 'collection';

    /** @deprecated legacy section type stored before rename */
    public const LEGACY_TYPE = 'directory';

    public const SOURCE_PAGES = 'pages';
    public const SOURCE_FORMS = 'forms';
    public const SOURCE_SPACES = 'spaces';
    public const SOURCE_CALENDAR = 'calendar';

    /** Every published page marked “Show in directory”. */
    public const SCOPE_LISTED = 'listed';
    /** Published child pages of a chosen collection. */
    public const SCOPE_COLLECTION = 'collection';
    /** Published child pages of the page that holds this block. */
    public const SCOPE_CHILDREN = 'children';

    public const EVENT_LIMIT_MIN = 1;
    public const EVENT_LIMIT_MAX = 30;
    public const EVENT_LIMIT_DEFAULT = 5;

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Collection');
    }

    protected function getDefaultAlign(): string
    {
        return self::ALIGN_CENTER;
    }

    public static function calendarEnabled(): bool
    {
        try {
            if (!Yii::$app->hasModule('calendar')) {
                return false;
            }
            $module = Yii::$app->getModule('calendar');
            return $module !== null
                && class_exists('humhub\\modules\\calendar\\interfaces\\CalendarService');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function sourceOptions(): array
    {
        $options = [
            self::SOURCE_PAGES => Yii::t('ThiscoveryPageBuilderModule.base', 'Pages'),
            self::SOURCE_FORMS => Yii::t('ThiscoveryPageBuilderModule.base', 'Forms'),
            self::SOURCE_SPACES => Yii::t('ThiscoveryPageBuilderModule.base', 'Spaces'),
        ];
        if (self::calendarEnabled()) {
            $options[self::SOURCE_CALENDAR] = Yii::t('ThiscoveryPageBuilderModule.base', 'Calendar');
        }
        return $options;
    }

    public static function pageScopeOptions(): array
    {
        return [
            self::SCOPE_LISTED => Yii::t('ThiscoveryPageBuilderModule.base', 'All pages shown in the directory'),
            self::SCOPE_COLLECTION => Yii::t('ThiscoveryPageBuilderModule.base', 'Pages from a collection'),
            self::SCOPE_CHILDREN => Yii::t('ThiscoveryPageBuilderModule.base', 'Child pages of this page'),
        ];
    }

    /**
     * @return EngagementPage[]
     */
    public static function pagesFor(array $settings, ?EngagementPage $host): array
    {
        $scope = (string) ($settings['page_scope'] ?? self::SCOPE_LISTED);
        if ($scope === self::SCOPE_COLLECTION) {
            return EngagementPage::findPublishedChildren((int) ($settings['collection_id'] ?? 0));
        }
        if ($scope === self::SCOPE_CHILDREN && $host !== null) {
            return EngagementPage::findPublishedChildren((int) $host->id);
        }
        return EngagementPage::findDirectoryPages();
    }

    public static function linkedCollection(array $settings): ?EngagementPage
    {
        if (($settings['page_scope'] ?? '') !== self::SCOPE_COLLECTION) {
            return null;
        }
        $id = (int) ($settings['collection_id'] ?? 0);
        if ($id < 1) {
            return null;
        }
        $page = EngagementPage::findOne($id);
        if (!$page instanceof EngagementPage || !$page->isCollection() || !$page->isPublished() || $page->isTemplate()) {
            return null;
        }
        return $page;
    }

    public static function clampEventLimit($value): int
    {
        $limit = (int) $value;
        if ($limit < self::EVENT_LIMIT_MIN) {
            return self::EVENT_LIMIT_DEFAULT;
        }
        if ($limit > self::EVENT_LIMIT_MAX) {
            return self::EVENT_LIMIT_MAX;
        }
        return $limit;
    }

    /**
     * Upcoming calendar events visible to the current user (including guests).
     * Space pages list that space’s events; global pages list site-wide upcoming events.
     *
     * @return array
     */
    public static function findUpcomingEvents(
        int $limit = self::EVENT_LIMIT_DEFAULT,
        int $days = 365,
        ?EngagementPage $page = null
    ): array {
        if (!self::calendarEnabled()) {
            return [];
        }
        try {
            $class = 'humhub\\modules\\calendar\\interfaces\\CalendarService';
            $module = Yii::$app->getModule('calendar');
            $service = $module->has($class) ? $module->get($class) : Yii::createObject($class);
            $container = self::pageContainer($page);
            $entries = $service->getUpcomingEntries($container, $days, $limit);
            return is_array($entries) ? $entries : [];
        } catch (\Throwable $e) {
            Yii::warning('Engagement Pages calendar collection failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            return [];
        }
    }

    public static function calendarUrl(?EngagementPage $page = null): string
    {
        $class = 'humhub\\modules\\calendar\\helpers\\Url';
        if (!class_exists($class)) {
            return '';
        }
        return $class::toCalendar(self::pageContainer($page));
    }

    private static function pageContainer(?EngagementPage $page): ?ContentContainerActiveRecord
    {
        if ($page === null) {
            return null;
        }
        try {
            $container = $page->content->container ?? null;
            return $container instanceof ContentContainerActiveRecord ? $container : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function formatEventTime($entry): string
    {
        $formatterClass = 'humhub\\modules\\calendar\\models\\CalendarDateFormatter';
        if (class_exists($formatterClass)) {
            try {
                return (string) (new $formatterClass(['calendarItem' => $entry]))->getFormattedTime('medium');
            } catch (\Throwable $e) {
            }
        }
        if (is_object($entry) && method_exists($entry, 'getStartDateTime')) {
            try {
                return (string) Yii::$app->formatter->asDatetime($entry->getStartDateTime(), 'medium');
            } catch (\Throwable $e) {
            }
        }
        return '';
    }

    public function normalizeSettings(): array
    {
        $source = (string) ($this->settings['source'] ?? self::SOURCE_PAGES);
        if (!isset(self::sourceOptions()[$source])) {
            $source = self::SOURCE_PAGES;
        }
        $scope = (string) ($this->settings['page_scope'] ?? self::SCOPE_LISTED);
        if (!isset(self::pageScopeOptions()[$scope])) {
            $scope = self::SCOPE_LISTED;
        }
        $collectionId = (int) ($this->settings['collection_id'] ?? 0);

        return [
            'title' => $this->string(
                'title',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Open for feedback')
            ),
            'source' => $source,
            'page_scope' => $scope,
            'collection_id' => $collectionId > 0 ? $collectionId : '',
            'empty_message' => $this->string(
                'empty_message',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Nothing to show yet.')
            ),
            'show_featured_first' => !isset($this->settings['show_featured_first'])
                || !empty($this->settings['show_featured_first']),
            'event_limit' => self::clampEventLimit($this->settings['event_limit'] ?? self::EVENT_LIMIT_DEFAULT),
            'more_label' => $this->string(
                'more_label',
                Yii::t('ThiscoveryPageBuilderModule.base', 'View calendar')
            ),
        ];
    }
}
