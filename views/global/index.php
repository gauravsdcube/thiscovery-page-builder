<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/** @var $pages */
/** @var $templates */
/** @var $canCreate */
/** @var $pendingComments */
/** @var $subscriptionCount */
/** @var $canViewHelp */

echo $this->render('@thiscovery-page-builder/views/page/index', [
    'pages' => $pages ?? [],
    'templates' => $templates ?? [],
    'canCreate' => $canCreate ?? false,
    'contentContainer' => null,
    'canViewHelp' => $canViewHelp ?? false,
    'pendingComments' => $pendingComments ?? 0,
    'subscriptionCount' => $subscriptionCount ?? 0,
]);
