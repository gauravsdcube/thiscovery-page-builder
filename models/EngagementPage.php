<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\models;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\engagementPages\helpers\FileHelper;
use humhub\modules\engagementPages\helpers\Url;
use humhub\modules\engagementPages\permissions\CreateGlobalPage;
use humhub\modules\engagementPages\permissions\CreatePage;
use humhub\modules\engagementPages\permissions\ManageGlobalPage;
use humhub\modules\engagementPages\permissions\ManagePages;
use humhub\modules\engagementPages\services\BlockRegistry;
use humhub\modules\search\interfaces\Searchable;
use humhub\modules\space\models\Space;
use humhub\modules\user\components\PermissionManager;
use Yii;
use yii\helpers\Json;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property int $status
 * @property string|null $sections_json
 * @property string $layout
 * @property string $page_width
 * @property string $audience
 * @property bool $listed
 * @property bool $featured
 * @property bool $is_directory
 * @property bool $is_template
 * @property string|null $category
 * @property string|null $closes_at
 * @property string|null $created_at
 * @property int|null $created_by
 * @property string|null $updated_at
 * @property int|null $updated_by
 */
class EngagementPage extends ContentActiveRecord implements Searchable
{
    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_ARCHIVED = 2;

    /** Reserved internal slug for the /pages homepage (URL is always /pages). */
    public const DIRECTORY_SLUG = 'directory';

    public const WIDTH_NARROW = 'narrow';
    public const WIDTH_STANDARD = 'standard';
    public const WIDTH_COMFORTABLE = 'comfortable';
    public const WIDTH_WIDE = 'wide';
    public const WIDTH_FULL = 'full';

    public const AUDIENCE_PUBLIC = 'public';
    public const AUDIENCE_MEMBERS = 'members';

    public $moduleId = 'engagement-pages';
    public $wallEntryClass = null;
    public $silentContentCreation = true;
    public $autoAddToWall = false;
    protected $streamChannel = null;
    protected $createPermission = CreatePage::class;
    protected $managePermission = ManagePages::class;

    /** @var array|null decoded sections for form binding */
    public $sections = null;

    public static function tableName()
    {
        return 'engagement_page';
    }

    public function init()
    {
        parent::init();
        if (!$this->isNewRecord) {
            return;
        }
        // Guard with hasAttribute so a stale DB schema cache after migrations
        // does not throw UnknownPropertyException during find()/new.
        if ($this->getAttribute('status') === null) {
            $this->status = self::STATUS_DRAFT;
        }
        if ($this->hasAttribute('layout') && ($this->getAttribute('layout') === null || $this->getAttribute('layout') === '')) {
            $this->layout = BlockRegistry::LAYOUT_LEFT;
        }
        if ($this->hasAttribute('page_width')
            && ($this->getAttribute('page_width') === null || $this->getAttribute('page_width') === '')) {
            $this->page_width = self::WIDTH_WIDE;
        }
        if ($this->hasAttribute('audience')
            && ($this->getAttribute('audience') === null || $this->getAttribute('audience') === '')) {
            $this->audience = self::AUDIENCE_PUBLIC;
        }
        if ($this->hasAttribute('listed') && $this->getAttribute('listed') === null) {
            $this->listed = true;
        }
    }

    public function rules()
    {
        $rules = [
            [['title', 'slug'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 120],
            [['slug'], 'match', 'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'message' => Yii::t('EngagementPagesModule.base', 'Slug may only contain lowercase letters, numbers, and hyphens.')],
            [['slug'], 'unique'],
            [['summary', 'sections_json'], 'string'],
            [['category'], 'string', 'max' => 64],
            [['closes_at'], 'safe'],
            [['listed', 'featured', 'is_directory', 'is_template'], 'boolean'],
            [['layout'], 'in', 'range' => array_keys(BlockRegistry::layoutOptions())],
            [['page_width'], 'in', 'range' => array_keys(self::pageWidthOptions())],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED]],
            [['sections'], 'safe'],
        ];

        if ($this->hasAttribute('audience')) {
            $rules[] = [['audience'], 'default', 'value' => self::AUDIENCE_PUBLIC];
            $rules[] = [['audience'], 'in', 'range' => array_keys(self::audienceOptions())];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'title' => Yii::t('EngagementPagesModule.base', 'Title'),
            'slug' => Yii::t('EngagementPagesModule.base', 'URL slug'),
            'summary' => Yii::t('EngagementPagesModule.base', 'Summary'),
            'status' => Yii::t('EngagementPagesModule.base', 'Status'),
            'layout' => Yii::t('EngagementPagesModule.base', 'Page layout'),
            'page_width' => Yii::t('EngagementPagesModule.base', 'Page width'),
            'audience' => Yii::t('EngagementPagesModule.base', 'Who can view'),
            'listed' => Yii::t('EngagementPagesModule.base', 'Show in directory'),
            'featured' => Yii::t('EngagementPagesModule.base', 'Featured'),
            'is_template' => Yii::t('EngagementPagesModule.base', 'Page template'),
            'category' => Yii::t('EngagementPagesModule.base', 'Category'),
            'closes_at' => Yii::t('EngagementPagesModule.base', 'Closes at'),
        ];
    }

