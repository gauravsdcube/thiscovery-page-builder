<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace humhub\modules\thiscoveryPageBuilder\helpers;

use humhub\modules\thiscoveryPageBuilder\blocks\CustomHtmlBlock;
use humhub\modules\web\security\helpers\Security;
use Yii;

/**
 * Raw HTML for the admin-only Custom HTML section.
 * Not purified. Script tags receive the page CSP nonce on output.
 */
class CustomHtml
{
    public const MAX_BYTES = 100000;

    public static function canManage(): bool
    {
        try {
            $user = Yii::$app->user;
            if ($user->getIsGuest()) {
                return false;
            }
            return $user->isAdmin();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function normalize(string $html): string
    {
        $html = str_replace("\0", '', $html);
        if ($html === '') {
            return '';
        }
        if (strlen($html) > self::MAX_BYTES) {
            $html = function_exists('mb_strcut')
                ? (string) mb_strcut($html, 0, self::MAX_BYTES, 'UTF-8')
                : substr($html, 0, self::MAX_BYTES);
        }
        return $html;
    }

    public static function forPublic(string $html): string
    {
        $html = self::normalize($html);
        if ($html === '') {
            return '';
        }
        return self::applyNonce($html);
    }

    public static function applyNonce(string $html): string
    {
        try {
            $nonce = (string) Security::getNonce(true);
        } catch (\Throwable $e) {
            return $html;
        }
        if ($nonce === '') {
            return $html;
        }

        $html = str_replace(['{{ nonce }}', 'RANDOM_NONCE_VALUE'], $nonce, $html);
        $encoded = htmlspecialchars($nonce, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return preg_replace_callback(
            '/<script\b([^>]*)>/i',
            static function (array $m) use ($encoded): string {
                $attrs = preg_replace('/\snonce\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $m[1]) ?? $m[1];
                return '<script' . $attrs . ' nonce="' . $encoded . '">';
            },
            $html
        ) ?? $html;
    }

    /**
     * Non-admins cannot add Custom HTML or change stored markup.
     * They may reorder or delete existing sections whose HTML already matches.
     *
     * @param array<int, array> $sections
     * @param array<int, array> $previous
     * @return array<int, array>
     */
    public static function restrictPostedSections(array $sections, array $previous): array
    {
        if (self::canManage()) {
            return $sections;
        }

        $allowed = [];
        self::collectHtml($previous, $allowed);

        return self::filterTree($sections, $allowed);
    }

    /**
     * @param array<int, array> $sections
     * @param array<string, true> $allowed
     * @return array<int, array>
     */
    private static function filterTree(array $sections, array $allowed): array
    {
        $out = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $type = (string) ($section['type'] ?? '');
            if ($type === CustomHtmlBlock::TYPE) {
                $html = (string) ($section['settings']['html'] ?? '');
                if ($html === '' || !isset($allowed[$html])) {
                    continue;
                }
            }
            if ($type === 'container' && isset($section['children']) && is_array($section['children'])) {
                $section['children'] = self::filterTree($section['children'], $allowed);
            }
            $out[] = $section;
        }
        return $out;
    }

    /**
     * @param array<int, array> $sections
     * @param array<string, true> $allowed
     */
    private static function collectHtml(array $sections, array &$allowed): void
    {
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            if (($section['type'] ?? '') === CustomHtmlBlock::TYPE) {
                $html = (string) ($section['settings']['html'] ?? '');
                if ($html !== '') {
                    $allowed[$html] = true;
                }
            }
            if (isset($section['children']) && is_array($section['children'])) {
                self::collectHtml($section['children'], $allowed);
            }
        }
    }
}
