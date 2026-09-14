<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageFolder;

class PageFolderService
{
    /**
     * @return array<int, string>
     */
    public static function treeOptions($container = null, ?int $excludeId = null): array
    {
        $out = [];
        foreach (self::walk($container) as $row) {
            /** @var PageFolder $folder */
            $folder = $row['folder'];
            if ($excludeId && ((int) $folder->id === $excludeId || $folder->isDescendantOf($excludeId))) {
                continue;
            }
            $prefix = $row['depth'] > 0 ? str_repeat('— ', $row['depth']) : '';
            $out[(int) $folder->id] = $prefix . $folder->name;
        }
        return $out;
    }

    /**
     * @return array<int, array{folder: PageFolder, depth: int}>
     */
    public static function walk($container = null, ?int $parentId = null, int $depth = 0): array
    {
        $out = [];
        $query = PageFolder::findForContainer($container);
        if ($parentId) {
            $query->andWhere(['parent_id' => $parentId]);
        } else {
            $query->andWhere(['parent_id' => null]);
        }
        foreach ($query->all() as $folder) {
            $out[] = ['folder' => $folder, 'depth' => $depth];
            $out = array_merge($out, self::walk($container, (int) $folder->id, $depth + 1));
        }
        return $out;
    }

    public static function pageCount(PageFolder $folder): int
    {
        return (int) EngagementPage::find()
            ->where(['folder_id' => (int) $folder->id, 'is_template' => 0])
            ->andWhere(['or', ['parent_id' => null], ['parent_id' => 0]])
            ->count();
    }

    public static function childCount(PageFolder $folder): int
    {
        return (int) PageFolder::find()->where(['parent_id' => (int) $folder->id])->count();
    }

    public static function findFolder($container, int $id): ?PageFolder
    {
        if ($id < 1 || !self::tablesReady()) {
            return null;
        }
        try {
            return PageFolder::findForContainer($container)->andWhere(['id' => $id])->one();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function tablesReady(): bool
    {
        try {
            return PageFolder::getTableSchema() !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
