<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260912_170000_page_folders extends Migration
{
    public function safeUp()
    {
        $folderTable = $this->db->schema->getTableSchema('thiscovery_page_folder', true);
        if ($folderTable === null) {
            $this->createTable('thiscovery_page_folder', [
                'id' => $this->primaryKey(),
                'parent_id' => $this->integer()->null(),
                'contentcontainer_id' => $this->integer()->null(),
                'name' => $this->string(255)->notNull(),
                'description' => $this->text()->null(),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
                'created_at' => $this->dateTime()->null(),
                'created_by' => $this->integer()->null(),
                'updated_at' => $this->dateTime()->null(),
                'updated_by' => $this->integer()->null(),
            ]);
            $this->createIndex('idx_tpb_folder_parent', 'thiscovery_page_folder', ['contentcontainer_id', 'parent_id']);
            $this->addForeignKey(
                'fk_tpb_folder_parent',
                'thiscovery_page_folder',
                'parent_id',
                'thiscovery_page_folder',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        $pageTable = $this->db->schema->getTableSchema('thiscovery_page', true);
        if ($pageTable !== null && !isset($pageTable->columns['folder_id'])) {
            $this->addColumn('thiscovery_page', 'folder_id', $this->integer()->null());
            $this->createIndex('idx_tpb_page_folder', 'thiscovery_page', 'folder_id');
            $this->addForeignKey(
                'fk_tpb_page_folder',
                'thiscovery_page',
                'folder_id',
                'thiscovery_page_folder',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $pageTable = $this->db->schema->getTableSchema('thiscovery_page', true);
        if ($pageTable !== null && isset($pageTable->columns['folder_id'])) {
            $this->dropForeignKey('fk_tpb_page_folder', 'thiscovery_page');
            $this->dropIndex('idx_tpb_page_folder', 'thiscovery_page');
            $this->dropColumn('thiscovery_page', 'folder_id');
        }
        if ($this->db->schema->getTableSchema('thiscovery_page_folder', true) !== null) {
            $this->dropTable('thiscovery_page_folder');
        }
    }
}
