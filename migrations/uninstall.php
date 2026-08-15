<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class uninstall extends Migration
{
    public function up()
    {
        foreach (['thiscovery_page_follow', 'engagement_page_follow'] as $table) {
            if ($this->db->schema->getTableSchema($table, true) !== null) {
                $this->dropTable($table);
            }
        }
        foreach (['thiscovery_page_comment', 'engagement_page_comment'] as $table) {
            if ($this->db->schema->getTableSchema($table, true) !== null) {
                $this->dropTable($table);
            }
        }
        foreach (['thiscovery_page', 'engagement_page'] as $table) {
            if ($this->db->schema->getTableSchema($table, true) !== null) {
                $this->dropTable($table);
            }
        }
    }

    public function down()
    {
        echo "uninstall cannot be reverted.\n";
        return false;
    }
}
