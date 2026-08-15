<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

/**
 * Single contact / enquiry details block (distinct from multi-person Team).
 */
class ContactBlock extends BaseBlock
{
    public const TYPE = 'contact';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Contact');
    }

    public function normalizeSettings(): array
    {
        return [
            'title' => $this->string('title', Yii::t('ThiscoveryPageBuilderModule.base', 'Contact us')),
            'name' => $this->string('name'),
            'role' => $this->string('role'),
            'organisation' => $this->string('organisation'),
            'email' => $this->string('email'),
            'phone' => $this->string('phone'),
            'address' => $this->string('address'),
            'website' => $this->string('website'),
            'website_label' => $this->string(
                'website_label',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Visit website')
            ),
            'notes' => $this->string('notes'),
            'show_email_link' => !isset($this->settings['show_email_link']) || (bool) $this->settings['show_email_link'],
        ];
    }
}
