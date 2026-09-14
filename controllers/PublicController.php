<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\components\Controller;
use humhub\components\access\ControllerAccess;
use humhub\modules\thiscoveryPageBuilder\assets\ThiscoveryPageBuilderAsset;
use humhub\modules\thiscoveryPageBuilder\blocks\CommentsBlock;
use humhub\modules\thiscoveryPageBuilder\helpers\Url as PageUrl;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageComment;
use humhub\modules\thiscoveryPageBuilder\models\PageCommentForm;
use humhub\modules\thiscoveryPageBuilder\models\PageFollow;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionService;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Guest-friendly public URLs for collections, nested pages, and top-level pages.
 */
class PublicController extends Controller
{
    public $subLayout = '@thiscovery-page-builder/views/layouts/public';

    protected $access = ControllerAccess::class;

    /** Max guest/member comments per page per IP within the window. */
    private const COMMENT_RATE_LIMIT = 3;
    private const COMMENT_RATE_WINDOW = 600; // seconds

    protected function getAccessRules()
    {
        return [];
    }

    public function actionIndex()
    {
        // Legacy entry: show the primary collection (former directory homepage).
        $page = EngagementPage::ensureDirectoryPage();
        return $this->renderPage($page);
    }

    public function actionView($slug, $parentSlug = null)
    {
        ThiscoveryPageBuilderAsset::register($this->view);

        $page = EngagementPage::findByPublicPath((string) $slug, $parentSlug !== null ? (string) $parentSlug : null);
        if ($page === null) {
            throw new NotFoundHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'Page not found.'));
        }

        if ($page->isTemplate()) {
            throw new NotFoundHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'Page not found.'));
        }

        return $this->renderPage($page);
    }

    public function actionFollow($slug, $parentSlug = null)
    {
        $page = EngagementPage::findByPublicPath((string) $slug, $parentSlug !== null ? (string) $parentSlug : null);
        if ($page === null || !$page->canAccessPublic()) {
            throw new NotFoundHttpException();
        }

        $email = strtolower(trim((string) Yii::$app->request->post('email', '')));
        $flashKey = 'ep-follow-' . $page->id;

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $existing = PageFollow::findOne(['page_id' => $page->id, 'email' => $email]);
            if ($existing === null) {
                $follow = new PageFollow([
                    'page_id' => $page->id,
                    'email' => $email,
                ]);
                $follow->save();
            }
            Yii::$app->session->setFlash($flashKey, true);
        }

        return $this->redirect(PageUrl::toPublic($page) . '#ep-updates-' . $page->id);
    }

    public function actionComment($slug, $parentSlug = null)
    {
        $page = EngagementPage::findByPublicPath((string) $slug, $parentSlug !== null ? (string) $parentSlug : null);
        if ($page === null || !$page->canAccessPublic()) {
            throw new NotFoundHttpException();
        }

        $flashKey = 'ep-comment-' . $page->id;
        $settings = $this->commentsBlockSettings($page);
        $allowGuests = !empty($settings['allow_guests']);
        $isGuest = Yii::$app->user->isGuest;

        if ($isGuest && !$allowGuests) {
            Yii::$app->session->setFlash($flashKey, 'denied');
            return $this->redirect(PageUrl::toPublic($page) . '#ep-comments-' . $page->id);
        }

        if ($this->isCommentRateLimited($page)) {
            Yii::$app->session->setFlash($flashKey, 'rate');
            return $this->redirect(PageUrl::toPublic($page) . '#ep-comments-' . $page->id);
        }

        $form = new PageCommentForm();
        $form->requireCaptcha = $isGuest;
        $form->requireEmail = $isGuest && !empty($settings['require_email']);
        $form->requireName = !empty($settings['ask_name']);

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            $moderateGuests = !empty($settings['moderate_guests']);
            $status = ($isGuest && $moderateGuests)
                ? PageComment::STATUS_PENDING
                : PageComment::STATUS_APPROVED;

            $authorEmail = null;
            if (!$isGuest) {
                $authorEmail = Yii::$app->user->identity->email ?? null;
            } elseif (!empty($settings['require_email'])) {
                $authorEmail = $form->author_email ?: null;
            }

            $comment = new PageComment([
                'page_id' => $page->id,
                'body' => $form->body,
                'author_name' => $form->resolvedAuthorName(),
                'author_email' => $authorEmail ?: null,
                'user_id' => $isGuest ? null : (int) Yii::$app->user->id,
                'status' => $status,
                'ip_hash' => $this->clientIpHash(),
            ]);

            if ($comment->save()) {
                $this->bumpCommentRateLimit($page);
                Yii::$app->session->setFlash($flashKey, 'ok');
                return $this->redirect(PageUrl::toPublic($page) . '#ep-comments-' . $page->id);
            }
            Yii::$app->session->setFlash($flashKey, 'save');
        }

        ThiscoveryPageBuilderAsset::register($this->view);
        $this->pageTitle = self::maybeTranslatePageTitle($page);
        $this->view->params['engagementPage'] = $page;
        $this->view->params['epCommentForm'] = $form;

        return $this->render('@thiscovery-page-builder/views/page/view', [
            'contentContainer' => $page->content->container,
            'page' => $page,
            'canManage' => $page->canManage(),
            'publicLayout' => true,
            'isDirectory' => $page->isCollection(),
            'isPreview' => false,
        ]);
    }

    private function renderPage(EngagementPage $page)
    {
        ThiscoveryPageBuilderAsset::register($this->view);

        if (!$page->canAccessPublic()) {
            if (!$page->isPublished() && !$page->canManage()) {
                throw new ForbiddenHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'This page is not published.'));
            }
            if (Yii::$app->user->isGuest && $page->getAudienceKey() === EngagementPage::AUDIENCE_MEMBERS) {
                return $this->redirect(['/user/auth/login']);
            }
            throw new ForbiddenHttpException(Yii::t('ThiscoveryPageBuilderModule.base', 'You do not have permission to view this page.'));
        }

        $isPreview = $this->isPreviewMode($page);
        if (PageVersionService::isAvailable()) {
            (new PageVersionService())->applyPublicDefinition($page, $isPreview);
        }

        $this->pageTitle = self::maybeTranslatePageTitle($page);
        $this->view->params['engagementPage'] = $page;

        return $this->render('@thiscovery-page-builder/views/page/view', [
            'contentContainer' => $page->content->container,
            'page' => $page,
            'canManage' => $page->canManage(),
            'publicLayout' => true,
            'isDirectory' => $page->isCollection(),
            'isPreview' => $isPreview,
        ]);
    }

    private function isPreviewMode(EngagementPage $page): bool
    {
        if (!$page->canManage()) {
            return false;
        }
        $preview = (string) Yii::$app->request->get('preview', '');
        $revisionId = (int) Yii::$app->request->get('revision_id', 0);
        $editionId = (int) Yii::$app->request->get('edition_id', 0);
        return $preview === '1' || $preview === 'true' || $revisionId > 0 || $editionId > 0;
    }

    private function commentsBlockSettings(EngagementPage $page): array
    {
        foreach ($page->getSections() as $section) {
            if (($section['type'] ?? '') === CommentsBlock::TYPE) {
                $block = new CommentsBlock((array) ($section['settings'] ?? []));
                return $block->normalizeSettings();
            }
        }
        return (new CommentsBlock())->normalizeSettings();
    }

    private function clientIpHash(): string
    {
        $ip = (string) (Yii::$app->request->userIP ?: 'unknown');
        return hash('sha256', $ip . '|' . Yii::$app->id);
    }

    private function commentRateCacheKey(EngagementPage $page): string
    {
        return 'ep-comment-rate:' . $page->id . ':' . $this->clientIpHash();
    }

    private function isCommentRateLimited(EngagementPage $page): bool
    {
        $count = (int) Yii::$app->cache->get($this->commentRateCacheKey($page));
        return $count >= self::COMMENT_RATE_LIMIT;
    }

    private function bumpCommentRateLimit(EngagementPage $page): void
    {
        $key = $this->commentRateCacheKey($page);
        $count = (int) Yii::$app->cache->get($key);
        Yii::$app->cache->set($key, $count + 1, self::COMMENT_RATE_WINDOW);
    }

    /**
     * Soft-dep on thiscovery-translate for browser/page title.
     */
    protected static function maybeTranslatePageTitle(EngagementPage $page): string
    {
        $title = (string)$page->title;
        try {
            $tt = Yii::$app->getModule('thiscovery-translate');
            if ($tt && method_exists($tt, 'getIsEnabled') && $tt->getIsEnabled()
                && class_exists(\humhub\modules\thiscoveryTranslate\services\PageBuilderHook::class)) {
                $meta = \humhub\modules\thiscoveryTranslate\services\PageBuilderHook::translatePageMeta($page);
                if (!empty($meta['title'])) {
                    return (string)$meta['title'];
                }
            }
        } catch (\Throwable $e) {
            // keep source
        }
        return $title;
    }
}
