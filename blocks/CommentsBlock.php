<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

class CommentsBlock extends BaseBlock
{
    public const TYPE = 'comments';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Comments');
    }

    public function normalizeSettings(): array
    {
        return [
            'title' => $this->string('title', Yii::t('ThiscoveryPageBuilderModule.base', 'Comments')),
            'intro' => $this->string(
                'intro',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Share your thoughts. Comments are moderated before they appear.')
            ),
            'allow_guests' => !isset($this->settings['allow_guests']) || !empty($this->settings['allow_guests']),
            'ask_name' => !isset($this->settings['ask_name']) || !empty($this->settings['ask_name']),
            'require_email' => !isset($this->settings['require_email']) || !empty($this->settings['require_email']),
            'moderate_guests' => !isset($this->settings['moderate_guests']) || !empty($this->settings['moderate_guests']),
            'show_comments' => !isset($this->settings['show_comments']) || !empty($this->settings['show_comments']),
            'success_message' => $this->string(
                'success_message',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Thanks — your comment has been submitted for review.')
            ),
        ];
    }
}
