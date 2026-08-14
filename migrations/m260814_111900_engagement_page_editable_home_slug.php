<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\db\Migration;

class m260814_111900_engagement_page_editable_home_slug extends Migration
{
    public function safeUp()
    {
        // Previous installs stored an internal "directory" slug and always published /pages.
        $conflict = (new \yii\db\Query())
            ->from('engagement_page')
            ->where(['slug' => 'pages'])
            ->andWhere(['is_directory' => false])
            ->one();

        if ($conflict) {
            $this->update(
                'engagement_page',
                ['slug' => 'pages-' . (int) $conflict['id']],
                ['id' => (int) $conflict['id']]
            );
        }

        $this->update(
            'engagement_page',
            ['slug' => 'pages'],
            ['is_directory' => true, 'slug' => 'directory']
        );
    }

    public function safeDown()
    {
        $this->update(
            'engagement_page',
            ['slug' => 'directory'],
            ['is_directory' => true, 'slug' => 'pages']
        );
    }
}
