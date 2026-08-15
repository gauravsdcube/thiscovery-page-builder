<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/**
 * Shared section POST parsing for global and space page editors.
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\modules\thiscoveryPageBuilder\blocks\CollectionBlock;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;
use Yii;

trait SectionPostParserTrait
{
    protected function parseSectionsFromPost(): array
    {
        $raw = Yii::$app->request->post('sections', []);
        if (!is_array($raw)) {
            return [];
        }

        $sections = [];
        foreach ($raw as $row) {
            $parsed = $this->parseSectionRow($row);
            if ($parsed !== null) {
                $sections[] = $parsed;
            }
        }

        return BlockRegistry::normalizeSections($sections);
    }

    protected function parseSectionRow($row): ?array
    {
        if (!is_array($row) || empty($row['type'])) {
            return null;
        }

        $type = (string) $row['type'];
        $settings = $row['settings'] ?? [];
        if (!is_array($settings)) {
            $settings = [];
        }

        if (in_array($type, ['downloads', 'phases', 'events', 'accordion'], true)
            && isset($settings['items']) && is_array($settings['items'])) {
            $settings['items'] = array_values(array_filter($settings['items'], 'is_array'));
        }

        if ($type === 'team' && isset($settings['people']) && is_array($settings['people'])) {
            $settings['people'] = array_values(array_filter($settings['people'], 'is_array'));
        }

        if ($type === 'container') {
            $settings['show_title'] = !empty($settings['show_title']);
            $settings['columns'] = \humhub\modules\thiscoveryPageBuilder\blocks\ContainerBlock::clampColumns(
                $settings['columns'] ?? 1
            );
        }
        if ($type === 'contact') {
            $settings['show_email_link'] = !empty($settings['show_email_link']);
        }
        if ($type === 'comments') {
            $settings['allow_guests'] = !empty($settings['allow_guests']);
            $settings['ask_name'] = !empty($settings['ask_name']);
            $settings['require_email'] = !empty($settings['require_email']);
            $settings['moderate_guests'] = !empty($settings['moderate_guests']);
            $settings['show_comments'] = !empty($settings['show_comments']);
        }
        if ($type === 'hero') {
            $settings['show_border'] = !empty($settings['show_border']);
        }
        if ($type === 'image') {
            $settings['use_as_card_image'] = !empty($settings['use_as_card_image']);
        }
        if ($type === 'collection' || $type === 'directory') {
            $settings['show_featured_first'] = !empty($settings['show_featured_first']);
            $settings['event_limit'] = CollectionBlock::clampEventLimit($settings['event_limit'] ?? CollectionBlock::EVENT_LIMIT_DEFAULT);
        }

        $section = [
            'type' => $type,
            'region' => (string) ($row['region'] ?? BlockRegistry::defaultRegion($type)),
            'settings' => $settings,
        ];

        if ($type === 'container') {
            $children = [];
            $maxCol = max(0, ((int) $settings['columns']) - 1);
            foreach ((array) ($row['children'] ?? []) as $childRow) {
                $child = $this->parseSectionRow($childRow);
                if ($child === null || ($child['type'] ?? '') === 'container') {
                    continue;
                }
                unset($child['region']);
                $col = (int) ($childRow['column'] ?? $child['column'] ?? 0);
                if ($col < 0) {
                    $col = 0;
                }
                if ($col > $maxCol) {
                    $col = $maxCol;
                }
                $child['column'] = $col;
                $children[] = $child;
            }
            $section['children'] = $children;
        }

        return $section;
    }

    protected function defaultSections(): array
    {
        return BlockRegistry::normalizeSections([
            ['type' => 'hero', 'region' => BlockRegistry::REGION_FULL, 'settings' => ['headline' => '', 'subheadline' => '']],
            [
                'type' => 'team',
                'region' => BlockRegistry::REGION_LEFT,
                'settings' => [
                    'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Meet the team'),
                    'people' => [
                        ['name' => '', 'role' => '', 'email' => '', 'phone' => '', 'bio' => ''],
                    ],
                ],
            ],
            ['type' => 'rich_text', 'region' => BlockRegistry::REGION_MAIN, 'settings' => ['title' => '', 'body' => '']],
            [
                'type' => 'phases',
                'region' => BlockRegistry::REGION_MAIN,
                'settings' => [
                    'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Project phases'),
                    'items' => [
                        ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Inform'), 'description' => '', 'status' => 'done'],
                        ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Have your say'), 'description' => '', 'status' => 'current'],
                        ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'What happens next'), 'description' => '', 'status' => 'upcoming'],
                    ],
                ],
            ],
            ['type' => 'survey_cta', 'region' => BlockRegistry::REGION_MAIN, 'settings' => []],
            ['type' => 'updates', 'region' => BlockRegistry::REGION_MAIN, 'settings' => []],
            ['type' => 'downloads', 'region' => BlockRegistry::REGION_MAIN, 'settings' => ['items' => []]],
        ]);
    }
}
