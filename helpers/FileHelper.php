<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\helpers;

use humhub\modules\file\models\File;

class FileHelper
{
    public static function findByGuid(?string $guid): ?File
    {
        $guid = trim((string) $guid);
        if ($guid === '') {
            return null;
        }
        return File::findOne(['guid' => $guid]);
    }

    public static function url(?string $guid): ?string
    {
        $file = self::findByGuid($guid);
        return $file ? $file->getUrl() : null;
    }

    public static function downloadUrl(?string $guid): ?string
    {
        $file = self::findByGuid($guid);
        return $file ? $file->getUrl(['download' => true]) : null;
    }

    public static function collectGuidsFromSections(array $sections): array
    {
        $guids = [];
        foreach ($sections as $section) {
            $settings = $section['settings'] ?? [];
            if (!empty($settings['image_guid'])) {
                $guids[] = (string) $settings['image_guid'];
            }
            foreach (['items', 'people'] as $listKey) {
                foreach (($settings[$listKey] ?? []) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (!empty($item['file_guid'])) {
                        $guids[] = (string) $item['file_guid'];
                    }
                    if (!empty($item['image_guid'])) {
                        $guids[] = (string) $item['image_guid'];
                    }
                }
            }
            if (!empty($section['children']) && is_array($section['children'])) {
                $guids = array_merge($guids, self::collectGuidsFromSections($section['children']));
            }
        }
        return array_values(array_unique(array_filter($guids)));
    }
}
