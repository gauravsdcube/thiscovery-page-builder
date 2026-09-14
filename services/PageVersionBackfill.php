<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryVersioning\models\VersionEdition;
use humhub\modules\thiscoveryVersioning\models\VersionRevision;
use Yii;

/**
 * Idempotent backfill of edition #1 for published pages so public URLs keep current content.
 */
class PageVersionBackfill
{
    public function run(): int
    {
        if (!class_exists(VersionRevision::class) || !class_exists(VersionEdition::class)) {
            return 0;
        }
        if (!Yii::$app->db->getTableSchema('{{%tc_version_revision}}', true)
            || !Yii::$app->db->getTableSchema('{{%tc_version_edition}}', true)) {
            return 0;
        }

        $snapshots = new PageSnapshotService();
        $count = 0;
        $query = EngagementPage::find()->andWhere(['is_template' => 0]);
        foreach ($query->each(50) as $page) {
            /** @var EngagementPage $page */
            if (!$page->hasAttribute('current_edition_id')) {
                continue;
            }
            if ($page->current_edition_id) {
                continue;
            }
            if ((int) $page->status !== EngagementPage::STATUS_PUBLISHED) {
                continue;
            }

            $existing = VersionRevision::find()
                ->where(['owner_type' => PageVersionAdapter::OWNER_TYPE, 'owner_id' => $page->id])
                ->count();
            if ($existing) {
                $edition = VersionEdition::find()
                    ->where([
                        'owner_type' => PageVersionAdapter::OWNER_TYPE,
                        'owner_id' => $page->id,
                        'is_current' => 1,
                    ])
                    ->one();
                if ($edition) {
                    $page->updateAttributes(['current_edition_id' => $edition->id]);
                }
                continue;
            }

            $rev = new VersionRevision();
            $rev->owner_type = PageVersionAdapter::OWNER_TYPE;
            $rev->owner_id = (int) $page->id;
            $rev->revision_number = 1;
            $rev->label = Yii::t('ThiscoveryPageBuilderModule.base', 'Migrated from existing page');
            $rev->availability_status = (string) (int) $page->status;
            $rev->setSnapshot($snapshots->export($page));
            $rev->setChangeSummary(['Initial migrated snapshot']);
            $rev->created_at = date('Y-m-d H:i:s');
            $rev->created_by = null;
            $rev->save(false);

            $edition = new VersionEdition();
            $edition->owner_type = PageVersionAdapter::OWNER_TYPE;
            $edition->owner_id = (int) $page->id;
            $edition->edition_number = 1;
            $edition->revision_id = (int) $rev->id;
            $edition->is_current = 1;
            $edition->published_at = date('Y-m-d H:i:s');
            $edition->published_by = null;
            $edition->save(false);

            $page->updateAttributes(['current_edition_id' => $edition->id]);
            $count++;
        }

        return $count;
    }
}
