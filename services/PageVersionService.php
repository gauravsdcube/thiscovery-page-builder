<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryVersioning\models\VersionEdition;
use humhub\modules\thiscoveryVersioning\models\VersionRevision;
use humhub\modules\thiscoveryVersioning\services\VersioningService;
use Yii;

/**
 * Page Builder helpers around the shared VersioningService.
 */
class PageVersionService
{
    private VersioningService $versions;
    private PageSnapshotService $snapshots;

    public function __construct(?VersioningService $versions = null, ?PageSnapshotService $snapshots = null)
    {
        $this->versions = $versions ?: new VersioningService();
        $this->snapshots = $snapshots ?: new PageSnapshotService();
    }

    public static function isAvailable(): bool
    {
        if (!Yii::$app->hasModule('thiscovery-versioning') || !class_exists(VersioningService::class)) {
            return false;
        }
        /** @var \humhub\modules\thiscoveryVersioning\Module|null $module */
        $module = Yii::$app->getModule('thiscovery-versioning');
        if (!$module instanceof \humhub\modules\thiscoveryVersioning\Module) {
            return false;
        }
        return $module->isOwnerEnabled(PageVersionAdapter::OWNER_TYPE);
    }

    public function versions(): VersioningService
    {
        return $this->versions;
    }

    public function recordSave(EngagementPage $page): ?VersionRevision
    {
        if (!self::isAvailable() || !$page->id || $page->isTemplate()) {
            return null;
        }
        return $this->versions->createRevision(
            PageVersionAdapter::OWNER_TYPE,
            (int) $page->id,
            $this->snapshots->export($page),
            (string) (int) $page->status
        );
    }

    public function publish(EngagementPage $page, ?int $revisionId = null): ?VersionEdition
    {
        if (!self::isAvailable() || !$page->id || $page->isTemplate()) {
            return null;
        }
        return $this->versions->publishRevision(
            PageVersionAdapter::OWNER_TYPE,
            (int) $page->id,
            $revisionId
        );
    }

    public function hasPublishedEdition(EngagementPage $page): bool
    {
        if ($page->hasAttribute('current_edition_id') && $page->current_edition_id) {
            return true;
        }
        if (!self::isAvailable() || !$page->id) {
            return false;
        }
        return (bool) $this->versions->currentEdition(PageVersionAdapter::OWNER_TYPE, (int) $page->id);
    }

    /**
     * Public URL: hydrate published (or historical) definition onto $page in memory.
     */
    public function applyPublicDefinition(EngagementPage $page, bool $isPreview = false): void
    {
        if (!self::isAvailable() || !$page->id || $page->isTemplate()) {
            return;
        }

        $revisionId = (int) Yii::$app->request->get('revision_id', 0);
        $editionId = (int) Yii::$app->request->get('edition_id', 0);

        if ($isPreview && ($revisionId || $editionId)) {
            if ($editionId) {
                $edition = $this->versions->findEdition($editionId);
                if ($edition && (int) $edition->owner_id === (int) $page->id
                    && $edition->owner_type === PageVersionAdapter::OWNER_TYPE) {
                    $this->snapshots->hydrateInMemory($page, $edition->getSnapshot());
                }
                return;
            }
            $rev = $this->versions->findRevision($revisionId);
            if ($rev && (int) $rev->owner_id === (int) $page->id
                && $rev->owner_type === PageVersionAdapter::OWNER_TYPE) {
                $this->snapshots->hydrateInMemory($page, $rev->getSnapshot());
            }
            return;
        }

        if ($isPreview) {
            // Working draft preview — leave live fields.
            return;
        }

        $current = $this->versions->currentEdition(PageVersionAdapter::OWNER_TYPE, (int) $page->id);
        $targetEditionId = $current
            ? (int) $current->id
            : ($page->hasAttribute('current_edition_id') && $page->current_edition_id
                ? (int) $page->current_edition_id
                : null);

        if (!$targetEditionId) {
            return;
        }
        $edition = $this->versions->findEdition($targetEditionId);
        if ($edition && (int) $edition->owner_id === (int) $page->id
            && $edition->owner_type === PageVersionAdapter::OWNER_TYPE) {
            $this->snapshots->hydrateInMemory($page, $edition->getSnapshot());
        }
    }
}
