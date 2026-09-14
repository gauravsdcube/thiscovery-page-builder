<?php

use yii\db\Migration;

/**
 * Backfill edition #1 for published pages so public URLs keep current content.
 */
class m260911_201000_page_versioning_backfill extends Migration
{
    public function safeUp()
    {
        if (!$this->db->getTableSchema('{{%tc_version_revision}}', true)
            || !$this->db->getTableSchema('{{%tc_version_edition}}', true)) {
            echo "Skipping page versioning backfill — thiscovery-versioning tables not present.\n";
            return true;
        }

        try {
            $mod = \Yii::$app->getModule('thiscovery-versioning');
            if ($mod && method_exists($mod, 'setOwnerEnabled')) {
                $mod->setOwnerEnabled('page', true);
            }
        } catch (\Throwable $e) {
        }

        if (class_exists(\humhub\modules\thiscoveryPageBuilder\services\PageVersionBackfill::class)) {
            $n = (new \humhub\modules\thiscoveryPageBuilder\services\PageVersionBackfill())->run();
            echo "Backfilled {$n} published page edition(s).\n";
        }

        return true;
    }

    public function safeDown()
    {
        echo "Backfill cannot be reversed automatically.\n";
        return true;
    }
}
