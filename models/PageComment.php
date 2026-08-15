<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\models;

use humhub\modules\user\models\User;
use Yii;
use yii\db\ActiveRecord;

/**
 * Moderated public-page comments (guest-capable; not HumHub stream comments).
 *
 * @property int $id
 * @property int $page_id
 * @property string $body
 * @property string $author_name
 * @property string|null $author_email
 * @property int|null $user_id
 * @property int $status
 * @property string|null $ip_hash
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $moderated_by
 * @property string|null $moderated_at
 *
 * @property-read EngagementPage $page
 * @property-read User|null $user
 */
class PageComment extends ActiveRecord
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public static function tableName()
    {
        return 'engagement_page_comment';
    }

    public function rules()
    {
        return [
            [['page_id', 'body', 'author_name', 'status'], 'required'],
            [['page_id', 'user_id', 'status', 'moderated_by'], 'integer'],
            [['body'], 'string', 'min' => 3, 'max' => 4000],
            [['author_name'], 'string', 'max' => 120],
            [['author_email'], 'email'],
            [['author_email'], 'string', 'max' => 255],
            [['ip_hash'], 'string', 'max' => 64],
            [['created_at', 'updated_at', 'moderated_at'], 'safe'],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
            ]],
        ];
    }

    public function attributeLabels()
    {
        return [
            'body' => Yii::t('ThiscoveryPageBuilderModule.base', 'Comment'),
            'author_name' => Yii::t('ThiscoveryPageBuilderModule.base', 'Name'),
            'author_email' => Yii::t('ThiscoveryPageBuilderModule.base', 'Email'),
            'status' => Yii::t('ThiscoveryPageBuilderModule.base', 'Status'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => Yii::t('ThiscoveryPageBuilderModule.base', 'Pending'),
            self::STATUS_APPROVED => Yii::t('ThiscoveryPageBuilderModule.base', 'Approved'),
            self::STATUS_REJECTED => Yii::t('ThiscoveryPageBuilderModule.base', 'Rejected'),
        ];
    }

    public function beforeSave($insert)
    {
        $now = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $now;
            if ($this->author_email) {
                $this->author_email = strtolower(trim((string) $this->author_email));
            }
            $this->author_name = trim((string) $this->author_name);
            $this->body = trim((string) $this->body);
        }
        $this->updated_at = $now;
        return parent::beforeSave($insert);
    }

    public function getPage()
    {
        return $this->hasOne(EngagementPage::class, ['id' => 'page_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function isPending(): bool
    {
        return (int) $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return (int) $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return (int) $this->status === self::STATUS_REJECTED;
    }

    public function approve(?int $moderatorId = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->moderated_by = $moderatorId;
        $this->moderated_at = date('Y-m-d H:i:s');
        return $this->save(false, ['status', 'moderated_by', 'moderated_at', 'updated_at']);
    }

    public function reject(?int $moderatorId = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->moderated_by = $moderatorId;
        $this->moderated_at = date('Y-m-d H:i:s');
        return $this->save(false, ['status', 'moderated_by', 'moderated_at', 'updated_at']);
    }

    /**
     * @return self[]
     */
    public static function findApprovedForPage(int $pageId): array
    {
        return static::find()
            ->where(['page_id' => $pageId, 'status' => self::STATUS_APPROVED])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
    }

    public static function countPending(?int $pageId = null): int
    {
        return self::countByStatus(self::STATUS_PENDING, $pageId);
    }

    public static function countByStatus(?int $status, ?int $pageId = null): int
    {
        $query = static::find();
        if ($status !== null) {
            $query->andWhere(['status' => $status]);
        }
        if ($pageId !== null) {
            $query->andWhere(['page_id' => $pageId]);
        }
        return (int) $query->count();
    }

    public function getModerator()
    {
        return $this->hasOne(User::class, ['id' => 'moderated_by']);
    }
}
