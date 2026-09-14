<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\models;

use humhub\components\ActiveRecord;
use Yii;
use yii\db\ActiveQuery;

/**
 * Admin-only grouping for pages and collections. Does not change public URLs.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $contentcontainer_id
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property string|null $created_at
 * @property int|null $created_by
 * @property string|null $updated_at
 * @property int|null $updated_by
 *
 * @property-read PageFolder|null $parent
 * @property-read PageFolder[] $children
 * @property-read EngagementPage[] $pages
 */
class PageFolder extends ActiveRecord
{
    public const MAX_DEPTH = 8;

    public static function tableName()
    {
        return 'thiscovery_page_folder';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['parent_id', 'contentcontainer_id', 'sort_order', 'created_by', 'updated_by'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['parent_id'], 'validateParent'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => Yii::t('ThiscoveryPageBuilderModule.base', 'Folder name'),
            'description' => Yii::t('ThiscoveryPageBuilderModule.base', 'Description'),
            'parent_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Parent folder'),
        ];
    }

    public function validateParent($attribute): void
    {
        $parentId = (int) $this->parent_id;
        if ($parentId < 1) {
            $this->parent_id = null;
            return;
        }
        if (!$this->isNewRecord && $parentId === (int) $this->id) {
            $this->addError($attribute, Yii::t('ThiscoveryPageBuilderModule.base', 'A folder cannot be inside itself.'));
            return;
        }
        $parent = static::findOne($parentId);
        if (!$parent) {
            $this->addError($attribute, Yii::t('ThiscoveryPageBuilderModule.base', 'Parent folder not found.'));
            return;
        }
        if ((int) $parent->contentcontainer_id !== (int) $this->contentcontainer_id) {
            $this->addError($attribute, Yii::t('ThiscoveryPageBuilderModule.base', 'Folders must stay in the same space.'));
            return;
        }
        if (!$this->isNewRecord && $parent->isDescendantOf((int) $this->id)) {
            $this->addError($attribute, Yii::t('ThiscoveryPageBuilderModule.base', 'A folder cannot be moved into one of its subfolders.'));
            return;
        }
        if ($parent->getDepth() + 1 >= self::MAX_DEPTH) {
            $this->addError($attribute, Yii::t('ThiscoveryPageBuilderModule.base', 'Folders can only be nested {n} levels deep.', [
                'n' => self::MAX_DEPTH,
            ]));
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $this->created_at ?: $now;
            $this->created_by = $this->created_by ?: Yii::$app->user->id;
        }
        $this->updated_at = $now;
        $this->updated_by = Yii::$app->user->id;
        if (!(int) $this->parent_id) {
            $this->parent_id = null;
        }
        return true;
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(static::class, ['parent_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);
    }

    public function getPages(): ActiveQuery
    {
        return $this->hasMany(EngagementPage::class, ['folder_id' => 'id']);
    }

    public function getDepth(): int
    {
        $depth = 0;
        $current = $this->parent;
        while ($current && $depth < self::MAX_DEPTH + 2) {
            $depth++;
            $current = $current->parent;
        }
        return $depth;
    }

    public function isDescendantOf(int $folderId): bool
    {
        $current = $this->parent;
        $guard = 0;
        while ($current && $guard < self::MAX_DEPTH + 2) {
            if ((int) $current->id === $folderId) {
                return true;
            }
            $current = $current->parent;
            $guard++;
        }
        return false;
    }

    /**
     * @return static[]
     */
    public function getAncestors(): array
    {
        $chain = [];
        $current = $this->parent;
        $guard = 0;
        while ($current && $guard < self::MAX_DEPTH + 2) {
            array_unshift($chain, $current);
            $current = $current->parent;
            $guard++;
        }
        return $chain;
    }

    public function getPathLabel(): string
    {
        $parts = array_map(static fn(self $folder) => $folder->name, $this->getAncestors());
        $parts[] = $this->name;
        return implode(' / ', $parts);
    }

    public static function findForContainer($container = null): ActiveQuery
    {
        $query = static::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);
        if ($container) {
            return $query->andWhere(['contentcontainer_id' => $container->contentcontainer_id]);
        }
        return $query->andWhere(['contentcontainer_id' => null]);
    }
}