    public function beforeValidate()
    {
        if (is_string($this->slug)) {
            $this->slug = strtolower(trim($this->slug));
        }
        if (is_array($this->sections)) {
            $this->sections = BlockRegistry::normalizeSections($this->sections);
            $this->sections_json = Json::encode($this->sections);
        }
        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        if (is_array($this->sections)) {
            $this->sections = BlockRegistry::normalizeSections($this->sections);
            $this->sections_json = Json::encode($this->sections);
        } elseif ($this->sections_json === null) {
            $this->sections_json = '[]';
        }

        if ($this->content) {
            // Templates stay private. Published + public audience → guest-readable content.
            // Members-only pages use PRIVATE content visibility (logged-in users can still view globals).
            if ($this->isTemplate()) {
                $this->content->visibility = Content::VISIBILITY_PRIVATE;
            } elseif ($this->status === self::STATUS_PUBLISHED
                && $this->getAudienceKey() === self::AUDIENCE_PUBLIC) {
                $this->content->visibility = Content::VISIBILITY_PUBLIC;
            } else {
                $this->content->visibility = Content::VISIBILITY_PRIVATE;
            }
            $this->content->hidden = true;
        }

        // Directory homepage is never listed as a card on itself.
        if (!empty($this->is_directory)) {
            $this->listed = false;
            $this->featured = false;
            if ($this->hasAttribute('is_template')) {
                $this->is_template = false;
            }
            if ($this->slug === '' || $this->slug === null) {
                $this->slug = self::DIRECTORY_SLUG;
            }
        }

        // Templates never appear in the public directory and are not published.
        if ($this->isTemplate()) {
            $this->listed = false;
            $this->featured = false;
            $this->is_directory = false;
            $this->status = self::STATUS_DRAFT;
        }

        return parent::beforeSave($insert);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->sections = $this->getSections();
    }

