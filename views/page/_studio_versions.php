<?php

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionAdapter;
use humhub\modules\thiscoveryPageBuilder\services\PageVersionService;
use humhub\modules\thiscoveryVersioning\widgets\VersionsPanel;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var EngagementPage $page */

if (!PageVersionService::isAvailable() || !$page->id) {
    echo '<div class="alert alert-warning">'
        . Html::encode(Yii::t(
            'ThiscoveryPageBuilderModule.base',
            'Versioning requires the Thiscovery Versioning module. Enable it in Administration → Modules, then turn on Page Builder versioning.'
        ))
        . '</div>';
    return;
}

$adapter = new PageVersionAdapter();
$ownerId = (int) $page->id;

echo VersionsPanel::widget([
    'ownerType' => PageVersionAdapter::OWNER_TYPE,
    'ownerId' => $ownerId,
    'canView' => $adapter->canViewVersions($ownerId),
    'canPublish' => $adapter->canPublishVersion($ownerId),
    'canRestore' => $adapter->canRestoreVersion($ownerId),
    'canDelete' => $adapter->canDeleteVersion($ownerId),
    'actionUrls' => [
        'publish' => $page->isGlobal()
            ? Url::to(['/thiscovery-page-builder/global/publish-version', 'id' => $ownerId])
            : $page->content->container->createUrl('/thiscovery-page-builder/page/publish-version', ['id' => $ownerId]),
        'restore' => $page->isGlobal()
            ? Url::to(['/thiscovery-page-builder/global/restore-version', 'id' => $ownerId])
            : $page->content->container->createUrl('/thiscovery-page-builder/page/restore-version', ['id' => $ownerId]),
        'deleteRevision' => $page->isGlobal()
            ? Url::to(['/thiscovery-page-builder/global/delete-revision', 'id' => $ownerId])
            : $page->content->container->createUrl('/thiscovery-page-builder/page/delete-revision', ['id' => $ownerId]),
        'deleteEdition' => $page->isGlobal()
            ? Url::to(['/thiscovery-page-builder/global/delete-edition', 'id' => $ownerId])
            : $page->content->container->createUrl('/thiscovery-page-builder/page/delete-edition', ['id' => $ownerId]),
    ],
]);
