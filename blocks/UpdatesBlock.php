<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use Yii;

class UpdatesBlock extends BaseBlock
{
    public const TYPE = 'updates';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Get updates');
    }

    public function normalizeSettings(): array
    {
        return [
            'title' => $this->string('title', Yii::t('EngagementPagesModule.base', 'Get updates')),
            'intro' => $this->string(
                'intro',
                Yii::t('EngagementPagesModule.base', 'Leave your email to hear about progress on this engagement.')
            ),
            'button_label' => $this->string('button_label', Yii::t('EngagementPagesModule.base', 'Subscribe')),
            'success_message' => $this->string(
                'success_message',
                Yii::t('EngagementPagesModule.base', 'Thanks — we will keep you updated.')
            ),
        ];
    }
}
