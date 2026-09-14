<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\models;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\thiscoveryPageBuilder\components\PageUrlRule;
use humhub\modules\thiscoveryPageBuilder\helpers\FileHelper;
use humhub\modules\thiscoveryPageBuilder\helpers\RichHtml;
use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\permissions\CreateGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\CreatePage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManageGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManagePages;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;
use humhub\modules\thiscoveryPageBuilder\services\PageStyleService;
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
 * @property bool $is_collection
 * @property int|null $parent_id
 * @property int|null $bound_space_id
 * @property bool $show_in_top_menu
 * @property string|null $top_menu_label
 * @property int $top_menu_sort_order
 * @property string $top_menu_visibility
 * @property bool $is_template
 * @property string|null $category
 * @property string|null $closes_at
 * @property string|null $created_at
 * @property int|null $created_by
 * @property string|null $updated_at
 * @property int|null $updated_by
 * @property int|null $theme_id
 * @property string|null $style_json
 * @property string|null $custom_css
 * @property int|null $current_edition_id
 *
 * @property int|null $folder_id
 * @property-read EngagementPage|null $parent
 * @property-read EngagementPage[] $children
 * @property-read PageFolder|null $folder
 */
class EngagementPage extends ContentActiveRecord implements Searchable
{
    public const TOP_MENU_ALL = 'all';
    public const TOP_MENU_GUESTS = 'guests';
    public const TOP_MENU_USERS = 'users';

    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_ARCHIVED = 2;

    /** Default public URL prefix for the directory homepage. Editable in the UI. */
    public const DEFAULT_PUBLIC_PREFIX = 'pages';

    /** @deprecated Use DEFAULT_PUBLIC_PREFIX. Kept so existing code still resolves. */
    public const DIRECTORY_SLUG = self::DEFAULT_PUBLIC_PREFIX;

    public const WIDTH_NARROW = 'narrow';
    public const WIDTH_STANDARD = 'standard';
    public const WIDTH_COMFORTABLE = 'comfortable';
    public const WIDTH_WIDE = 'wide';
    public const WIDTH_EXTRA_WIDE = 'extra_wide';
    public const WIDTH_FULL = 'full';

    public const AUDIENCE_PUBLIC = 'public';
    public const AUDIENCE_MEMBERS = 'members';

    public $moduleId = 'thiscovery-page-builder';
    public $wallEntryClass = null;
    public $silentContentCreation = true;
    public $autoAddToWall = false;
    protected $streamChannel = null;
    protected $createPermission = CreatePage::class;
    protected $managePermission = ManagePages::class;

    /** @var array|null decoded sections for form binding */
    public $sections = null;

    /** @var array decoded style tokens for form binding */
    public $style = [];

    public static function tableName()
    {
        return 'thiscovery_page';
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
        if ($this->isNewRecord && $this->hasAttribute('theme_id') && $this->getAttribute('theme_id') === null) {
            try {
                $default = PageTheme::findDefault();
                if ($default) {
                    $this->theme_id = (int) $default->id;
                }
            } catch (\Throwable $e) {
                // Theme table may not exist yet.
            }
        }
    }

    public function rules()
    {
        $rules = [
            [['title', 'slug'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 120],
            [['slug'], 'match', 'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'message' => Yii::t('ThiscoveryPageBuilderModule.base', 'Slug may only contain lowercase letters, numbers, and hyphens.')],
            [['slug'], 'unique', 'message' => Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'This slug is already used by another page. Choose a different URL slug.'
            )],
            [['slug'], 'validateSlugReserved'],
            [['slug'], 'validateSlugCollisionMessage'],
            [['summary', 'sections_json'], 'string'],
            [['category'], 'string', 'max' => 64],
            [['closes_at'], 'safe'],
            [['listed', 'featured', 'is_directory', 'is_template'], 'boolean'],
            [['layout'], 'in', 'range' => array_keys(BlockRegistry::layoutOptions())],
            [['page_width'], 'in', 'range' => array_keys(self::pageWidthOptions())],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED]],
            [['sections', 'style'], 'safe'],
        ];