    public function getSections(): array
    {
        if (is_array($this->sections)) {
            return $this->sections;
        }
        if (!$this->sections_json) {
            return [];
        }
        try {
            $decoded = Json::decode($this->sections_json);
            return is_array($decoded) ? BlockRegistry::normalizeSections($decoded) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getBlocks(): array
    {
        $blocks = [];
        foreach ($this->getSections() as $section) {
            $block = $this->hydrateBlock($section);
            if ($block !== null) {
                $blocks[] = $block;
            }
        }
        return $blocks;
    }

    public function hydrateBlock(array $section)
    {
        $block = BlockRegistry::create((string) ($section['type'] ?? ''), (array) ($section['settings'] ?? []));
        if ($block === null) {
            return null;
        }
        if ($block instanceof \humhub\modules\engagementPages\blocks\ContainerBlock) {
            $block->setChildren((array) ($section['children'] ?? []));
        }
        return $block;
    }

    public function getLayoutKey(): string
    {
        $layout = (string) ($this->layout ?: BlockRegistry::LAYOUT_MAIN);
        if (!isset(BlockRegistry::layoutOptions()[$layout])) {
            return BlockRegistry::LAYOUT_MAIN;
        }
        return $layout;
    }

    public function getPageWidthKey(): string
    {
        $width = (string) ($this->page_width ?: self::WIDTH_WIDE);
        if (!isset(self::pageWidthOptions()[$width])) {
            return self::WIDTH_WIDE;
        }
        return $width;
    }

    /**
     * CSS max-width value for the public shell (null = no max / full).
     */
    public function getPageWidthCssMax(): ?string
    {
        return match ($this->getPageWidthKey()) {
            self::WIDTH_NARROW => '720px',
            self::WIDTH_STANDARD => '960px',
            self::WIDTH_COMFORTABLE => '1100px',
            // Fixed 1440 — must stay below typical theme max (often 1600–2000)
            // so "Wide" and "Full" remain visually distinct.
            self::WIDTH_WIDE => '1440px',
            default => null,
        };
    }

    public static function pageWidthOptions(): array
    {
        return [
            self::WIDTH_NARROW => Yii::t('EngagementPagesModule.base', 'Narrow (720px)'),
            self::WIDTH_STANDARD => Yii::t('EngagementPagesModule.base', 'Standard (960px)'),
            self::WIDTH_COMFORTABLE => Yii::t('EngagementPagesModule.base', 'Comfortable (1100px)'),
            self::WIDTH_WIDE => Yii::t('EngagementPagesModule.base', 'Wide (1440px)'),
            self::WIDTH_FULL => Yii::t('EngagementPagesModule.base', 'Full browser width'),
        ];
    }

    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_PUBLIC => Yii::t('EngagementPagesModule.base', 'Public (guests and members)'),
            self::AUDIENCE_MEMBERS => Yii::t('EngagementPagesModule.base', 'Community members only'),
        ];
    }

    public function getAudienceKey(): string
    {
        if (!$this->hasAttribute('audience')) {
            return self::AUDIENCE_PUBLIC;
        }
        $audience = (string) ($this->audience ?: self::AUDIENCE_PUBLIC);
        if (!isset(self::audienceOptions()[$audience])) {
            return self::AUDIENCE_PUBLIC;
        }
        return $audience;
    }

    public function isPublicAudience(): bool
    {
        return $this->getAudienceKey() === self::AUDIENCE_PUBLIC;
    }

    /**
     * Whether the current (or given) viewer may open this page on the public URL.
     */
    public function canAccessPublic($user = null): bool
    {
        if ($this->canManage($user)) {
            return true;
        }
        if (!$this->isPublished() || $this->isTemplate()) {
            return false;
        }
        if ($this->getAudienceKey() === self::AUDIENCE_PUBLIC) {
            return true;
        }
        // Members-only
        if ($user === null) {
            return !Yii::$app->user->isGuest;
        }
        return true;
    }

    /**
     * Best image for collection cards: ticked image block, then hero, then first image.
     */
    public function getCardImageUrl(): ?string
    {
        $chosen = null;
        $hero = null;
        $firstImage = null;

        $resolve = static function (array $settings): ?string {
            $guid = (string) ($settings['image_guid'] ?? '');
            if ($guid !== '') {
                $url = FileHelper::url($guid);
                if ($url) {
                    return $url;
                }
            }
            $legacy = (string) ($settings['image_url'] ?? '');
            return $legacy !== '' ? $legacy : null;
        };

        $walk = function (array $items) use (&$walk, &$chosen, &$hero, &$firstImage, $resolve) {
            foreach ($items as $section) {
                $type = (string) ($section['type'] ?? '');
                $settings = (array) ($section['settings'] ?? []);
                $url = $resolve($settings);

                if ($type === 'image' && $url && !empty($settings['use_as_card_image']) && $chosen === null) {
                    $chosen = $url;
                }
                if ($type === 'hero' && $url && $hero === null) {
                    $hero = $url;
                }
                if ($type === 'image' && $url && $firstImage === null) {
                    $firstImage = $url;
                }
                if (!empty($section['children']) && is_array($section['children'])) {
                    $walk($section['children']);
                }
            }
        };
        $walk($this->getSections());

        return $chosen ?: $hero ?: $firstImage;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Guard against silent content save failures during insert.
        if ($insert && $this->content->isNewRecord) {
            try {
                $this->ensureContentRecord();
            } catch (\Throwable $e) {
                Yii::error('Engagement Pages content repair failed: ' . $e->getMessage(), 'engagement-pages');
            }
        }

        $this->postProcessRichTextInSections($this->getSections());

        if (!empty($this->summary)) {
            try {
                \humhub\modules\content\widgets\richtext\RichText::postProcess((string) $this->summary, $this);
            } catch (\Throwable $e) {
                Yii::warning('Engagement Pages summary postProcess failed: ' . $e->getMessage(), 'engagement-pages');
            }
        }

        try {
            $guids = \humhub\modules\engagementPages\helpers\FileHelper::collectGuidsFromSections($this->getSections());
            // Attach (and reclaim unattached) so guests can download via File::canView → content ACL.
            if ($guids !== []) {
                $this->fileManager->attach($guids, true);
            }
        } catch (\Throwable $e) {
            Yii::warning('Engagement Pages file attach failed: ' . $e->getMessage(), 'engagement-pages');
        }
    }

