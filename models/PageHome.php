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
 * @property int|null $page_id
 * @property string|null $url
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
            [['target'], 'required'],
            [['page_id', 'group_id', 'priority'], 'integer'],
            [['enabled'], 'boolean'],
            [['url'], 'string', 'max' => 512],
            [['target'], 'in', 'range' => array_keys(self::targetOptions())],
            [['priority'], 'default', 'value' => 100],
            [['enabled'], 'default', 'value' => true],
            [['group_id'], 'required', 'when' => fn () => $this->target === self::TARGET_GROUP],
            [['page_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => EngagementPage::class, 'targetAttribute' => ['page_id' => 'id']],
            [['group_id'], 'exist', 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id'], 'when' => fn () => $this->group_id],
            [['url'], 'validateDestination', 'skipOnEmpty' => false],
        ];
    }

    public function attributeLabels()
    {
        return [
            'target' => Yii::t('ThiscoveryPageBuilderModule.base', 'Audience'),
            'group_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Group'),
            'page_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page'),
            'url' => Yii::t('ThiscoveryPageBuilderModule.base', 'URL'),
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

    public function validateDestination($attribute, $params = []): void
    {
        $destination = self::normalizeDestination((string) $this->url);
        if ($destination !== null) {
            $this->url = $destination;
            $this->page_id = null;
            return;
        }
        $this->url = null;
        if ((int) $this->page_id < 1) {
            $this->addError('page_id', Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Choose a page or enter a URL.'
            ));
        }
    }

    /**
     * Site path (/dashboard) or an http(s) URL. Anything else is rejected.
     */
    public static function normalizeDestination(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 512 || preg_match('/[\r\n\0]/', $url)) {
            return null;
        }
        if (preg_match('#^https?://#i', $url)) {
            return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
        }
        if ($url[0] !== '/' || str_starts_with($url, '//') || str_starts_with($url, '/\\')) {
            return null;
        }
        if (preg_match('#[\s<>"\']#', $url)) {
            return null;
        }
        return $url;
    }

    public function beforeSave($insert)
    {
        if ($this->target !== self::TARGET_GROUP) {
            $this->group_id = null;
        }
        $destination = self::normalizeDestination((string) $this->url);
        if ($destination !== null) {
            $this->url = $destination;
            $this->page_id = null;
        } else {
            $this->url = null;
        }
        if ((int) $this->page_id < 1) {
            $this->page_id = null;
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
        $destination = self::normalizeDestination((string) $row->url);
        if ($destination !== null) {
            return $destination;
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
            $model->url = null;
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

    /**
     * Values for the site homepage screen. One guest row, one logged-in row
     * (the lowest priority wins), and every group row.
     *
     * @return array{guest: array, registered: array, groups: array, extraGuest: int, extraRegistered: int}
     */
    public static function formState(): array
    {
        $guestRows = [];
        $registeredRows = [];
        $groupRows = [];
        foreach (static::find()->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])->all() as $row) {
            if ($row->target === self::TARGET_GUEST) {
                $guestRows[] = $row;
            } elseif ($row->target === self::TARGET_REGISTERED) {
                $registeredRows[] = $row;
            } elseif ($row->target === self::TARGET_GROUP) {
                $groupRows[] = $row;
            }
        }

        return [
            'guest' => self::slotFromRows($guestRows, 100),
            'registered' => self::slotFromRows($registeredRows, 100),
            'groups' => array_map(static fn (self $row) => self::slotFromRow($row), $groupRows),
            'extraGuest' => max(0, count($guestRows) - ($guestRows === [] ? 0 : 1)),
            'extraRegistered' => max(0, count($registeredRows) - ($registeredRows === [] ? 0 : 1)),
        ];
    }

    /**
     * Replace every homepage assignment with the site homepage form.
     * Returns an error message, or null when saved.
     */
    public static function replaceAll(array $posted): ?string
    {
        $models = [];
        foreach ([
            'guest' => self::TARGET_GUEST,
            'registered' => self::TARGET_REGISTERED,
        ] as $key => $target) {
            $row = is_array($posted[$key] ?? null) ? $posted[$key] : [];
            if (empty($row['enabled'])) {
                continue;
            }
            $model = self::modelFromInput($target, $row, null);
            if ($model->hasErrors()) {
                return implode(' ', $model->getFirstErrors());
            }
            $models[] = $model;
        }

        foreach ((array) ($posted['group'] ?? []) as $row) {
            if (!is_array($row) || empty($row['enabled'])) {
                continue;
            }
            $groupId = (int) ($row['group_id'] ?? 0);
            if ($groupId < 1) {
                continue;
            }
            $model = self::modelFromInput(self::TARGET_GROUP, $row, $groupId);
            if ($model->hasErrors()) {
                return implode(' ', $model->getFirstErrors());
            }
            $models[] = $model;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            static::deleteAll();
            foreach ($models as $model) {
                if (!$model->save(false)) {
                    $transaction->rollBack();
                    return Yii::t('ThiscoveryPageBuilderModule.base', 'Could not save the site homepage.');
                }
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e, 'thiscovery-page-builder');
            return Yii::t('ThiscoveryPageBuilderModule.base', 'Could not save the site homepage.');
        }

        self::flushCache();
        return null;
    }

    /**
     * @param self[] $rows
     */
    protected static function slotFromRows(array $rows, int $defaultPriority): array
    {
        if ($rows === []) {
            return self::emptySlot($defaultPriority);
        }
        return self::slotFromRow($rows[0]);
    }

    protected static function slotFromRow(self $row): array
    {
        $url = trim((string) $row->url);
        return [
            'enabled' => (bool) $row->enabled,
            'kind' => $url !== '' ? 'url' : 'page',
            'page_id' => $row->page_id ? (string) $row->page_id : '',
            'url' => $url,
            'priority' => (int) $row->priority,
            'group_id' => $row->group_id ? (string) $row->group_id : '',
        ];
    }

    public static function emptySlot(int $priority = 100): array
    {
        return [
            'enabled' => false,
            'kind' => 'page',
            'page_id' => '',
            'url' => '',
            'priority' => $priority,
            'group_id' => '',
        ];
    }

    public static function slotFromInput(array $row, int $defaultPriority = 100): array
    {
        $kind = ($row['kind'] ?? '') === 'url' ? 'url' : 'page';
        return [
            'enabled' => !empty($row['enabled']),
            'kind' => $kind,
            'page_id' => (string) ($row['page_id'] ?? ''),
            'url' => trim((string) ($row['url'] ?? '')),
            'priority' => (int) ($row['priority'] ?? $defaultPriority),
            'group_id' => (string) ($row['group_id'] ?? ''),
        ];
    }

    protected static function modelFromInput(string $target, array $row, ?int $groupId): self
    {
        $model = new static();
        $model->target = $target;
        $model->group_id = $groupId;
        $model->priority = (int) ($row['priority'] ?? ($target === self::TARGET_GROUP ? 50 : 100));
        $model->enabled = true;
        if (($row['kind'] ?? 'page') === 'url') {
            $model->url = trim((string) ($row['url'] ?? ''));
            $model->page_id = null;
        } else {
            $model->url = null;
            $pageId = (int) ($row['page_id'] ?? 0);
            $model->page_id = $pageId > 0 ? $pageId : null;
        }
        $model->validate();
        return $model;
    }
}
