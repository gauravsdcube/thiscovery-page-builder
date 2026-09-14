<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\thiscoveryPageBuilder\helpers\Url;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryVersioning\interfaces\VersionableAdapter;
use humhub\modules\thiscoveryVersioning\models\VersionEdition;
use humhub\modules\thiscoveryVersioning\permissions\DeleteVersion;
use humhub\modules\thiscoveryVersioning\permissions\PublishVersion;
use humhub\modules\thiscoveryVersioning\permissions\RestoreVersion;
use humhub\modules\thiscoveryVersioning\permissions\ViewVersions;
use Yii;

class PageVersionAdapter implements VersionableAdapter
{
    public const OWNER_TYPE = 'page';

    private PageSnapshotService $snapshots;

    public function __construct(?PageSnapshotService $snapshots = null)
    {
        $this->snapshots = $snapshots ?: new PageSnapshotService();
    }

    public function getOwnerType(): string
    {
        return self::OWNER_TYPE;
    }

    public function exportSnapshot(int $ownerId): array
    {
        $page = EngagementPage::findOne($ownerId);
        if (!$page) {
            return [];
        }
        return $this->snapshots->export($page);
    }

    public function importSnapshot(int $ownerId, array $snapshot): bool
    {
        $page = EngagementPage::findOne($ownerId);
        if (!$page) {
            return false;
        }
        return $this->snapshots->import($page, $snapshot, true);
    }

    public function canDeleteEdition(int $ownerId, int $editionId): bool
    {
        $edition = VersionEdition::findOne($editionId);
        if (!$edition || (int) $edition->owner_id !== $ownerId || $edition->owner_type !== self::OWNER_TYPE) {
            return false;
        }
        return (int) $edition->is_current !== 1;
    }

    public function canDeleteRevision(int $ownerId, int $revisionId): bool
    {
        $editions = VersionEdition::find()
            ->where(['owner_type' => self::OWNER_TYPE, 'owner_id' => $ownerId, 'revision_id' => $revisionId])
            ->all();
        foreach ($editions as $edition) {
            if (!$this->canDeleteEdition($ownerId, (int) $edition->id)) {
                return false;
            }
        }
        return true;
    }

    public function canViewVersions(int $ownerId, $user = null): bool
    {
        return $this->perm($ownerId, ViewVersions::class, $user);
    }

    public function canRestoreVersion(int $ownerId, $user = null): bool
    {
        return $this->perm($ownerId, RestoreVersion::class, $user);
    }

    public function canPublishVersion(int $ownerId, $user = null): bool
    {
        return $this->perm($ownerId, PublishVersion::class, $user);
    }

    public function canDeleteVersion(int $ownerId, $user = null): bool
    {
        return $this->perm($ownerId, DeleteVersion::class, $user);
    }

    public function getPreviewUrl(int $ownerId, ?int $revisionId = null, ?int $editionId = null): string
    {
        $page = EngagementPage::findOne($ownerId);
        if (!$page || !$page->canManage() || $page->isTemplate()) {
            return '';
        }
        return Url::toPreview($page, false, $revisionId, $editionId);
    }

    public function onEditionPublished(int $ownerId, int $editionId): void
    {
        $page = EngagementPage::findOne($ownerId);
        if (!$page || !$page->hasAttribute('current_edition_id')) {
            return;
        }
        $page->updateAttributes(['current_edition_id' => $editionId]);
        $page->current_edition_id = $editionId;
    }

    public function formatAvailabilityStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }
        $labels = EngagementPage::statusOptions();
        $int = (int) $status;
        return $labels[$int] ?? (string) $status;
    }

    protected function perm(int $ownerId, string $permissionClass, $user = null): bool
    {
        $page = EngagementPage::findOne($ownerId);
        if (!$page || !$page->canManage($user)) {
            return false;
        }
        try {
            if ($page->isGlobal()) {
                return Yii::$app->user->can($permissionClass) || $page->canManage($user);
            }
            $container = $page->content->container ?? null;
            if ($container && method_exists($container, 'getPermissionManager')) {
                return $container->getPermissionManager($user)->can($permissionClass) || $page->canManage($user);
            }
        } catch (\Throwable $e) {
            // Fall through to manage-page.
        }
        return $page->canManage($user);
    }
}