    public function isGlobal(): bool
    {
        try {
            if ($this->content && !$this->content->isNewRecord) {
                return empty($this->content->contentcontainer_id);
            }
            return $this->content->getContainer() === null;
        } catch (\Throwable $e) {
            return empty($this->content->contentcontainer_id ?? null);
        }
    }

    public function isPublished(): bool
    {
        return (int) $this->status === self::STATUS_PUBLISHED;
    }

    public function getIcon()
    {
        return 'fa-bullhorn';
    }

    public function getContentName()
    {
        return Yii::t('EngagementPagesModule.base', 'Page');
    }

    public function getContentDescription()
    {
        return $this->title;
    }

    public function getUrl()
    {
        return Url::toPublic($this);
    }

    public function getSearchAttributes()
    {
        return [
            'title' => $this->title,
            'summary' => (string) $this->summary,
            'slug' => $this->slug,
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => Yii::t('EngagementPagesModule.base', 'Draft'),
            self::STATUS_PUBLISHED => Yii::t('EngagementPagesModule.base', 'Published'),
            self::STATUS_ARCHIVED => Yii::t('EngagementPagesModule.base', 'Archived'),
        ];
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::find()->where(['slug' => $slug])->one();
    }

    /**
     * Published pages that should appear in the public directory listing block.
     * @return self[]
     */
    public static function findDirectoryPages(): array
    {
        $query = static::find()
            ->where([
                'status' => self::STATUS_PUBLISHED,
                'listed' => true,
            ])
            ->orderBy([
                'featured' => SORT_DESC,
                'updated_at' => SORT_DESC,
                'id' => SORT_DESC,
            ]);

        if ((new static())->hasAttribute('is_directory')) {
            $query->andWhere(['is_directory' => false]);
        }
        if ((new static())->hasAttribute('is_template')) {
            $query->andWhere(['is_template' => false]);
        }
        // Guests only see public-audience pages in collections/directory.
        if (Yii::$app->user->isGuest && (new static())->hasAttribute('audience')) {
            $query->andWhere(['audience' => self::AUDIENCE_PUBLIC]);
        }

        return $query->all();
    }

    public static function findDirectoryHome(): ?self
    {
        if (!(new static())->hasAttribute('is_directory')) {
            return null;
        }
        return static::find()->where(['is_directory' => true])->one();
    }

    /**
     * Ensures a single editable /pages homepage exists (global, published).
     */
    public static function ensureDirectoryPage(): self
    {
        $existing = self::findDirectoryHome();
        if ($existing !== null) {
            return $existing;
        }

        $page = new self();
        $page->title = Yii::t('EngagementPagesModule.base', 'Engagements');
        $page->slug = self::DIRECTORY_SLUG;
        $page->status = self::STATUS_PUBLISHED;
        $page->layout = BlockRegistry::LAYOUT_MAIN;
        $page->page_width = self::WIDTH_WIDE;
        $page->listed = false;
        $page->featured = false;
        $page->is_directory = true;
        $page->sections = BlockRegistry::normalizeSections([
            [
                'type' => 'hero',
                'region' => BlockRegistry::REGION_FULL,
                'settings' => [
                    'headline' => Yii::t('EngagementPagesModule.base', 'Shape local health services'),
                    'subheadline' => Yii::t(
                        'EngagementPagesModule.base',
                        'Browse open consultations and surveys. Tell us what matters to you.'
                    ),
                ],
            ],
            [
                'type' => 'collection',
                'region' => BlockRegistry::REGION_MAIN,
                'settings' => [
                    'title' => Yii::t('EngagementPagesModule.base', 'Open for feedback'),
                    'source' => 'pages',
                ],
            ],
        ]);
        $page->content->visibility = Content::VISIBILITY_PUBLIC;
        $page->content->hidden = true;

        $createdBy = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        if (!$createdBy) {
            $admin = \humhub\modules\user\models\User::find()
                ->where(['status' => \humhub\modules\user\models\User::STATUS_ENABLED])
                ->orderBy(['id' => SORT_ASC])
                ->one();
            $createdBy = $admin?->id;
        }
        if ($createdBy) {
            $page->content->created_by = (int) $createdBy;
        }

        if (!$page->save()) {
            throw new \RuntimeException(
                'Could not create directory page: ' . Json::encode($page->getErrors())
            );
        }

        // ContentActiveRecord may leave an orphan page if content->save() fails silently.
        if ($page->content->isNewRecord) {
            $page->ensureContentRecord();
        }

        return $page;
    }

