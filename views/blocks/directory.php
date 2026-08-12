<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

/**
 * Legacy view path for type=directory sections before rename to collection.
 * @var mixed $block
 * @var mixed $page
 * @var array $settings
 */
echo $this->render('collection', [
    'block' => $block,
    'page' => $page,
    'settings' => $settings,
]);
