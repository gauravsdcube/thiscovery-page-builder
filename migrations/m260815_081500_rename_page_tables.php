<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

/**
 * Rename page tables to match Thiscovery Page Builder.
 */
class m260815_081500_rename_page_tables extends Migration
{
    private const TABLES = [
        'engagement_page' => 'thiscovery_page',
        'engagement_page_follow' => 'thiscovery_page_follow',
        'engagement_page_comment' => 'thiscovery_page_comment',
    ];

    private const INDEXES = [
        'thiscovery_page' => [
            'idx_engagement_page_slug' => 'idx_thiscovery_page_slug',
            'idx_engagement_page_status' => 'idx_thiscovery_page_status',
            'idx_ep_audience' => 'idx_thiscovery_page_audience',
            'idx_ep_is_template' => 'idx_thiscovery_page_is_template',
            'idx_ep_is_directory' => 'idx_thiscovery_page_is_directory',
        ],
        'thiscovery_page_follow' => [
            'idx_ep_follow_page_email' => 'idx_thiscovery_page_follow_page_email',
        ],
        'thiscovery_page_comment' => [
            'idx_ep_comment_page_status' => 'idx_thiscovery_page_comment_page_status',
            'idx_ep_comment_created' => 'idx_thiscovery_page_comment_created',
        ],
    ];

    public function safeUp()
    {
        $this->dropFkIfExists('fk_ep_follow_page', 'engagement_page_follow');
        $this->dropFkIfExists('fk_ep_comment_page', 'engagement_page_comment');

        foreach (self::TABLES as $from => $to) {
            $this->renameTableIfExists($from, $to);
        }

        foreach (self::INDEXES as $table => $map) {
            foreach ($map as $old => $new) {
                $this->renameIndexIfExists($table, $old, $new);
            }
        }

        if ($this->db->schema->getTableSchema('thiscovery_page_follow', true) !== null) {
            $this->addForeignKey(
                'fk_thiscovery_page_follow_page',
                'thiscovery_page_follow',
                'page_id',
                'thiscovery_page',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
        if ($this->db->schema->getTableSchema('thiscovery_page_comment', true) !== null) {
            $this->addForeignKey(
                'fk_thiscovery_page_comment_page',
                'thiscovery_page_comment',
                'page_id',
                'thiscovery_page',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        $this->renamePermissionId('engagement_pages_create_global', 'thiscovery_page_builder_create_global');
        $this->renamePermissionId('engagement_pages_manage_global', 'thiscovery_page_builder_manage_global');
    }

    public function safeDown()
    {
        echo "m260815_081500_rename_page_tables cannot be reverted.\n";
        return false;
    }

    private function renameTableIfExists(string $from, string $to): void
    {
        if ($this->db->schema->getTableSchema($from, true) === null) {
            return;
        }
        if ($this->db->schema->getTableSchema($to, true) !== null) {
            return;
        }
        $this->renameTable($from, $to);
    }

    private function dropFkIfExists(string $name, string $table): void
    {
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }
        try {
            $this->dropForeignKey($name, $table);
        } catch (\Throwable $e) {
            // Already dropped or named differently.
        }
    }

    private function renameIndexIfExists(string $table, string $old, string $new): void
    {
        if ($this->db->schema->getTableSchema($table, true) === null) {
            return;
        }
        $names = [];
        foreach ($this->db->schema->getTableIndexes($table) as $index) {
            $names[] = $index->name;
        }
        if (in_array($new, $names, true) || !in_array($old, $names, true)) {
            return;
        }
        $this->execute('ALTER TABLE `' . str_replace('`', '``', $table) . '` RENAME INDEX `' . str_replace('`', '``', $old) . '` TO `' . str_replace('`', '``', $new) . '`');
    }

    private function renamePermissionId(string $old, string $new): void
    {
        foreach (['group_permission', 'contentcontainer_permission'] as $table) {
            if ($this->db->schema->getTableSchema($table, true) === null) {
                continue;
            }
            $this->update($table, ['permission_id' => $new], ['permission_id' => $old]);
        }
    }
}
