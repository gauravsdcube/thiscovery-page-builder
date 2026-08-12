<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260812_170000_engagement_audience_comments extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'engagement_page',
            'audience',
            $this->string(16)->notNull()->defaultValue('public')
        );
        $this->createIndex('idx_ep_audience', 'engagement_page', 'audience');

        $this->createTable('engagement_page_comment', [
            'id' => $this->primaryKey(),
            'page_id' => $this->integer()->notNull(),
            'body' => $this->text()->notNull(),
            'author_name' => $this->string(120)->notNull(),
            'author_email' => $this->string(255)->null(),
            'user_id' => $this->integer()->null(),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'ip_hash' => $this->string(64)->null(),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
            'moderated_by' => $this->integer()->null(),
            'moderated_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_ep_comment_page_status', 'engagement_page_comment', ['page_id', 'status']);
        $this->createIndex('idx_ep_comment_created', 'engagement_page_comment', 'created_at');
        $this->addForeignKey(
            'fk_ep_comment_page',
            'engagement_page_comment',
            'page_id',
            'engagement_page',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_ep_comment_page', 'engagement_page_comment');
        $this->dropTable('engagement_page_comment');
        $this->dropIndex('idx_ep_audience', 'engagement_page');
        $this->dropColumn('engagement_page', 'audience');
    }
}
