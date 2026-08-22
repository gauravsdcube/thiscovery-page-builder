<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\helpers;

use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\cfiles\models\File as CFile;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\richtext\AbstractRichText;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\gallery\models\Media;
use humhub\modules\space\models\Space;
use humhub\modules\tasks\models\Task;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;

/**
 * Renders page-builder-native presentations of bound Space content
 * (not the Space module’s own panel/stream chrome).
 */
class SpaceWidgets
{
    public const STREAM_LIMIT = 8;
    public const TASKS_LIMIT = 8;
    public const FILES_LIMIT = 12;
    public const GALLERY_LIMIT = 12;
    public const CALENDAR_LIMIT = 8;

    public static function resolveSpace(EngagementPage $page): ?Space
    {
        [$space] = self::resolveSpaceWithReason($page);
        return $space;
    }

    public static function canViewSpace(Space $space): bool
    {
        try {
            if (!Yii::$app->user->isGuest && Yii::$app->user->isAdmin()) {
                return true;
            }
            $visibility = (int) $space->visibility;
            if ($visibility === Space::VISIBILITY_ALL) {
                return true;
            }
            if (Yii::$app->user->isGuest) {
                return false;
            }
            if ($visibility === Space::VISIBILITY_REGISTERED_ONLY) {
                return true;
            }
            return $space->isMember();
        } catch (\Throwable $e) {
            Yii::warning('Space access check failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            return false;
        }
    }

    /**
     * @return array{0:?Space,1:?string}
     */
    public static function resolveSpaceWithReason(EngagementPage $page): array
    {
        if (!$page->hasAttribute('bound_space_id') || $page->bound_space_id === null || $page->bound_space_id === '') {
            return [null, 'unbound'];
        }
        $space = Space::findOne((int) $page->bound_space_id);
        if ($space === null) {
            return [null, 'missing'];
        }
        if (!self::canViewSpace($space)) {
            return [null, 'denied'];
        }
        return [$space, null];
    }

    public static function moduleEnabled(Space $space, string $moduleId): bool
    {
        try {
            return $space->moduleManager->isEnabled($moduleId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function emptyMessage(string $message): string
    {
        return '<div class="ep-space-panel ep-space-panel--empty">'
            . '<p class="ep-space-panel__empty">' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '</div>';
    }

    public static function unavailableMessage(?string $reason, string $unboundFallback): string
    {
        return match ($reason) {
            'denied' => self::emptyMessage(Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'You do not have permission to view this Space’s content.'
            )),
            'missing' => self::emptyMessage(Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'The bound Space could not be found.'
            )),
            default => self::emptyMessage($unboundFallback),
        };
    }

    protected static function renderPanel(string $view, array $params): string
    {
        $path = '@thiscovery-page-builder/views/space_embed/' . $view;
        if (Yii::$app->controller !== null) {
            return Yii::$app->controller->renderPartial($path, $params);
        }
        return Yii::$app->getView()->render($path, $params);
    }

    public static function renderStream(EngagementPage $page): string
    {
        [$space, $reason] = self::resolveSpaceWithReason($page);
        if ($space === null) {
            return self::unavailableMessage($reason, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Bind a Space in page settings to show its stream.'
            ));
        }

        try {
            $items = self::fetchStreamItems($space);
            return self::renderPanel('stream', [
                'space' => $space,
                'items' => $items,
                'spaceUrl' => $space->createUrl('/space/space/home'),
            ]);
        } catch (\Throwable $e) {
            Yii::warning('Space stream embed failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Stream could not be loaded.'));
        }
    }

    public static function renderTasks(EngagementPage $page): string
    {
        [$space, $reason] = self::resolveSpaceWithReason($page);
        if ($space === null) {
            return self::unavailableMessage($reason, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Bind a Space in page settings to show tasks.'
            ));
        }
        if (!Yii::$app->hasModule('tasks') || !self::moduleEnabled($space, 'tasks')) {
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Tasks is not enabled for this Space.'));
        }

