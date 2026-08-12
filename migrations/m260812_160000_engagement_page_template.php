<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260812_160000_engagement_page_template extends Migration
{
    public function safeUp()
    {
        $this->addColumn('engagement_page', 'is_template', $this->boolean()->notNull()->defaultValue(false));
        $this->createIndex('idx_ep_is_template', 'engagement_page', 'is_template');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_ep_is_template', 'engagement_page');
        $this->dropColumn('engagement_page', 'is_template');
    }
}
