<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use Yii;

class PhasesBlock extends BaseBlock
{
    public const TYPE = 'phases';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Phases');
    }

    public function normalizeSettings(): array
    {
        $items = [];
        foreach ((array) ($this->settings['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $status = (string) ($item['status'] ?? 'upcoming');
            if (!in_array($status, ['done', 'current', 'upcoming'], true)) {
                $status = 'upcoming';
            }
            $items[] = [
                'label' => $label,
                'description' => trim((string) ($item['description'] ?? '')),
                'status' => $status,
            ];
        }

        return [
            'title' => $this->string('title', Yii::t('EngagementPagesModule.base', 'Project phases')),
            'style' => in_array(($this->settings['style'] ?? ''), ['linear', 'plan'], true)
                ? (string) $this->settings['style']
                : 'linear',
            'items' => $items,
        ];
    }
}
