<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use Yii;

/**
 * Portrait contact cards with optional photo (circular crop, accent ring).
 */
class ContactCardBlock extends BaseBlock
{
    public const TYPE = 'contact_card';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Contact card');
    }

    public function normalizeSettings(): array
    {
        $people = [];
        foreach ((array) ($this->settings['people'] ?? []) as $person) {
            if (!is_array($person)) {
                continue;
            }
            $name = trim((string) ($person['name'] ?? ''));
            $role = trim((string) ($person['role'] ?? ''));
            $organisation = trim((string) ($person['organisation'] ?? ''));
            $email = trim((string) ($person['email'] ?? ''));
            $imageGuid = trim((string) ($person['image_guid'] ?? ''));
            $imageAlt = trim((string) ($person['image_alt'] ?? ''));
            if ($name === '' && $role === '' && $organisation === '' && $email === '' && $imageGuid === '') {
                continue;
            }
            $people[] = [
                'image_guid' => $imageGuid,
                'image_alt' => $imageAlt,
                'name' => $name,
                'role' => $role,
                'organisation' => $organisation,
                'email' => $email,
            ];
        }

        return [
            'title' => $this->string('title'),
            'intro' => $this->string('intro'),
            'people' => $people,
        ];
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            if (mb_strlen($letters) >= 2) {
                break;
            }
        }
        return $letters !== '' ? $letters : '?';
    }
}