        try {
            $tasks = Task::findPendingTasks($space)
                ->orderBy(['task.end_datetime' => SORT_ASC, 'task.id' => SORT_DESC])
                ->limit(self::TASKS_LIMIT)
                ->all();

            return self::renderPanel('tasks', [
                'space' => $space,
                'tasks' => $tasks,
                'spaceUrl' => $space->createUrl('/tasks/task/index'),
            ]);
        } catch (\Throwable $e) {
            Yii::warning('Tasks embed failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Tasks could not be loaded.'));
        }
    }

    public static function renderFiles(EngagementPage $page): string
    {
        [$space, $reason] = self::resolveSpaceWithReason($page);
        if ($space === null) {
            return self::unavailableMessage($reason, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Bind a Space in page settings to show files.'
            ));
        }
        if (!Yii::$app->hasModule('cfiles') || !self::moduleEnabled($space, 'cfiles')) {
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Files is not enabled for this Space.'));
        }

        try {
            $folder = Folder::getRoot($space) ?: Folder::initRoot($space);
            $files = [];
            $folders = [];
            if ($folder !== null) {
                $folders = $folder->getFolders()->limit(self::FILES_LIMIT)->all();
                $files = $folder->getFiles()->limit(self::FILES_LIMIT)->all();
            }

            return self::renderPanel('files', [
                'space' => $space,
                'folder' => $folder,
                'folders' => $folders,
                'files' => $files,
                'spaceUrl' => $space->createUrl('/cfiles/browse'),
            ]);
        } catch (\Throwable $e) {
            Yii::warning('Files embed failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Files could not be loaded.'));
        }
    }

    public static function renderGallery(EngagementPage $page): string
    {
        [$space, $reason] = self::resolveSpaceWithReason($page);
        if ($space === null) {
            return self::unavailableMessage($reason, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Bind a Space in page settings to show the gallery.'
            ));
        }
        if (!Yii::$app->hasModule('gallery') || !self::moduleEnabled($space, 'gallery')) {
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Gallery is not enabled for this Space.'));
        }

        try {
            $media = Media::find()
                ->contentContainer($space)
                ->readable()
                ->orderBy(['gallery_media.id' => SORT_DESC])
                ->limit(self::GALLERY_LIMIT)
                ->all();

            return self::renderPanel('gallery', [
                'space' => $space,
                'media' => $media,
                'spaceUrl' => $space->createUrl('/gallery/list'),
            ]);
        } catch (\Throwable $e) {
            Yii::warning('Gallery embed failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Gallery could not be loaded.'));
        }
    }

    public static function renderCalendar(EngagementPage $page): string
    {
        [$space, $reason] = self::resolveSpaceWithReason($page);
        if ($space === null) {
            return self::unavailableMessage($reason, Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'Bind a Space in page settings to show the calendar.'
            ));
        }
        if (!Yii::$app->hasModule('calendar') || !self::moduleEnabled($space, 'calendar')) {
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Calendar is not enabled for this Space.'));
        }

        try {
            /** @var CalendarService $calendarService */
            $calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);
            $entries = $calendarService->getUpcomingEntries($space, 60, self::CALENDAR_LIMIT);

            return self::renderPanel('calendar', [
                'space' => $space,
                'entries' => $entries ?: [],
                'spaceUrl' => $space->createUrl('/calendar/view/index'),
            ]);
        } catch (\Throwable $e) {
            Yii::warning('Calendar embed failed: ' . $e->getMessage(), 'thiscovery-page-builder');
            return self::emptyMessage(Yii::t('ThiscoveryPageBuilderModule.base', 'Calendar could not be loaded.'));
        }
    }

    /**
     * @return array<int, array{
     *   title:string,summary:string,url:string,author:?string,created_at:?string,type:string,
     *   comment_count:int,comments:array<int, array{author:?string,body:string,created_at:?string}>
     * }>
     */
    protected static function fetchStreamItems(Space $space): array
    {
        $contents = Content::find()
            ->where([
                'content.contentcontainer_id' => $space->contentcontainer_id,
                'content.stream_channel' => 'default',
                'content.hidden' => 0,
            ])
            ->orderBy(['content.created_at' => SORT_DESC, 'content.id' => SORT_DESC])
            ->limit(self::STREAM_LIMIT * 2)
            ->all();

        $items = [];
        foreach ($contents as $content) {
            /** @var Content $content */
            try {
                $canView = true;
                try {
                    $canView = $content->canView();
                } catch (\Throwable $e) {
                    $canView = ((int) $content->visibility === Content::VISIBILITY_PUBLIC);
                }
                if (!$canView) {
                    continue;
                }
                $model = $content->getModel();
                if ($model === null) {
                    continue;
                }

                $typeLabel = method_exists($model, 'getContentName')
                    ? (string) $model->getContentName()
                    : (new \ReflectionClass($model))->getShortName();

                $rawTitle = (!empty($model->title) && is_string($model->title)) ? $model->title : '';
                $rawBody = '';
                if (method_exists($model, 'getContentDescription')) {
                    $rawBody = (string) $model->getContentDescription();
                } elseif (!empty($model->message) && is_string($model->message)) {
                    $rawBody = $model->message;
                }

                $title = self::toPlainPreview($rawTitle, 90);
                $summary = self::toPlainPreview($rawBody, 180);

                if ($title === '' && $summary !== '') {
                    $title = mb_strlen($summary) > 90 ? mb_substr($summary, 0, 87) . '…' : $summary;
                    $summary = mb_strlen($summary) > 90 ? $summary : '';
                }
                if ($title === '') {
                    $title = $typeLabel !== '' ? $typeLabel : Yii::t('ThiscoveryPageBuilderModule.base', 'Update');
                }
                if ($summary === $title) {
                    $summary = '';
                }

                $author = null;
                try {
                    $author = $content->createdBy?->displayName;
                } catch (\Throwable $e) {
                }

                $url = '#';
                try {
                    if (method_exists($model, 'getUrl')) {
                        $url = (string) $model->getUrl();
                    } elseif (method_exists($content, 'getUrl')) {
                        $url = (string) $content->getUrl();
                    }
                } catch (\Throwable $e) {
                }

                $objectModel = $model::class;
                $objectId = (int) $model->getPrimaryKey();
                $commentCount = 0;
                $comments = [];
                try {
                    $commentCount = (int) Comment::GetCommentCount($objectModel, $objectId);
                    if ($commentCount > 0) {
                        foreach (Comment::GetCommentsLimited($objectModel, $objectId, 2) as $comment) {
                            /** @var Comment $comment */
                            $comments[] = [
                                'author' => $comment->user->displayName ?? null,
                                'body' => self::toPlainPreview((string) $comment->message, 140),
                                'created_at' => $comment->created_at,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                }

                $items[] = [
                    'title' => $title,
                    'summary' => $summary,
                    'url' => $url,
                    'author' => $author,
                    'created_at' => $content->created_at,
                    'type' => $typeLabel,
                    'comment_count' => $commentCount,
                    'comments' => $comments,
                ];
            } catch (\Throwable $e) {
                continue;
            }

            if (count($items) >= self::STREAM_LIMIT) {
                break;
            }
        }

        return $items;
    }

    /**
     * Convert HumHub richtext (mentions, markdown links, etc.) to readable plain text.
     */
    public static function toPlainPreview(?string $text, int $maxLength = 180): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        try {
            if (class_exists(RichText::class)) {
                $text = RichText::convert($text, AbstractRichText::FORMAT_PLAINTEXT);
            }
        } catch (\Throwable $e) {
            // Fall through to regex cleanup.
        }

        // Leftover mention markdown: [Name](mention:guid "url")
        $text = preg_replace('/\[([^\]]+)\]\(mention:[^)]+\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\((?:file-guid|oembed|https?:)[^)]*\)/iu', '$1', $text) ?? $text;
        // Leftover plaintext mention form from RichText converter: @Name(https://...)
        $text = preg_replace('/@([^(\n]+)\((?:https?:|\/)[^)]+\)/u', '$1', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, max(0, $maxLength - 1)) . '…';
        }

        return $text;
    }

    public static function fileDownloadUrl(CFile $file): string
    {
        try {
            $base = $file->baseFile ?? null;
            if ($base && method_exists($base, 'getUrl')) {
                return (string) $base->getUrl();
            }
            if (method_exists($file, 'getUrl')) {
                return (string) $file->getUrl();
            }
        } catch (\Throwable $e) {
        }
        return '#';
    }

    public static function fileSizeLabel(CFile $file): string
    {
        try {
            $size = (int) ($file->baseFile->size ?? 0);
            if ($size <= 0) {
                return '';
            }
            if ($size < 1024) {
                return $size . ' B';
            }
            if ($size < 1048576) {
                return round($size / 1024, 1) . ' KB';
            }
            return round($size / 1048576, 1) . ' MB';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function mediaUrl(Media $media): string
    {
        try {
            foreach (['getSquarePreviewImageUrl', 'getPreviewImageUrl'] as $method) {
                if (method_exists($media, $method)) {
                    $url = (string) $media->$method();
                    if ($url !== '') {
                        return $url;
                    }
                }
            }
            $url = (string) ($media->getFileUrl(false) ?? '');
            if ($url !== '') {
                return $url;
            }
            $file = $media->baseFile ?? null;
            if ($file && method_exists($file, 'getPreviewUrl')) {
                return (string) $file->getPreviewUrl();
            }
            if ($file && method_exists($file, 'getUrl')) {
                return (string) $file->getUrl();
            }
        } catch (\Throwable $e) {
        }
        return '';
    }
}
