<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

/**
 * Multi-root collections, page-bound space, top menu flags, site homepage targets.
 */
class m260820_150000_page_hierarchy_space_nav extends Migration
{
    public function safeUp()
    {
        $table = 'thiscovery_page';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        if ($schema->getColumn('parent_id') === null) {
            $this->addColumn($table, 'parent_id', $this->integer()->null()->after('id'));
            $this->createIndex('idx_thiscovery_page_parent_id', $table, 'parent_id');
        }
        if ($schema->getColumn('is_collection') === null) {
            $this->addColumn($table, 'is_collection', $this->boolean()->notNull()->defaultValue(false)->after('is_directory'));
            $this->createIndex('idx_thiscovery_page_is_collection', $table, 'is_collection');
        }
        if ($schema->getColumn('bound_space_id') === null) {
            $this->addColumn($table, 'bound_space_id', $this->integer()->null());
            $this->createIndex('idx_thiscovery_page_bound_space', $table, 'bound_space_id');
        }
        if ($schema->getColumn('show_in_top_menu') === null) {
            $this->addColumn($table, 'show_in_top_menu', $this->boolean()->notNull()->defaultValue(false));
        }
        if ($schema->getColumn('top_menu_label') === null) {
            $this->addColumn($table, 'top_menu_label', $this->string(64)->null());
        }
        if ($schema->getColumn('top_menu_sort_order') === null) {
            $this->addColumn($table, 'top_menu_sort_order', $this->integer()->notNull()->defaultValue(400));
        }
        if ($schema->getColumn('top_menu_visibility') === null) {
            $this->addColumn($table, 'top_menu_visibility', $this->string(16)->notNull()->defaultValue('all'));
        }

        // Directory homepage becomes a collection; former child pages nest under it.
        $this->update($table, ['is_collection' => 1], ['is_directory' => 1]);

        $homeId = (new \yii\db\Query())
            ->select('id')
            ->from($table)
            ->where(['is_directory' => 1])
            ->scalar();

        if ($homeId) {
            $condition = ['and', ['is_directory' => 0], ['parent_id' => null]];
            if ($schema->getColumn('is_template') !== null) {
                $condition[] = ['is_template' => 0];
            }
            $this->update($table, ['parent_id' => (int) $homeId], $condition);
        }

        if ($this->db->schema->getTableSchema('thiscovery_page_home', true) === null) {
            $this->createTable('thiscovery_page_home', [
                'id' => $this->primaryKey(),
                'target' => $this->string(16)->notNull(),
                'group_id' => $this->integer()->null(),
                'page_id' => $this->integer()->notNull(),
                'priority' => $this->integer()->notNull()->defaultValue(100),
                'enabled' => $this->boolean()->notNull()->defaultValue(true),
                'created_at' => $this->dateTime()->null(),
                'updated_at' => $this->dateTime()->null(),
            ]);
            $this->createIndex('idx_tph_target_group', 'thiscovery_page_home', ['target', 'group_id']);
            $this->createIndex('idx_tph_page_id', 'thiscovery_page_home', 'page_id');
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('thiscovery_page_home', true) !== null) {
            $this->dropTable('thiscovery_page_home');
        }

        $table = 'thiscovery_page';
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        foreach ([
            'top_menu_visibility',
            'top_menu_sort_order',
            'top_menu_label',
            'show_in_top_menu',
            'bound_space_id',
            'is_collection',
            'parent_id',
        ] as $col) {
            if ($schema->getColumn($col) !== null) {
                if (in_array($col, ['parent_id', 'is_collection', 'bound_space_id'], true)) {
                    try {
                        $this->dropIndex('idx_thiscovery_page_' . ($col === 'bound_space_id' ? 'bound_space' : $col), $table);
                    } catch (\Throwable $e) {
                        // index name may differ
                    }
                }
                $this->dropColumn($table, $col);
            }
        }
    }
}