    public function isDirectoryHome(): bool
    {
        return $this->hasAttribute('is_directory') && !empty($this->is_directory);
    }

    public function isTemplate(): bool
    {
        return $this->hasAttribute('is_template') && !empty($this->is_template);
    }

    /**
     * @return self[]
     */
    public static function findTemplates(?int $contentContainerId = null): array
    {
        if (!(new static())->hasAttribute('is_template')) {
            return [];
        }
        $query = static::find()
            ->joinWith('content')
            ->andWhere(['engagement_page.is_template' => true])
            ->orderBy(['engagement_page.title' => SORT_ASC, 'engagement_page.id' => SORT_DESC]);

        if ($contentContainerId === null) {
            $query->andWhere(['content.contentcontainer_id' => null]);
        } else {
            $query->andWhere(['content.contentcontainer_id' => $contentContainerId]);
        }

        return $query->all();
    }

    /**
     * Clone layout + sections into a reusable template page.
     */
    public function saveAsTemplate(?string $title = null): self
    {
        if ($this->isDirectoryHome()) {
            throw new \InvalidArgumentException(
                Yii::t('EngagementPagesModule.base', 'The /pages homepage cannot be saved as a template.')
            );
        }

        $container = $this->content->container ?? null;
        $tpl = $container ? new self($container) : new self();
        $baseTitle = trim((string) ($title !== null && $title !== '' ? $title : $this->title));
        if ($baseTitle === '') {
            $baseTitle = Yii::t('EngagementPagesModule.base', 'Untitled template');
        }
        $tpl->title = $baseTitle;
        $tpl->slug = self::uniqueTemplateSlug($baseTitle);
        $tpl->summary = $this->summary;
        $tpl->status = self::STATUS_DRAFT;
        $tpl->layout = $this->getLayoutKey();
        $tpl->page_width = $this->getPageWidthKey();
        $tpl->listed = false;
        $tpl->featured = false;
        $tpl->is_directory = false;
        $tpl->is_template = true;
        $tpl->category = $this->category;
        $tpl->closes_at = null;
        $tpl->sections = $this->getSections();
        if ($tpl->content) {
            $tpl->content->visibility = Content::VISIBILITY_PRIVATE;
        }
        if (!$tpl->save()) {
            throw new \RuntimeException(
                Yii::t('EngagementPagesModule.base', 'Could not save template: {errors}', [
                    'errors' => Json::encode($tpl->getErrors()),
                ])
            );
        }
        return $tpl;
    }

    /**
     * Copy template structure onto a new (unsaved) page instance.
     */
    public function applyTemplateTo(self $page): void
    {
        $page->layout = $this->getLayoutKey();
        $page->page_width = $this->getPageWidthKey();
        if ($this->hasAttribute('audience') && $page->hasAttribute('audience')) {
            $page->audience = $this->getAudienceKey();
        }
        $page->sections = $this->getSections();
        if ($page->title === '' || $page->title === null) {
            $page->title = $this->title;
        }
        // New pages need a unique public slug; templates use tpl-* and must not be reused.
        if ($page->slug === '' || $page->slug === null) {
            $page->slug = self::uniquePageSlug((string) ($page->title ?: $this->title ?: 'page'));
        }
    }

