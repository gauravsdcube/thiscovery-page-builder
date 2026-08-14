<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace humhub\modules\engagementPages\helpers;

use humhub\modules\thiscoveryEditor\helpers\EditorHtml;

/**
 * Page-builder facade over Thiscovery Editor HTML conversion.
 */
class RichHtml
{
    public static function looksLikeHtml(string $content): bool
    {
        return EditorHtml::looksLikeHtml($content);
    }

    public static function toEditorHtml(?string $content): string
    {
        return EditorHtml::toEditorHtml($content);
    }

    public static function toHtml(?string $content): string
    {
        return EditorHtml::toHtml($content);
    }

    public static function purify(string $html): string
    {
        return EditorHtml::purify($html);
    }
}
