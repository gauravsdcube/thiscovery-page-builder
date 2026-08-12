<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260812_150000_engagement_page_width extends Migration
{
    public function safeUp()
    {
        $this->addColumn('engagement_page', 'page_width', $this->string(16)->notNull()->defaultValue('wide'));
    }

    public function safeDown()
    {
        $this->dropColumn('engagement_page', 'page_width');
    }
}