    /**
     * Build a unique public page slug from a title (not the tpl- prefix used for templates).
     */
    public static function uniquePageSlug(string $title): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? '', '-'));
        if ($base === '' || str_starts_with($base, 'tpl-')) {
            $base = 'page';
        }
        $base = substr($base, 0, 100);
        if ($base === self::DIRECTORY_SLUG) {
            $base = 'page';
        }
        $slug = $base;
        $i = 1;
        while (static::find()->where(['slug' => $slug])->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    public static function uniqueTemplateSlug(string $title): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? '', '-'));
        if ($base === '') {
            $base = 'template';
        }
        $base = 'tpl-' . substr($base, 0, 80);
        $slug = $base;
        $i = 1;
        while (static::find()->where(['slug' => $slug])->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * Create a missing Content row for this page (can happen if content->save() failed after insert).
     */
    public function ensureContentRecord(): void
    {
        if (!$this->content->isNewRecord) {
            return;
        }

        $createdBy = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        if (!$createdBy) {
            $createdBy = $this->created_by
                ?: \humhub\modules\user\models\User::find()
                    ->where(['status' => \humhub\modules\user\models\User::STATUS_ENABLED])
                    ->orderBy(['id' => SORT_ASC])
                    ->one()?->id;
        }

        $this->content->object_model = static::getObjectModel();
        $this->content->object_id = $this->getPrimaryKey();
        $this->content->visibility = ((int) $this->status === self::STATUS_PUBLISHED)
            ? Content::VISIBILITY_PUBLIC
            : Content::VISIBILITY_PRIVATE;
        $this->content->hidden = true;
        $this->content->contentcontainer_id = null;
        $this->content->stream_channel = $this->streamChannel;
        if ($createdBy) {
            $this->content->created_by = (int) $createdBy;
        }

        if (!$this->content->save()) {
            throw new \RuntimeException(
                'Could not create content record for engagement page: ' . Json::encode($this->content->getErrors())
            );
        }
    }

    public function getDirectoryBlurb(): string
    {
        $summary = trim(strip_tags((string) $this->summary));
        if ($summary !== '') {
            return mb_strimwidth($summary, 0, 180, '…');
        }
        return '';
    }

    public function canCreate($user = null): bool
    {
        $user = $user ?: Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }

        if ($this->isGlobal()) {
            $pm = new PermissionManager(['subject' => $user]);
            return $pm->can(CreateGlobalPage::class) || Yii::$app->user->isAdmin();
        }

        $container = $this->content->container ?? null;
        if ($container === null) {
            return false;
        }
        return $container->getPermissionManager($user)->can(CreatePage::class);
    }

    public function canManage($user = null): bool
    {
        $user = $user ?: Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }
        if ((int) $this->content->created_by === (int) $user->id) {
            return true;
        }

        if ($this->isGlobal()) {
            $pm = new PermissionManager(['subject' => $user]);
            return $pm->can(ManageGlobalPage::class) || Yii::$app->user->isAdmin();
        }

        $container = $this->content->container ?? null;
        if ($container === null) {
            return false;
        }
        return $container->getPermissionManager($user)->can(ManagePages::class);
    }

    protected function postProcessRichTextInSections(array $sections): void
    {
        foreach ($sections as $section) {
            $settings = $section['settings'] ?? [];
            foreach (['body', 'intro', 'subheadline'] as $richKey) {
                if (!empty($settings[$richKey]) && is_string($settings[$richKey])) {
                    try {
                        \humhub\modules\content\widgets\richtext\RichText::postProcess($settings[$richKey], $this);
                    } catch (\Throwable $e) {
                        Yii::warning('Engagement Pages richtext postProcess failed: ' . $e->getMessage(), 'engagement-pages');
                    }
                }
            }
            foreach ((array) ($section['settings']['items'] ?? []) as $item) {
                if (!empty($item['body']) && is_string($item['body'])) {
                    try {
                        \humhub\modules\content\widgets\richtext\RichText::postProcess($item['body'], $this);
                    } catch (\Throwable $e) {
                        Yii::warning('Engagement Pages accordion postProcess failed: ' . $e->getMessage(), 'engagement-pages');
                    }
                }
            }
            if (!empty($section['children']) && is_array($section['children'])) {
                $this->postProcessRichTextInSections($section['children']);
            }
        }
    }
}
