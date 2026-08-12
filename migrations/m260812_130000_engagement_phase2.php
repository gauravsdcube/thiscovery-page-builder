<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260812_130000_engagement_phase2 extends Migration
{
    public function safeUp()
    {
        $this->addColumn('engagement_page', 'listed', $this->boolean()->notNull()->defaultValue(true));
        $this->addColumn('engagement_page', 'featured', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('engagement_page', 'category', $this->string(64)->null());
        $this->addColumn('engagement_page', 'closes_at', $this->dateTime()->null());

        $this->createTable('engagement_page_follow', [
            'id' => $this->primaryKey(),
            'page_id' => $this->integer()->notNull(),
            'email' => $this->string(255)->notNull(),
            'created_at' => $this->dateTime()->null(),
            'token' => $this->string(64)->null(),
        ]);
        $this->createIndex('idx_ep_follow_page_email', 'engagement_page_follow', ['page_id', 'email'], true);
        $this->addForeignKey(
            'fk_ep_follow_page',
            'engagement_page_follow',
            'page_id',
            'engagement_page',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_ep_follow_page', 'engagement_page_follow');
        $this->dropTable('engagement_page_follow');
        $this->dropColumn('engagement_page', 'closes_at');
        $this->dropColumn('engagement_page', 'category');
        $this->dropColumn('engagement_page', 'featured');
        $this->dropColumn('engagement_page', 'listed');
    }
}
