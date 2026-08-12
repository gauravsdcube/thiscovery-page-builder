<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260812_120000_engagement_page_layout extends Migration
{
    public function safeUp()
    {
        $this->addColumn('engagement_page', 'layout', $this->string(16)->notNull()->defaultValue('main'));
    }

    public function safeDown()
    {
        $this->dropColumn('engagement_page', 'layout');
    }
}
