<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

/**
 * Rename HumHub module id engagement-pages → thiscovery-page-builder.
 * Page tables stay engagement_page* until m260815_081500.
 */
class m260815_080000_rename_module_id extends Migration
{
    private const OLD_ID = 'engagement-pages';
    private const NEW_ID = 'thiscovery-page-builder';
    private const OLD_NS = 'humhub\\modules\\engagementPages';
    private const NEW_NS = 'humhub\\modules\\thiscoveryPageBuilder';

    public function safeUp()
    {
        $this->renameModuleId('module_enabled', 'module_id');
        $this->renameModuleId('contentcontainer_module', 'module_id');
        $this->renameModuleId('group_permission', 'module_id');
        $this->renameModuleId('contentcontainer_permission', 'module_id');
        $this->renameModuleId('setting', 'module_id');
        $this->renameModuleId('contentcontainer_setting', 'module_id');

        $this->renameClassColumn('content', 'object_model');
        $this->renameClassColumn('file', 'object_model');
        $this->renameClassColumn('group_permission', 'class');
        $this->renameClassColumn('contentcontainer_permission', 'class');
        $this->renameClassColumn('notification', 'class');
        $this->renameClassColumn('activity', 'class');
    }

    public function safeDown()
    {
        echo "m260815_080000_rename_module_id cannot be reverted.\n";
        return false;
    }

    private function renameModuleId(string $table, string $column): void
    {
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }
        $this->update($table, [$column => self::NEW_ID], [$column => self::OLD_ID]);
    }

    private function renameClassColumn(string $table, string $column): void
    {
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null || $schema->getColumn($column) === null) {
            return;
        }
        $this->update(
            $table,
            [$column => self::NEW_NS . '\\models\\EngagementPage'],
            [$column => self::OLD_NS . '\\models\\EngagementPage']
        );
        foreach (['CreatePage', 'ManagePages', 'CreateGlobalPage', 'ManageGlobalPage'] as $perm) {
            $this->update(
                $table,
                [$column => self::NEW_NS . '\\permissions\\' . $perm],
                [$column => self::OLD_NS . '\\permissions\\' . $perm]
            );
        }
        $this->update(
            $table,
            [$column => self::NEW_NS . '\\Module'],
            [$column => self::OLD_NS . '\\Module']
        );
    }
}
