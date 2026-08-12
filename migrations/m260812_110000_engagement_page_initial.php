<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260812_110000_engagement_page_initial extends Migration
{
    public function safeUp()
    {
        $this->createTable('engagement_page', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(120)->notNull(),
            'summary' => $this->text()->null(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(0),
            'sections_json' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext')->null(),
            'created_at' => $this->dateTime()->null(),
            'created_by' => $this->integer()->null(),
            'updated_at' => $this->dateTime()->null(),
            'updated_by' => $this->integer()->null(),
        ]);

        $this->createIndex('idx_engagement_page_slug', 'engagement_page', 'slug', true);
        $this->createIndex('idx_engagement_page_status', 'engagement_page', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('engagement_page');
    }
}
