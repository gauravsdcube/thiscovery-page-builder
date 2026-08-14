<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\controllers;

use humhub\components\Controller;
use humhub\components\access\ControllerAccess;
use humhub\modules\engagementPages\assets\EngagementPagesAsset;
use humhub\modules\engagementPages\blocks\CommentsBlock;
use humhub\modules\engagementPages\helpers\Url as PageUrl;
use humhub\modules\engagementPages\models\EngagementPage;
use humhub\modules\engagementPages\models\PageComment;
use humhub\modules\engagementPages\models\PageCommentForm;
use humhub\modules\engagementPages\models\PageFollow;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Guest-friendly public URLs: /{homepage-slug} and /{homepage-slug}/{page-slug}
 */
class PublicController extends Controller
{
    public $subLayout = '@engagement-pages/views/layouts/public';

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
        EngagementPagesAsset::register($this->view);

        $page = EngagementPage::ensureDirectoryPage();

        if (!$page->canAccessPublic()) {
            if (!$page->isPublished() && !$page->canManage()) {
                throw new ForbiddenHttpException(Yii::t('EngagementPagesModule.base', 'This page is not published.'));
            }
            if (Yii::$app->user->isGuest && $page->getAudienceKey() === EngagementPage::AUDIENCE_MEMBERS) {
                return $this->redirect(['/user/auth/login']);
            }
            throw new ForbiddenHttpException(Yii::t('EngagementPagesModule.base', 'You do not have permission to view this page.'));
        }

        $this->pageTitle = $page->title;
        $this->view->params['engagementPage'] = $page;

        return $this->render('@engagement-pages/views/page/view', [
            'contentContainer' => $page->content->container,
            'page' => $page,
            'canManage' => $page->canManage(),
            'publicLayout' => true,
            'isDirectory' => true,
        ]);
    }

    public function actionView($slug)
    {
        EngagementPagesAsset::register($this->view);

        $page = EngagementPage::findBySlug((string) $slug);
        if ($page === null) {
            throw new NotFoundHttpException(Yii::t('EngagementPagesModule.base', 'Page not found.'));
        }

        if ($page->isDirectoryHome()) {
            return $this->redirect(PageUrl::toDirectory());
        }

        if ($page->isTemplate()) {
            throw new NotFoundHttpException(Yii::t('EngagementPagesModule.base', 'Page not found.'));
        }

        if (!$page->canAccessPublic()) {
            if (!$page->isPublished() && !$page->canManage()) {
                throw new ForbiddenHttpException(Yii::t('EngagementPagesModule.base', 'This page is not published.'));
            }
            if (Yii::$app->user->isGuest && $page->getAudienceKey() === EngagementPage::AUDIENCE_MEMBERS) {
                return $this->redirect(['/user/auth/login']);
            }
            throw new ForbiddenHttpException(Yii::t('EngagementPagesModule.base', 'You do not have permission to view this page.'));
        }

        $this->pageTitle = $page->title;
        $this->view->params['engagementPage'] = $page;

        return $this->render('@engagement-pages/views/page/view', [
            'contentContainer' => $page->content->container,
            'page' => $page,
            'canManage' => $page->canManage(),
            'publicLayout' => true,
            'isDirectory' => false,
        ]);
    }

    public function actionFollow($slug)
    {
        $page = EngagementPage::findBySlug((string) $slug);
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

    public function actionComment($slug)
    {
        $page = EngagementPage::findBySlug((string) $slug);
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

        // Re-render page with form errors.
        EngagementPagesAsset::register($this->view);
        $this->pageTitle = $page->title;
        $this->view->params['engagementPage'] = $page;
        $this->view->params['epCommentForm'] = $form;

        return $this->render('@engagement-pages/views/page/view', [
            'contentContainer' => $page->content->container,
            'page' => $page,
            'canManage' => $page->canManage(),
            'publicLayout' => true,
            'isDirectory' => $page->isDirectoryHome(),
        ]);
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
}
