<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

/**
 * Allow a site homepage to be a path or URL, not only a Page Builder page.
 */
class m261005_173000_page_home_url extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('thiscovery_page_home', true);
        if ($schema === null) {
            return;
        }
        if ($schema->getColumn('url') === null) {
            $this->addColumn('thiscovery_page_home', 'url', $this->string(512)->null()->after('page_id'));
        }
        $pageId = $schema->getColumn('page_id');
        if ($pageId !== null && !$pageId->allowNull) {
            $this->alterColumn('thiscovery_page_home', 'page_id', $this->integer()->null());
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('thiscovery_page_home', true);
        if ($schema === null) {
            return;
        }
        if ($schema->getColumn('url') !== null) {
            $this->delete('thiscovery_page_home', ['page_id' => null]);
            $this->dropColumn('thiscovery_page_home', 'url');
        }
        $pageId = $this->db->schema->getTableSchema('thiscovery_page_home', true)->getColumn('page_id');
        if ($pageId !== null && $pageId->allowNull) {
            $this->alterColumn('thiscovery_page_home', 'page_id', $this->integer()->notNull());
        }
    }
}
