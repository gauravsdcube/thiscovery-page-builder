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
        if ($this->db->schema->getTableSchema('engagement_page_follow', true) !== null) {
            $this->dropTable('engagement_page_follow');
        }
        if ($this->db->schema->getTableSchema('engagement_page_comment', true) !== null) {
            $this->dropTable('engagement_page_comment');
        }
        $this->safeDropTable('engagement_page');
    }

    public function down()
    {
        echo "uninstall cannot be reverted.\n";
        return false;
    }
}
