<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $page_id
 * @property string $email
 * @property string|null $created_at
 * @property string|null $token
 */
class PageFollow extends ActiveRecord
{
    public static function tableName()
    {
        return 'engagement_page_follow';
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
}
