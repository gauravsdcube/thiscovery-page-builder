<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use Yii;

class TeamBlock extends BaseBlock
{
    public const TYPE = 'team';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Team / contacts');
    }

    public function normalizeSettings(): array
    {
        $people = [];
        foreach ((array) ($this->settings['people'] ?? []) as $person) {
            if (!is_array($person)) {
                continue;
            }
            $name = trim((string) ($person['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $people[] = [
                'name' => $name,
                'role' => trim((string) ($person['role'] ?? '')),
                'email' => trim((string) ($person['email'] ?? '')),
                'phone' => trim((string) ($person['phone'] ?? '')),
                'bio' => trim((string) ($person['bio'] ?? '')),
            ];
        }

        return [
            'title' => $this->string('title', Yii::t('EngagementPagesModule.base', 'Meet the team')),
            'intro' => $this->string('intro'),
            'people' => $people,
        ];
    }
}
