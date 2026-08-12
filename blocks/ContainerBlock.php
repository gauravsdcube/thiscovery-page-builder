<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use humhub\modules\engagementPages\models\EngagementPage;
use humhub\modules\engagementPages\services\BlockRegistry;
use Yii;

class ContainerBlock extends BaseBlock
{
    public const TYPE = 'container';

    public const MIN_COLUMNS = 1;
    public const MAX_COLUMNS = 4;

    /** @var array nested hydrated children */
    public array $children = [];

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Grid container');
    }

    public static function columnOptions(): array
    {
        return [
            1 => Yii::t('EngagementPagesModule.base', '1 column'),
            2 => Yii::t('EngagementPagesModule.base', '2 columns'),
            3 => Yii::t('EngagementPagesModule.base', '3 columns'),
            4 => Yii::t('EngagementPagesModule.base', '4 columns'),
        ];
    }

    public static function clampColumns($value): int
    {
        $columns = (int) $value;
        if ($columns < self::MIN_COLUMNS) {
            return self::MIN_COLUMNS;
        }
        if ($columns > self::MAX_COLUMNS) {
            return self::MAX_COLUMNS;
        }
        return $columns;
    }

    public function normalizeSettings(): array
    {
        return [
            'title' => $this->string('title', Yii::t('EngagementPagesModule.base', 'Container')),
            'show_title' => !isset($this->settings['show_title']) || (bool) $this->settings['show_title'],
            'columns' => self::clampColumns($this->settings['columns'] ?? 1),
        ];
    }

    public function getColumns(): int
    {
        return self::clampColumns($this->settings['columns'] ?? 1);
    }

    public function render(EngagementPage $page): string
    {
        $html = Yii::$app->view->render(
            '@engagement-pages/views/blocks/container',
            [
                'block' => $this,
                'page' => $page,
                'settings' => $this->getPersistedSettings(),
                'children' => $this->children,
            ]
        );

        return $this->wrapAligned($html);
    }

    public function setChildren(array $children): void
    {
        $this->children = [];
        $maxCol = max(0, $this->getColumns() - 1);
        foreach ($children as $child) {
            if (!is_array($child) || empty($child['type'])) {
                continue;
            }
            $nested = BlockRegistry::create((string) $child['type'], (array) ($child['settings'] ?? []));
            if ($nested === null || $nested instanceof self) {
                continue;
            }
            $column = (int) ($child['column'] ?? 0);
            if ($column < 0) {
                $column = 0;
            }
            if ($column > $maxCol) {
                $column = $maxCol;
            }
            $child['column'] = $column;
            $this->children[] = [
                'block' => $nested,
                'section' => $child,
            ];
        }
    }

    /**
     * @return array<int, array> children grouped by column index
     */
    public function childrenByColumn(): array
    {
        $columns = $this->getColumns();
        $grouped = array_fill(0, $columns, []);
        foreach ($this->children as $child) {
            $col = (int) ($child['section']['column'] ?? 0);
            if ($col < 0 || $col >= $columns) {
                $col = 0;
            }
            $grouped[$col][] = $child;
        }
        return $grouped;
    }
}
