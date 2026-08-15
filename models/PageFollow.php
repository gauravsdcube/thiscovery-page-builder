<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Email subscription from the Get updates block.
 *
 * @property int $id
 * @property int $page_id
 * @property string $email
 * @property string|null $created_at
 * @property string|null $token
 *
 * @property-read EngagementPage|null $page
 */
class PageFollow extends ActiveRecord
{
    public static function tableName()
    {
        return 'thiscovery_page_follow';
    }

    public function rules()
    {
        return [
            [['page_id', 'email'], 'required'],
            [['page_id'], 'integer'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['token'], 'string', 'max' => 64],
            [['created_at'], 'safe'],
            [['page_id', 'email'], 'unique', 'targetAttribute' => ['page_id', 'email']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'email' => Yii::t('ThiscoveryPageBuilderModule.base', 'Email'),
            'created_at' => Yii::t('ThiscoveryPageBuilderModule.base', 'Subscribed'),
            'page_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page'),
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
            if (!$this->token) {
                $this->token = Yii::$app->security->generateRandomString(32);
            }
            $this->email = strtolower(trim((string) $this->email));
        }
        return parent::beforeSave($insert);
    }

    public function getPage()
    {
        return $this->hasOne(EngagementPage::class, ['id' => 'page_id']);
    }

    /**
     * Subscriptions for network-level (global) pages only.
     */
    public static function findGlobalQuery(?int $pageId = null): ActiveQuery
    {
        $query = static::find()
            ->alias('f')
            ->innerJoinWith([
                'page p' => static function (ActiveQuery $q) {
                    $q->innerJoinWith('content');
                },
            ])
            ->andWhere(['content.contentcontainer_id' => null])
            ->andWhere(['p.is_template' => false])
            ->orderBy(['f.created_at' => SORT_DESC, 'f.id' => SORT_DESC]);

        if ($pageId) {
            $query->andWhere(['f.page_id' => $pageId]);
        }

        return $query;
    }

    public static function countGlobal(?int $pageId = null): int
    {
        return (int) self::findGlobalQuery($pageId)->count();
    }

    /**
     * Global pages that have at least one subscriber, for the admin filter.
     *
     * @return array<int, string> page id => title
     */
    public static function globalPageFilterOptions(): array
    {
        $pages = EngagementPage::find()
            ->alias('p')
            ->joinWith('content')
            ->innerJoin(self::tableName() . ' f', 'f.page_id = p.id')
            ->andWhere(['content.contentcontainer_id' => null])
            ->andWhere(['p.is_template' => false])
            ->orderBy(['p.title' => SORT_ASC])
            ->all();

        $options = [];
        foreach ($pages as $page) {
            $options[(int) $page->id] = (string) $page->title;
        }
        return $options;
    }
}
