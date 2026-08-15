<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\thiscoveryPageBuilder\blocks\AccordionBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\CalloutBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\CollectionBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\CommentsBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\ContactBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\ContainerBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\DownloadsBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\EventsBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\HeroBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\ImageBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\PhasesBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\PollEmbedBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\RichTextBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\SurveyCtaBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\TeamBlock;
use humhub\modules\thiscoveryPageBuilder\blocks\UpdatesBlock;
use Yii;

class BlockRegistry
{
    public const REGION_FULL = 'full';
    public const REGION_LEFT = 'left';
    public const REGION_MAIN = 'main';
    public const REGION_RIGHT = 'right';

    public const LAYOUT_MAIN = 'main';
    public const LAYOUT_LEFT = 'left';
    public const LAYOUT_RIGHT = 'right';
    public const LAYOUT_BOTH = 'both';

    public static function types(): array
    {
        return [
            HeroBlock::TYPE => HeroBlock::class,
            RichTextBlock::TYPE => RichTextBlock::class,
            SurveyCtaBlock::TYPE => SurveyCtaBlock::class,
            PollEmbedBlock::TYPE => PollEmbedBlock::class,
            DownloadsBlock::TYPE => DownloadsBlock::class,
            ContainerBlock::TYPE => ContainerBlock::class,
            PhasesBlock::TYPE => PhasesBlock::class,
            EventsBlock::TYPE => EventsBlock::class,
            TeamBlock::TYPE => TeamBlock::class,
            ContactBlock::TYPE => ContactBlock::class,
            UpdatesBlock::TYPE => UpdatesBlock::class,
            CommentsBlock::TYPE => CommentsBlock::class,
            AccordionBlock::TYPE => AccordionBlock::class,
            CalloutBlock::TYPE => CalloutBlock::class,
            ImageBlock::TYPE => ImageBlock::class,
            CollectionBlock::TYPE => CollectionBlock::class,
            // Legacy type name before rename to Collection
            CollectionBlock::LEGACY_TYPE => CollectionBlock::class,
        ];
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::types() as $type => $class) {
            if ($type === CollectionBlock::LEGACY_TYPE) {
                continue; // avoid duplicate palette/editor label
            }
            $labels[$type] = (new $class())->getLabel();
        }
        return $labels;
    }

    public static function palette(): array
    {
        return [
            ['type' => HeroBlock::TYPE, 'icon' => 'fa-picture-o', 'group' => 'layout'],
            ['type' => ContainerBlock::TYPE, 'icon' => 'fa-th', 'group' => 'layout'],
            ['type' => ImageBlock::TYPE, 'icon' => 'fa-image', 'group' => 'layout'],
            ['type' => RichTextBlock::TYPE, 'icon' => 'fa-paragraph', 'group' => 'content'],
            ['type' => AccordionBlock::TYPE, 'icon' => 'fa-list-alt', 'group' => 'content'],
            ['type' => CalloutBlock::TYPE, 'icon' => 'fa-info-circle', 'group' => 'content'],
            ['type' => DownloadsBlock::TYPE, 'icon' => 'fa-download', 'group' => 'content'],
            ['type' => SurveyCtaBlock::TYPE, 'icon' => 'fa-wpforms', 'group' => 'engagement'],
            ['type' => PollEmbedBlock::TYPE, 'icon' => 'fa-bar-chart', 'group' => 'engagement'],
            ['type' => PhasesBlock::TYPE, 'icon' => 'fa-road', 'group' => 'engagement'],
            ['type' => EventsBlock::TYPE, 'icon' => 'fa-calendar', 'group' => 'engagement'],
            ['type' => TeamBlock::TYPE, 'icon' => 'fa-users', 'group' => 'engagement'],
            ['type' => ContactBlock::TYPE, 'icon' => 'fa-address-card', 'group' => 'engagement'],
            ['type' => UpdatesBlock::TYPE, 'icon' => 'fa-envelope-o', 'group' => 'engagement'],
            ['type' => CommentsBlock::TYPE, 'icon' => 'fa-comments', 'group' => 'engagement'],
            ['type' => CollectionBlock::TYPE, 'icon' => 'fa-th-list', 'group' => 'engagement'],
        ];
    }

    public static function defaultRegion(string $type): string
    {
        return match ($type) {
            HeroBlock::TYPE => self::REGION_FULL,
            TeamBlock::TYPE, ContactBlock::TYPE => self::REGION_LEFT,
            CollectionBlock::TYPE, CollectionBlock::LEGACY_TYPE => self::REGION_MAIN,
            default => self::REGION_MAIN,
        };
    }

    public static function layoutOptions(): array
    {
        return [
            self::LAYOUT_MAIN => Yii::t('ThiscoveryPageBuilderModule.base', 'Main column only'),
            self::LAYOUT_LEFT => Yii::t('ThiscoveryPageBuilderModule.base', 'Left + main'),
            self::LAYOUT_RIGHT => Yii::t('ThiscoveryPageBuilderModule.base', 'Main + right'),
            self::LAYOUT_BOTH => Yii::t('ThiscoveryPageBuilderModule.base', 'Left + main + right'),
        ];
    }

    public static function regionsForLayout(string $layout): array
    {
        return match ($layout) {
            self::LAYOUT_LEFT => [self::REGION_FULL, self::REGION_LEFT, self::REGION_MAIN],
            self::LAYOUT_RIGHT => [self::REGION_FULL, self::REGION_MAIN, self::REGION_RIGHT],
            self::LAYOUT_BOTH => [self::REGION_FULL, self::REGION_LEFT, self::REGION_MAIN, self::REGION_RIGHT],
            default => [self::REGION_FULL, self::REGION_MAIN],
        };
    }

    public static function create(string $type, array $settings = [])
    {
        $map = self::types();
        if (!isset($map[$type])) {
            return null;
        }
        $class = $map[$type];
        return new $class($settings);
    }

    public static function normalizeSections(array $sections): array
    {
        $out = [];
        foreach ($sections as $section) {
            $normalized = self::normalizeSection($section);
            if ($normalized !== null) {
                $out[] = $normalized;
            }
        }
        return $out;
    }

    public static function normalizeSection($section): ?array
    {
        if (!is_array($section) || empty($section['type'])) {
            return null;
        }
        $type = (string) $section['type'];
        if ($type === CollectionBlock::LEGACY_TYPE) {
            $type = CollectionBlock::TYPE;
        }
        $block = self::create($type, (array) ($section['settings'] ?? []));
        if ($block === null) {
            return null;
        }

        $region = (string) ($section['region'] ?? self::defaultRegion($type));
        if (!in_array($region, [self::REGION_FULL, self::REGION_LEFT, self::REGION_MAIN, self::REGION_RIGHT], true)) {
            $region = self::defaultRegion($type);
        }
        if ($type === HeroBlock::TYPE && empty($section['region'])) {
            $region = self::REGION_FULL;
        }
        if (in_array($type, [TeamBlock::TYPE, ContactBlock::TYPE], true) && empty($section['region'])) {
            $region = self::REGION_LEFT;
        }

        $children = [];
        if ($type === ContainerBlock::TYPE) {
            $columns = ContainerBlock::clampColumns($block->getPersistedSettings()['columns'] ?? 1);
            $maxCol = max(0, $columns - 1);
            foreach ((array) ($section['children'] ?? []) as $child) {
                if (!is_array($child) || ($child['type'] ?? '') === ContainerBlock::TYPE) {
                    continue;
                }
                $normalizedChild = self::normalizeSection($child);
                if ($normalizedChild !== null) {
                    unset($normalizedChild['region']);
                    $col = (int) ($child['column'] ?? $normalizedChild['column'] ?? 0);
                    if ($col < 0) {
                        $col = 0;
                    }
                    if ($col > $maxCol) {
                        $col = $maxCol;
                    }
                    $normalizedChild['column'] = $col;
                    $children[] = $normalizedChild;
                }
            }
        }

        $result = [
            'type' => $block->getType(),
            'region' => $region,
            'settings' => $block->getPersistedSettings(),
        ];
        if ($type === ContainerBlock::TYPE) {
            $result['children'] = $children;
        }
        return $result;
    }

    public static function groupByRegion(array $sections): array
    {
        $grouped = [
            self::REGION_FULL => [],
            self::REGION_LEFT => [],
            self::REGION_MAIN => [],
            self::REGION_RIGHT => [],
        ];
        foreach ($sections as $section) {
            $region = $section['region'] ?? self::REGION_MAIN;
            if (!isset($grouped[$region])) {
                $region = self::REGION_MAIN;
            }
            $grouped[$region][] = $section;
        }
        return $grouped;
    }
}
