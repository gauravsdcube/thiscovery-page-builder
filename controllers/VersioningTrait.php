<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionAdapter;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionService;
use humhub\modules\thiscoveryVersioning\services\VersioningService;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Studio versioning actions (publish / restore / delete).
 */
trait VersioningTrait
{
    public function actionPublishVersion($id)
    {
        $page = $this->findPage($id);
        $this->assertVersionManage($page);
        if (!PageVersionService::isAvailable()) {
            throw new NotFoundHttpException();
        }

        $revisionId = (int) Yii::$app->request->post('revision_id', 0) ?: null;
        try {
            $page->refresh();
            $edition = (new PageVersionService())->publish($page, $revisionId);
            Yii::$app->session->setFlash(
                'success',
                Yii::t(
                    'ThiscoveryPageBuilderModule.base',
                    'Published edition #{n}. Visitors now see this version.',
                    ['n' => $edition ? $edition->edition_number : '?']
                )
            );
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(Url::toEdit($page) . '?tab=settings&section=versions');
    }

    public function actionRestoreVersion($id)
    {
        $page = $this->findPage($id);
        $this->assertVersionManage($page);
        if (!PageVersionService::isAvailable()) {
            throw new NotFoundHttpException();
        }

        $revisionId = (int) Yii::$app->request->post('revision_id', 0);
        try {
            (new VersioningService())->restoreRevision(
                PageVersionAdapter::OWNER_TYPE,
                (int) $page->id,
                $revisionId
            );
            Yii::$app->session->setFlash(
                'success',
                Yii::t('ThiscoveryPageBuilderModule.base', 'Working draft restored. Publish when you want visitors to see it.')
            );
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(Url::toEdit($page) . '?tab=settings&section=versions');
    }

    public function actionDeleteRevision($id)
    {
        $page = $this->findPage($id);
        $this->assertVersionManage($page);
        if (!PageVersionService::isAvailable()) {
            throw new NotFoundHttpException();
        }

        $revisionId = (int) Yii::$app->request->post('revision_id', 0);
        try {
            (new VersioningService())->deleteRevision(
                PageVersionAdapter::OWNER_TYPE,
                (int) $page->id,
                $revisionId
            );
            Yii::$app->session->setFlash('success', Yii::t('ThiscoveryPageBuilderModule.base', 'Revision deleted.'));
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(Url::toEdit($page) . '?tab=settings&section=versions');
    }

    public function actionDeleteEdition($id)
    {
        $page = $this->findPage($id);
        $this->assertVersionManage($page);
        if (!PageVersionService::isAvailable()) {
            throw new NotFoundHttpException();
        }

        $editionId = (int) Yii::$app->request->post('edition_id', 0);
        try {
            (new VersioningService())->deleteEdition(
                PageVersionAdapter::OWNER_TYPE,
                (int) $page->id,
                $editionId
            );
            Yii::$app->session->setFlash('success', Yii::t('ThiscoveryPageBuilderModule.base', 'Edition deleted.'));
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(Url::toEdit($page) . '?tab=settings&section=versions');
    }

    protected function assertVersionManage(EngagementPage $page): void
    {
        if (!$page->canManage()) {
            throw new ForbiddenHttpException();
        }
        if (!Yii::$app->request->isPost) {
            throw new ForbiddenHttpException();
        }
    }
}