        if ($this->hasAttribute('audience')) {
            $rules[] = [['audience'], 'default', 'value' => self::AUDIENCE_PUBLIC];
            $rules[] = [['audience'], 'in', 'range' => array_keys(self::audienceOptions())];
        }
        if ($this->hasAttribute('is_collection')) {
            $rules[] = [['is_collection'], 'boolean'];
        }
        if ($this->hasAttribute('parent_id')) {
            $rules[] = [['parent_id'], 'integer'];
            $rules[] = [['parent_id'], 'validateParent'];
        }
        if ($this->hasAttribute('folder_id')) {
            $rules[] = [['folder_id'], 'integer'];
            $rules[] = [['folder_id'], 'validateFolder'];
        }
        if ($this->hasAttribute('bound_space_id')) {
            $rules[] = [['bound_space_id'], 'integer'];
            $rules[] = [['bound_space_id'], 'exist', 'skipOnEmpty' => true,
                'targetClass' => Space::class, 'targetAttribute' => ['bound_space_id' => 'id']];
        }
        if ($this->hasAttribute('show_in_top_menu')) {
            $rules[] = [['show_in_top_menu'], 'boolean'];
            $rules[] = [['top_menu_label'], 'string', 'max' => 64];
            $rules[] = [['top_menu_sort_order'], 'integer'];
            $rules[] = [['top_menu_visibility'], 'in', 'range' => array_keys(self::topMenuVisibilityOptions())];
        }
        if ($this->hasAttribute('theme_id')) {
            $rules[] = [['theme_id'], 'integer'];
            $rules[] = [['theme_id'], 'exist', 'skipOnEmpty' => true,
                'targetClass' => PageTheme::class, 'targetAttribute' => ['theme_id' => 'id']];
        }
        if ($this->hasAttribute('custom_css')) {
            $rules[] = [['custom_css'], 'string'];
        }
        if ($this->hasAttribute('style_json')) {
            $rules[] = [['style_json'], 'string'];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Title'),
            'slug' => Yii::t('ThiscoveryPageBuilderModule.base', 'URL slug'),
            'summary' => Yii::t('ThiscoveryPageBuilderModule.base', 'Summary'),
            'status' => Yii::t('ThiscoveryPageBuilderModule.base', 'Status'),
            'layout' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page layout'),
            'page_width' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page width'),
            'audience' => Yii::t('ThiscoveryPageBuilderModule.base', 'Who can view'),
            'listed' => Yii::t('ThiscoveryPageBuilderModule.base', 'Show in directory'),
            'featured' => Yii::t('ThiscoveryPageBuilderModule.base', 'Featured'),
            'is_template' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page template'),
            'is_collection' => Yii::t('ThiscoveryPageBuilderModule.base', 'Collection'),
            'parent_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Collection'),
            'folder_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Folder'),
            'bound_space_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Bound Space'),
            'show_in_top_menu' => Yii::t('ThiscoveryPageBuilderModule.base', 'Show in top menu'),
            'top_menu_label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Top menu label'),
            'top_menu_sort_order' => Yii::t('ThiscoveryPageBuilderModule.base', 'Top menu order'),
            'top_menu_visibility' => Yii::t('ThiscoveryPageBuilderModule.base', 'Top menu visibility'),
            'category' => Yii::t('ThiscoveryPageBuilderModule.base', 'Category'),
            'closes_at' => Yii::t('ThiscoveryPageBuilderModule.base', 'Closes at'),
            'theme_id' => Yii::t('ThiscoveryPageBuilderModule.base', 'Theme'),
            'custom_css' => Yii::t('ThiscoveryPageBuilderModule.base', 'Custom CSS'),
        ];
    }

    public function validateSlugReserved($attribute): void
    {
        $slug = (string) $this->$attribute;
        if ($slug === '') {
            return;
        }

        $isRoot = $this->isCollection() || $this->isDirectoryHome() || empty($this->parent_id);
        if ($isRoot && in_array($slug, self::reservedPrefixSlugs(), true)) {
            $this->addError($attribute, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'This URL is reserved by the platform. Choose a different slug.'
            ));
            return;
        }

        if (!$isRoot && in_array($slug, self::reservedChildSlugs(), true)) {
            $this->addError($attribute, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'This slug is reserved. Choose a different URL slug.'
            ));
        }
    }

    /**
     * Enrich unique-slug errors with the conflicting page title when possible.
     */
    public function validateSlugCollisionMessage($attribute): void
    {
        if ($this->hasErrors($attribute)) {
            $other = static::find()
                ->where(['slug' => (string) $this->$attribute])
                ->andFilterWhere(['<>', 'id', $this->id])
                ->one();
            if ($other !== null) {
                $this->clearErrors($attribute);
                $this->addError($attribute, Yii::t(
                    'ThiscoveryPageBuilderModule.base',
                    'This slug is already used by “{title}”. Choose a different URL slug.',
                    ['title' => $other->title]
                ));
            }
            return;
        }
    }

    public function validateParent($attribute): void
    {
        if ($this->isCollection() || $this->isDirectoryHome()) {
            if (!empty($this->parent_id)) {
                $this->addError($attribute, Yii::t(
                    'ThiscoveryPageBuilderModule.base',
                    'Collections cannot be nested under another page.'
                ));
            }
            return;
        }
        if (empty($this->parent_id)) {
            return;
        }
        $parent = static::findOne((int) $this->parent_id);
        if ($parent === null || (!$parent->isCollection() && !$parent->isDirectoryHome())) {
            $this->addError($attribute, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Parent must be an existing collection.'
            ));
            return;
        }
        if ((int) $parent->id === (int) $this->id) {
            $this->addError($attribute, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'A page cannot be its own parent.'
            ));
        }
    }

    public function validateFolder($attribute): void
    {
        if ($this->folder_id === '' || $this->folder_id === null || (int) $this->folder_id === 0) {
            $this->folder_id = null;
            return;
        }
        $folder = PageFolder::findOne((int) $this->folder_id);
        if ($folder === null) {
            $this->addError($attribute, Yii::t('ThiscoveryPageBuilderModule.base', 'Folder not found.'));
            return;
        }
        $containerId = $this->content->contentcontainer_id ?? null;
        if ((int) $folder->contentcontainer_id !== (int) $containerId) {
            $this->addError($attribute, Yii::t('ThiscoveryPageBuilderModule.base', 'Folders must stay in the same space.'));
        }
    }

    /**
     * First URL segment of the legacy primary collection (directory homepage).
     */
    public static function publicPrefix(): string
    {
        return PageUrlRule::getPrefix();
    }

    /**
     * Public path for this page, e.g. /about or /consultations/my-page.
     */
    public function getPublicPath(): string
    {
        if ($this->hasAttribute('parent_id') && !empty($this->parent_id)) {
            $parent = $this->parent;
            $parentSlug = $parent ? $parent->slug : self::publicPrefix();
            return '/' . $parentSlug . '/' . $this->slug;
        }
        return '/' . ($this->slug ?: self::DEFAULT_PUBLIC_PREFIX);
    }

    public function getParentUrlSlug(): ?string
    {
        if (!$this->hasAttribute('parent_id') || empty($this->parent_id)) {
            return null;
        }
        return $this->parent?->slug;
    }

    /**
     * @return string[]
     */
    public static function reservedPrefixSlugs(): array
    {
        return [
            'admin', 'api', 'assets', 'c', 'calendar', 'comment', 'content', 'dashboard',
            'directory', 'file', 'home', 'index', 'installer', 'legal', 'like', 'login',
            'logout', 'mail', 'marketplace', 'mention', 'notification', 'oembed', 'p',
            'page-builder', 'people', 'post', 'register', 'rest', 's', 'search', 'space',
            'spaces', 'static', 'tasks', 'thiscovery-forms', 'topic', 'tour', 'u',
            'uploads', 'user', 'wiki', PageUrlRule::ADMIN_PREFIX,
        ];
    }

    /**
     * @return string[]
     */
    public static function reservedChildSlugs(): array
    {
        return ['follow', 'comment'];
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
        if (is_array($this->style) && $this->hasAttribute('style_json')) {
            $this->style_json = Json::encode((new PageStyleService())->normalize($this->style), JSON_UNESCAPED_UNICODE);
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

        if (is_array($this->style) && $this->hasAttribute('style_json')) {
            $this->style_json = Json::encode((new PageStyleService())->normalize($this->style), JSON_UNESCAPED_UNICODE);
        }

        if ($this->hasAttribute('theme_id') && ($this->theme_id === '' || $this->theme_id === 0)) {
            $this->theme_id = null;
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

        // Directory / collection roots are never listed as cards on themselves.
        if (!empty($this->is_directory) || $this->isCollection()) {
            $this->listed = false;
            $this->featured = false;
            if ($this->hasAttribute('is_template')) {
                $this->is_template = false;
            }
            if ($this->hasAttribute('parent_id')) {
                $this->parent_id = null;
            }
            if ($this->hasAttribute('is_collection') && !empty($this->is_directory)) {
                $this->is_collection = true;
            }
            if (!empty($this->is_directory) && ($this->slug === '' || $this->slug === null || $this->slug === 'directory')) {
                $this->slug = self::DEFAULT_PUBLIC_PREFIX;
            }
        }

        // Templates never appear in the public directory and are not published.
        if ($this->isTemplate()) {
            $this->listed = false;
            $this->featured = false;
            $this->is_directory = false;
            if ($this->hasAttribute('is_collection')) {
                $this->is_collection = false;
            }
            if ($this->hasAttribute('parent_id')) {
                $this->parent_id = null;
            }
            if ($this->hasAttribute('show_in_top_menu')) {
                $this->show_in_top_menu = false;
            }
            $this->status = self::STATUS_DRAFT;
        }

        if ($this->hasAttribute('folder_id')) {
            if ($this->folder_id === '' || $this->folder_id === 0) {
                $this->folder_id = null;
            }
            if ($this->isTemplate() || (!$this->isCollection() && !empty($this->parent_id))) {
                $this->folder_id = null;
            }
        }

        return parent::beforeSave($insert);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->sections = $this->getSections();
        $decoded = [];
        if ($this->hasAttribute('style_json') && $this->style_json) {
            $parsed = json_decode((string) $this->style_json, true);
            $decoded = is_array($parsed) ? $parsed : [];
        }
        $this->style = $decoded;
    }

    public function getStyle(): array
    {
        return is_array($this->style) ? $this->style : [];
    }

    public function setStyle(array $style): void
    {
        $normalized = (new PageStyleService())->normalize($style);
        $this->style = $normalized;
        if ($this->hasAttribute('style_json')) {
            $this->style_json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        }
    }

    public function resolveTheme(): ?PageTheme
    {
        if (!$this->hasAttribute('theme_id') || !$this->theme_id) {
            return null;
        }
        try {
            return PageTheme::findOne((int) $this->theme_id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Compiled theme tokens plus page overrides and custom CSS, sanitized for inline <style>.
     */
    public function getSafeCustomCss(): string
    {
        $theme = $this->resolveTheme();
        $baseStyle = $theme ? $theme->getStyle() : [];
        $pageStyle = $this->getStyle();
        $svc = new PageStyleService();
        $merged = $svc->mergeStyles($baseStyle, $pageStyle);
        $chunks = [$svc->compile($merged)];
        if ($theme && trim((string) $theme->custom_css) !== '') {
            $chunks[] = (string) $theme->custom_css;
        }
        if ($this->hasAttribute('custom_css')) {
            $chunks[] = (string) $this->custom_css;
        }
        $css = trim(implode("\n", array_filter(array_map('trim', $chunks))));
        return PageStyleService::sanitizeCustomCss($css);
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
        if ($block instanceof \humhub\modules\thiscoveryPageBuilder\blocks\ContainerBlock) {
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
            self::WIDTH_WIDE => '1440px',
            self::WIDTH_EXTRA_WIDE => '1600px',
            default => null,
        };
    }

    public static function pageWidthOptions(): array
    {
        return [
            self::WIDTH_NARROW => Yii::t('ThiscoveryPageBuilderModule.base', 'Narrow (720px)'),
            self::WIDTH_STANDARD => Yii::t('ThiscoveryPageBuilderModule.base', 'Standard (960px)'),
            self::WIDTH_COMFORTABLE => Yii::t('ThiscoveryPageBuilderModule.base', 'Comfortable (1100px)'),
            self::WIDTH_WIDE => Yii::t('ThiscoveryPageBuilderModule.base', 'Wide (1440px)'),
            self::WIDTH_EXTRA_WIDE => Yii::t('ThiscoveryPageBuilderModule.base', 'Extra wide (1600px)'),
            self::WIDTH_FULL => Yii::t('ThiscoveryPageBuilderModule.base', 'Full browser width'),
        ];
    }

    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_PUBLIC => Yii::t('ThiscoveryPageBuilderModule.base', 'Public (guests and members)'),
            self::AUDIENCE_MEMBERS => Yii::t('ThiscoveryPageBuilderModule.base', 'Community members only'),
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
                Yii::error('Engagement Pages content repair failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            }
        }

        $this->postProcessRichTextInSections($this->getSections());

        $this->postProcessIfMarkdown((string) $this->summary);

        try {
            $guids = \humhub\modules\thiscoveryPageBuilder\helpers\FileHelper::collectGuidsFromSections($this->getSections());
            // Attach (and reclaim unattached) so guests can download via File::canView → content ACL.
            if ($guids !== []) {
                $this->fileManager->attach($guids, true);
            }
        } catch (\Throwable $e) {
            Yii::warning('Engagement Pages file attach failed: ' . $e->getMessage(), 'thiscovery-page-builder');
        }

        if (($this->isDirectoryHome() || $this->isCollection() || empty($this->parent_id))
            && ($insert || array_key_exists('slug', $changedAttributes) || array_key_exists('parent_id', $changedAttributes))) {
            PageUrlRule::flushCache();
        }

        // Homepage URLs depend on published status and slug.
        if ($insert
            || array_key_exists('status', $changedAttributes)
            || array_key_exists('slug', $changedAttributes)
            || array_key_exists('parent_id', $changedAttributes)
            || array_key_exists('is_template', $changedAttributes)
        ) {
            try {
                PageHome::flushCache();
            } catch (\Throwable $e) {
            }
        }

        \humhub\modules\thiscoveryPageBuilder\services\PageNavigationSync::sync($this, $changedAttributes);
    }

    public function afterDelete()
    {
        parent::afterDelete();
        \humhub\modules\thiscoveryPageBuilder\services\PageNavigationSync::remove($this);
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
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Page');
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
            self::STATUS_DRAFT => Yii::t('ThiscoveryPageBuilderModule.base', 'Draft'),
            self::STATUS_PUBLISHED => Yii::t('ThiscoveryPageBuilderModule.base', 'Published'),
            self::STATUS_ARCHIVED => Yii::t('ThiscoveryPageBuilderModule.base', 'Archived'),
        ];
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::find()->where(['slug' => $slug])->one();
    }

    /**
     * Resolve a public page from root slug and optional child slug.
     */
    public static function findByPublicPath(string $slug, ?string $parentSlug = null): ?self
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        if ($parentSlug !== null && $parentSlug !== '') {
            $parentSlug = strtolower(trim($parentSlug));
            $parent = static::find()
                ->where(['slug' => $parentSlug])
                ->andWhere(['parent_id' => null])
                ->one();
            if ($parent === null) {
                return null;
            }
            return static::find()
                ->where(['slug' => $slug, 'parent_id' => $parent->id])
                ->one();
        }

        // Prefer exact root match; fall back to any slug (legacy flat URLs).
        $root = static::find()
            ->where(['slug' => $slug])
            ->andWhere(['parent_id' => null])
            ->one();
        if ($root !== null) {
            return $root;
        }
        return static::findBySlug($slug);
    }

    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])
            ->andWhere(['is_template' => false])
            ->orderBy(['title' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getFolder()
    {
        return $this->hasOne(PageFolder::class, ['id' => 'folder_id']);
    }

    public function getPageHomes()
    {
        return $this->hasMany(PageHome::class, ['page_id' => 'id']);
    }

    /**
     * @return self[]
     */
    public static function findCollections(?int $contentContainerId = null): array
    {
        $query = static::find()
            ->alias('p')
            ->joinWith('content')
            ->andWhere(['or', ['p.is_collection' => 1], ['p.is_directory' => 1]])
            ->andWhere(['p.is_template' => false])
            ->orderBy(['p.title' => SORT_ASC, 'p.id' => SORT_ASC]);

        if ($contentContainerId === null) {
            $query->andWhere(['content.contentcontainer_id' => null]);
        } else {
            $query->andWhere(['content.contentcontainer_id' => $contentContainerId]);
        }

        return $query->all();
    }

    public static function collectionOptions(?int $contentContainerId = null, ?int $excludeId = null): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Top-level — not in a collection')];
        foreach (self::findCollections($contentContainerId) as $collection) {
            if ($excludeId && (int) $collection->id === (int) $excludeId) {
                continue;
            }
            $options[(string) $collection->id] = $collection->title . ' (/' . $collection->slug . ')';
        }
        return $options;
    }

    public static function spaceOptions(): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'No Space bound')];
        $spaces = Space::find()->orderBy(['name' => SORT_ASC])->limit(500)->all();
        foreach ($spaces as $space) {
            $options[(string) $space->id] = $space->getDisplayName();
        }
        return $options;
    }

    public static function topMenuVisibilityOptions(): array
    {
        return [
            self::TOP_MENU_ALL => Yii::t('ThiscoveryPageBuilderModule.base', 'Everyone'),
            self::TOP_MENU_GUESTS => Yii::t('ThiscoveryPageBuilderModule.base', 'Guests only'),
            self::TOP_MENU_USERS => Yii::t('ThiscoveryPageBuilderModule.base', 'Logged-in users only'),
        ];
    }

    public function getBoundSpace(): ?Space
    {
        if (!$this->hasAttribute('bound_space_id') || empty($this->bound_space_id)) {
            return null;
        }
        return Space::findOne((int) $this->bound_space_id);
    }

    /**
     * Published pages for button/page pickers.
     */
    public static function publishedPageOptions(?int $excludeId = null): array
    {
        $options = ['' => Yii::t('ThiscoveryPageBuilderModule.base', 'Select a page…')];
        $query = static::find()
            ->where(['status' => self::STATUS_PUBLISHED, 'is_template' => false])
            ->orderBy(['title' => SORT_ASC]);
        if ($excludeId) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }
        foreach ($query->all() as $page) {
            $options[(string) $page->id] = $page->title . ' (' . $page->getPublicPath() . ')';
        }
        return $options;
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
        if ((new static())->hasAttribute('is_collection')) {
            $query->andWhere(['is_collection' => false]);
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
     * Ensures a single editable public homepage exists (global, published).
     */
    public static function ensureDirectoryPage(): self
    {
        $existing = self::findDirectoryHome();
        if ($existing !== null) {
            return $existing;
        }

        $page = new self();
        $page->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Engagements');
        $page->slug = self::DEFAULT_PUBLIC_PREFIX;
        $page->status = self::STATUS_PUBLISHED;
        $page->layout = BlockRegistry::LAYOUT_MAIN;
        $page->page_width = self::WIDTH_WIDE;
        $page->listed = false;
        $page->featured = false;
        $page->is_directory = true;
        if ($page->hasAttribute('is_collection')) {
            $page->is_collection = true;
        }
        $page->sections = BlockRegistry::normalizeSections([
            [
                'type' => 'hero',
                'region' => BlockRegistry::REGION_FULL,
                'settings' => [
                    'headline' => Yii::t('ThiscoveryPageBuilderModule.base', 'Shape local health services'),
                    'subheadline' => Yii::t(
                        'ThiscoveryPageBuilderModule.base',
                        'Browse open consultations and surveys. Tell us what matters to you.'
                    ),
                ],
            ],
            [
                'type' => 'collection',
                'region' => BlockRegistry::REGION_MAIN,
                'settings' => [
                    'title' => Yii::t('ThiscoveryPageBuilderModule.base', 'Open for feedback'),
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

    public function isCollection(): bool
    {
        if ($this->isDirectoryHome()) {
            return true;
        }
        return $this->hasAttribute('is_collection') && !empty($this->is_collection);
    }

    public function isTopLevel(): bool
    {
        if (!$this->hasAttribute('parent_id')) {
            return $this->isDirectoryHome();
        }
        return empty($this->parent_id);
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
            ->alias('p')
            ->joinWith('content')
            ->andWhere(['p.is_template' => true])
            ->orderBy(['p.title' => SORT_ASC, 'p.id' => SORT_DESC]);

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
                Yii::t('ThiscoveryPageBuilderModule.base', 'The public homepage cannot be saved as a template.')
            );
        }

        $container = $this->content->container ?? null;
        $tpl = $container ? new self($container) : new self();
        $baseTitle = trim((string) ($title !== null && $title !== '' ? $title : $this->title));
        if ($baseTitle === '') {
            $baseTitle = Yii::t('ThiscoveryPageBuilderModule.base', 'Untitled template');
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
        if ($tpl->hasAttribute('is_collection')) {
            $tpl->is_collection = false;
        }
        if ($tpl->hasAttribute('parent_id')) {
            $tpl->parent_id = null;
        }
        $tpl->is_template = true;
        $tpl->category = $this->category;
        $tpl->closes_at = null;
        $tpl->sections = $this->getSections();
        if ($tpl->hasAttribute('theme_id') && $this->hasAttribute('theme_id')) {
            $tpl->theme_id = $this->theme_id;
        }
        if ($tpl->hasAttribute('style_json') && $this->hasAttribute('style_json')) {
            $tpl->setStyle($this->getStyle());
        }
        if ($tpl->hasAttribute('custom_css') && $this->hasAttribute('custom_css')) {
            $tpl->custom_css = $this->custom_css;
        }
        if ($tpl->content) {
            $tpl->content->visibility = Content::VISIBILITY_PRIVATE;
        }
        if (!$tpl->save()) {
            throw new \RuntimeException(
                Yii::t('ThiscoveryPageBuilderModule.base', 'Could not save template: {errors}', [
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
        if ($page->hasAttribute('theme_id') && $this->hasAttribute('theme_id')) {
            $page->theme_id = $this->theme_id;
        }
        if ($page->hasAttribute('style_json') && $this->hasAttribute('style_json')) {
            $page->setStyle($this->getStyle());
        }
        if ($page->hasAttribute('custom_css') && $this->hasAttribute('custom_css')) {
            $page->custom_css = $this->custom_css;
        }
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
        $reserved = array_merge(
            [self::publicPrefix()],
            self::reservedChildSlugs(),
            self::reservedPrefixSlugs()
        );
        if (in_array($base, $reserved, true)) {
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
                    $this->postProcessIfMarkdown($settings[$richKey]);
                }
            }
            foreach ((array) ($section['settings']['items'] ?? []) as $item) {
                if (!empty($item['body']) && is_string($item['body'])) {
                    $this->postProcessIfMarkdown($item['body']);
                }
            }
            if (!empty($section['children']) && is_array($section['children'])) {
                $this->postProcessRichTextInSections($section['children']);
            }
        }
    }

    /**
     * HumHub RichText::postProcess expects markdown. Skip TinyMCE HTML.
     */
    protected function postProcessIfMarkdown(string $text): void
    {
        $text = trim($text);
        if ($text === '' || RichHtml::looksLikeHtml($text)) {
            return;
        }
        try {
            \humhub\modules\content\widgets\richtext\RichText::postProcess($text, $this);
        } catch (\Throwable $e) {
            Yii::warning('Engagement Pages richtext postProcess failed: ' . $e->getMessage(), 'thiscovery-page-builder');
        }
    }
}
