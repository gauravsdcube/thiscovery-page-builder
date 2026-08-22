<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\models;

use humhub\components\ActiveRecord;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\User;
use Yii;
use yii\db\ActiveQuery;
use yii\web\IdentityInterface;

/**
 * Site homepage assignment for guests, registered users, or a group.
 *
 * @property int $id
 * @property string $target
 * @property int|null $group_id
 * @property int $page_id
 * @property int $priority
 * @property bool $enabled
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property-read EngagementPage|null $page
 * @property-read Group|null $group
 */
class PageHome extends ActiveRecord
{
    public const TARGET_GUEST = 'guest';
    public const TARGET_REGISTERED = 'registered';
    public const TARGET_GROUP = 'group';

    public static function tableName()
    {
        return 'thiscovery_page_home';
    }

    public function rules()
    {
        return [
            [['target', 'page_id'], 'required'],
            [['page_id', 'group_id', 'priority'], 'integer'],
            [['enabled'], 'boolean'],
            [['target'], 'in', 'range' => array_keys(self::targetOptions())],
            [['priority'], 'default', 'value' => 100],
            [['enabled'], 'default', 'value' => true],
            [['group_id'], 'required', 'when' => fn () => $this->target === self::TARGET_GROUP],
            [['page_id'], 'exist', 'targetClass' => EngagementPage::class, 'targetAttribute' => ['page_id' => 'id']],
            [['group_id'], 'exist', 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id'], 'when' => fn () => $this->group_id],
        ];
    }

    public function attributeLabels()
    {
        return [
            'target' => Yii::t('ThiscoveryPageBuilderModule.base', 'Audience'),
            'group_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Group'),
            'page_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page'),
            'priority' => Yii::t('ThiscoveryPageBuilderModule.base', 'Priority'),
            'enabled' => Yii::t('ThiscoveryPageBuilderModule.base', 'Enabled'),
        ];
    }

    public static function targetOptions(): array
    {
        return [
            self::TARGET_GUEST => Yii::t('ThiscoveryPageBuilderModule.base', 'Guests'),
            self::TARGET_REGISTERED => Yii::t('ThiscoveryPageBuilderModule.base', 'Logged-in users (default)'),
            self::TARGET_GROUP => Yii::t('ThiscoveryPageBuilderModule.base', 'Group'),
        ];
    }

    public function getPage(): ActiveQuery
    {
        return $this->hasOne(EngagementPage::class, ['id' => 'page_id']);
    }

    public function getGroup(): ActiveQuery
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    public function beforeSave($insert)
    {
        if ($this->target !== self::TARGET_GROUP) {
            $this->group_id = null;
        }
        $now = date('Y-m-d H:i:s');
        if ($insert && empty($this->created_at)) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        self::flushCache();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        self::flushCache();
    }

    public static function flushCache(): void
    {
        // Bump generation so all guest/user cache keys miss immediately.
        Yii::$app->cache->set(self::cacheVersionKey(), (string) microtime(true), 0);
        Yii::$app->cache->delete(self::cacheId(null));
        if (!Yii::$app->user->isGuest && Yii::$app->user->id) {
            Yii::$app->cache->delete(self::cacheId((int) Yii::$app->user->id));
        }
    }

    protected static function cacheVersionKey(): string
    {
        return 'thiscovery-page-builder-home:ver';
    }

    protected static function cacheVersion(): string
    {
        $ver = Yii::$app->cache->get(self::cacheVersionKey());
        return is_string($ver) && $ver !== '' ? $ver : '0';
    }

    public static function cacheId($userId): string
    {
        return 'thiscovery-page-builder-home:' . self::cacheVersion() . ':'
            . ($userId === null ? 'guest' : ('u' . (int) $userId));
    }

    public static function getUrlForGuest(): ?string
    {
        $row = static::find()
            ->where(['target' => self::TARGET_GUEST, 'enabled' => 1])
            ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
            ->one();
        return self::urlFromRow($row);
    }

    public static function getUrlForUser(User|IdentityInterface|null $user = null): ?string
    {
        $user = $user ?: Yii::$app->user->identity;
        if (!$user instanceof User) {
            return self::getUrlForGuest();
        }

        $cacheId = self::cacheId($user->id);
        $cached = Yii::$app->cache->get($cacheId);
        if (is_string($cached) || $cached === false) {
            if ($cached === false) {
                // miss handled below
            } elseif ($cached === '') {
                return null;
            } else {
                return $cached;
            }
        }

        $groupIds = $user->getGroups()->select('id')->column();
        $url = null;
        if ($groupIds !== []) {
            $groupHome = static::find()
                ->where(['target' => self::TARGET_GROUP, 'enabled' => 1])
                ->andWhere(['group_id' => $groupIds])
                ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                ->one();
            $url = self::urlFromRow($groupHome);
        }
        if ($url === null) {
            $registered = static::find()
                ->where(['target' => self::TARGET_REGISTERED, 'enabled' => 1])
                ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                ->one();
            $url = self::urlFromRow($registered);
        }

        Yii::$app->cache->set($cacheId, $url ?? '', 600);
        return $url;
    }

    public static function resolveHomeUrl(): ?string
    {
        if (Yii::$app->user->isGuest) {
            return self::getUrlForGuest();
        }
        return self::getUrlForUser();
    }

    protected static function urlFromRow(?self $row): ?string
    {
        if ($row === null) {
            return null;
        }
        $page = $row->page;
        if ($page === null || !$page->isPublished() || $page->isTemplate()) {
            return null;
        }
        return Url::toPublic($page);
    }

    /**
     * Sync homepage assignments posted from the page editor.
     *
     * @param array $targets list of ['target'=>, 'group_id'=>?, 'priority'=>?, 'enabled'=>?]
     */
    public static function syncForPage(EngagementPage $page, array $targets): void
    {
        $keepIds = [];
        foreach ($targets as $row) {
            $target = (string) ($row['target'] ?? '');
            if (!isset(self::targetOptions()[$target])) {
                continue;
            }
            if (empty($row['enabled'])) {
                continue;
            }
            $groupId = ($target === self::TARGET_GROUP) ? (int) ($row['group_id'] ?? 0) : null;
            if ($target === self::TARGET_GROUP && !$groupId) {
                continue;
            }

            $query = static::find()->where(['page_id' => $page->id, 'target' => $target]);
            if ($target === self::TARGET_GROUP) {
                $query->andWhere(['group_id' => $groupId]);
            } else {
                $query->andWhere(['group_id' => null]);
            }
            $model = $query->one() ?: new static();
            $model->page_id = (int) $page->id;
            $model->target = $target;
            $model->group_id = $groupId;
            $model->priority = (int) ($row['priority'] ?? 100);
            $model->enabled = true;
            if ($model->save()) {
                $keepIds[] = (int) $model->id;
            }
        }

        $deleteQuery = static::find()->where(['page_id' => $page->id]);
        if ($keepIds !== []) {
            $deleteQuery->andWhere(['not in', 'id', $keepIds]);
        }
        foreach ($deleteQuery->all() as $old) {
            $old->delete();
        }
        self::flushCache();
    }
}
