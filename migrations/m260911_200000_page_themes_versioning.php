<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260911_200000_page_themes_versioning extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('thiscovery_page_theme', true) === null) {
            $this->createTable('thiscovery_page_theme', [
                'id' => $this->primaryKey(),
                'name' => $this->string(120)->notNull(),
                'style_json' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->null(),
                'custom_css' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->null(),
                'is_default' => $this->tinyInteger()->notNull()->defaultValue(0),
                'created_at' => $this->dateTime()->null(),
                'created_by' => $this->integer()->null(),
                'updated_at' => $this->dateTime()->null(),
                'updated_by' => $this->integer()->null(),
            ]);
            $this->createIndex('idx_thiscovery_page_theme_default', 'thiscovery_page_theme', 'is_default');
        }

        $this->ensureColumn('thiscovery_page', 'theme_id', $this->integer()->null());
        $this->ensureColumn('thiscovery_page', 'style_json', $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->null());
        $this->ensureColumn('thiscovery_page', 'custom_css', $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->null());
        $this->ensureColumn('thiscovery_page', 'current_edition_id', $this->integer()->null());

        $exists = (new \yii\db\Query())->from('thiscovery_page_theme')->count();
        if ((int) $exists === 0) {
            $now = date('Y-m-d H:i:s');
            $this->insert('thiscovery_page_theme', [
                'name' => 'Default',
                'style_json' => json_encode(['page' => []], JSON_UNESCAPED_UNICODE),
                'custom_css' => '',
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $defaultId = (new \yii\db\Query())
            ->from('thiscovery_page_theme')
            ->where(['is_default' => 1])
            ->select('id')
            ->scalar();
        if ($defaultId) {
            $this->update(
                'thiscovery_page',
                ['theme_id' => (int) $defaultId],
                ['theme_id' => null]
            );
        }

        try {
            $mod = \Yii::$app->getModule('thiscovery-versioning');
            if ($mod && method_exists($mod, 'setOwnerEnabled')) {
                $mod->setOwnerEnabled('page', true);
            }
        } catch (\Throwable $e) {
            // Versioning module optional until enabled.
        }
    }

    public function safeDown()
    {
        $this->dropColumnSafe('thiscovery_page', 'current_edition_id');
        $this->dropColumnSafe('thiscovery_page', 'custom_css');
        $this->dropColumnSafe('thiscovery_page', 'style_json');
        $this->dropColumnSafe('thiscovery_page', 'theme_id');
        if ($this->db->schema->getTableSchema('thiscovery_page_theme', true) !== null) {
            $this->dropTable('thiscovery_page_theme');
        }
    }

    private function ensureColumn(string $table, string $column, $type): void
    {
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema && $schema->getColumn($column) === null) {
            $this->addColumn($table, $column, $type);
        }
    }

    private function dropColumnSafe(string $table, string $column): void
    {
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema && $schema->getColumn($column) !== null) {
            $this->dropColumn($table, $column);
        }
    }
}
