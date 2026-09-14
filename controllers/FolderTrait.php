<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\controllers;

use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageFolder;
use humhub\modules\thiscoveryPageBuilder\services\PageFolderService;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait FolderTrait
{
    protected function folderContainer()
    {
        return property_exists($this, 'contentContainer') ? $this->contentContainer : null;
    }

    protected function findFolder($id): PageFolder
    {
        $folder = PageFolderService::findFolder($this->folderContainer(), (int) $id);
        if (!$folder) {
            throw new NotFoundHttpException();
        }
        return $folder;
    }

    public function actionFolderEdit($id = null)
    {
        $this->assertFolderManage();
        $container = $this->folderContainer();
        $parentId = (int) Yii::$app->request->get('parent', 0);
        if ($id) {
            $folder = $this->findFolder($id);
        } else {
            $parent = $parentId ? $this->findFolder($parentId) : null;
            $folder = new PageFolder([
                'contentcontainer_id' => $container ? (int) $container->contentcontainer_id : null,
                'parent_id' => $parent ? (int) $parent->id : null,
            ]);
        }

        $request = Yii::$app->request;
        if ($request->isPost) {
            $folder->load($request->post());
            $folder->contentcontainer_id = $container ? (int) $container->contentcontainer_id : null;
            if ($folder->save()) {
                Yii::$app->session->setFlash('success', Yii::t('ThiscoveryPageBuilderModule.base', 'Folder saved.'));
                return $this->redirect(Url::toIndex($container, ['folder' => (int) $folder->id]));
            }
        }

        return $this->render('@thiscovery-page-builder/views/page/folder_edit', [
            'folder' => $folder,
            'isNew' => $folder->isNewRecord,
            'contentContainer' => $container,
            'parentOptions' => PageFolderService::treeOptions($container, $folder->isNewRecord ? null : (int) $folder->id),
        ]);
    }

    public function actionFolderDelete($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new ForbiddenHttpException();
        }
        $this->assertFolderManage();
        $folder = $this->findFolder($id);
        $parentId = (int) $folder->parent_id;
        $folder->delete();
        Yii::$app->session->setFlash(
            'success',
            Yii::t('ThiscoveryPageBuilderModule.base', 'Folder deleted. Pages in it were moved to Unfiled.')
        );
        return $this->redirect(Url::toIndex($this->folderContainer(), $parentId ? ['folder' => $parentId] : []));
    }

    protected function applyFolderFromRequest(EngagementPage $page): void
    {
        if (!$page->hasAttribute('folder_id')) {
            return;
        }
        $folderId = (int) Yii::$app->request->get('folder_id', 0);
        if ($folderId < 1 || $page->parent_id) {
            return;
        }
        $folder = PageFolderService::findFolder($this->folderContainer(), $folderId);
        if ($folder) {
            $page->folder_id = (int) $folder->id;
        }
    }

    protected function assertFolderManage(): void
    {
        if (method_exists($this, 'requireManage')) {
            $this->requireManage();
            return;
        }
        $probe = new EngagementPage($this->folderContainer());
        if (!$probe->canCreate() && !$probe->canManage()) {
            throw new ForbiddenHttpException();
        }
    }
}
