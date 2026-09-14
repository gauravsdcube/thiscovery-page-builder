<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageTheme;
use humhub\modules\thiscoveryPageBuilder\services\PageStyleService;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionAdapter;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionService;
use Yii;

/**
 * Shared studio save: appearance fields, revisions, publish, preview.
 */
trait PageStudioSaveTrait
{
    protected function applyPostedAppearance(EngagementPage $page): void
    {
        $post = Yii::$app->request->post('EngagementPage', []);
        if (!is_array($post)) {
            $post = [];
        }

        if ($page->hasAttribute('theme_id')) {
            $tid = $post['theme_id'] ?? $page->theme_id;
            $page->theme_id = ($tid === '' || $tid === null) ? null : (int) $tid;
        }

        $style = $post['style'] ?? [];
        if (is_array($style)) {
            $page->style = (new PageStyleService())->normalize($style);
        }

        if ($page->hasAttribute('custom_css') && array_key_exists('custom_css', $post)) {
            $page->custom_css = (string) $post['custom_css'];
        }
    }

    protected function recordPageVersion(EngagementPage $page): void
    {
        if (!PageVersionService::isAvailable() || !$page->id) {
            return;
        }
        try {
            (new PageVersionService())->recordSave($page);
        } catch (\Throwable $e) {
            Yii::warning('Page version revision failed: ' . $e->getMessage(), 'thiscovery-page-builder');
        }
    }

    /**
     * Publish the revision created by the save that just ran.
     */
    protected function publishSavedDraft(EngagementPage $page): void
    {
        if (!PageVersionService::isAvailable() || !$page->id || $page->isTemplate()) {
            return;
        }
        try {
            $svc = new PageVersionService();
            $page->refresh();
            $latest = $svc->versions()->latestRevision(
                PageVersionAdapter::OWNER_TYPE,
                (int) $page->id
            );
            $edition = $svc->publish($page, $latest ? (int) $latest->id : null);
            Yii::$app->session->setFlash(
                'success',
                Yii::t(
                    'ThiscoveryPageBuilderModule.base',
                    'Saved and published edition #{n}. Visitors now see this version.',
                    ['n' => $edition ? $edition->edition_number : '?']
                )
            );
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
    }

    protected function redirectAfterStudioSave(EngagementPage $page)
    {
        $after = (string) Yii::$app->request->post('after_save', 'stay');
        if ($after === 'preview') {
            if ($page->isTemplate()) {
                Yii::$app->session->setFlash(
                    'info',
                    Yii::t('ThiscoveryPageBuilderModule.base', 'Templates cannot be previewed publicly. Open the page builder Share tab after creating a page from this template.')
                );
                return $this->redirect(Url::toEdit($page));
            }
            return $this->redirect(Url::toPreview($page));
        }
        if ($after === 'publish') {
            $this->publishSavedDraft($page);
            $url = Url::toEdit($page) . '?tab=settings&section=versions';
            return $this->redirect($url);
        }

        $tab = trim((string) Yii::$app->request->post('studio_tab', ''));
        $section = trim((string) Yii::$app->request->post('studio_section', ''));
        if (in_array($tab, ['css', 'share', 'versions'], true)) {
            $section = $tab;
            $tab = 'settings';
        }
        $url = Url::toEdit($page);
        if ($tab !== '' && $tab !== 'builder') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'tab=' . rawurlencode($tab);
            if ($tab === 'settings' && $section !== '') {
                $url .= '&section=' . rawurlencode($section);
            }
        }
        if (!Yii::$app->session->hasFlash('success') && !Yii::$app->session->hasFlash('error')) {
            Yii::$app->session->setFlash(
                'success',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Page saved.')
            );
        }
        return $this->redirect($url);
    }

    protected function assignDefaultThemeIfNeeded(EngagementPage $page): void
    {
        if (!$page->isNewRecord || !$page->hasAttribute('theme_id') || $page->theme_id) {
            return;
        }
        $default = PageTheme::findDefault();
        if ($default) {
            $page->theme_id = (int) $default->id;
        }
    }
}
