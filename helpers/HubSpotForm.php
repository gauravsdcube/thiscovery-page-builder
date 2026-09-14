<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace humhub\modules\thiscoveryPageBuilder\helpers;

use humhub\helpers\Html;

/**
 * Parses HubSpot form embed snippets and emits an allowlisted loader.
 * Raw &lt;script&gt; from the editor is never persisted or printed.
 */
class HubSpotForm
{
    public const SCRIPT_SRC_PATTERN = '#^https://js(?:-[a-z0-9]+)?\.hsforms\.net/forms/embed/\d+\.js$#i';

    public const FORM_ID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    /** @var array<string, true> */
    private static array $registeredLoaders = [];

    /**
     * @return array{portal_id:string,form_id:string,region:string}
     */
    public static function parseSnippet(string $html): array
    {
        $portalId = '';
        $formId = '';
        $region = '';

        if (preg_match('/\bdata-portal-id\s*=\s*["\']?(\d+)/i', $html, $m)) {
            $portalId = $m[1];
        }
        if (preg_match('/\bdata-form-id\s*=\s*["\']?([0-9a-f-]{36})/i', $html, $m)) {
            $formId = strtolower($m[1]);
        }
        if (preg_match('/\bdata-region\s*=\s*["\']?([a-z0-9]+)/i', $html, $m)) {
            $region = strtolower($m[1]);
        }
        if (preg_match('#https://js(?:-([a-z0-9]+))?\.hsforms\.net/forms/embed/(\d+)\.js#i', $html, $m)) {
            if ($portalId === '') {
                $portalId = $m[2];
            }
            if ($region === '' && !empty($m[1])) {
                $region = strtolower($m[1]);
            }
        }

        $ids = self::normalize($portalId, $formId, $region !== '' ? $region : 'na1');
        if ($region === '') {
            $ids['region'] = '';
        }

        return $ids;
    }

    /**
     * @return array{portal_id:string,form_id:string,region:string}
     */
    public static function normalize(string $portalId, string $formId, string $region): array
    {
        $portalId = preg_replace('/\D+/', '', $portalId) ?? '';
        if (strlen($portalId) > 12) {
            $portalId = substr($portalId, 0, 12);
        }

        $formId = strtolower(trim($formId));
        if ($formId !== '' && !preg_match(self::FORM_ID_PATTERN, $formId)) {
            $formId = '';
        }

        $region = strtolower(trim($region));
        if ($region === '') {
            $region = 'na1';
        }
        if (!preg_match('/^[a-z0-9]{1,12}$/', $region)) {
            $region = 'na1';
        }

        return [
            'portal_id' => $portalId,
            'form_id' => $formId,
            'region' => $region,
        ];
    }

    public static function scriptSrc(string $region, string $portalId): ?string
    {
        $parsed = self::normalize($portalId, '', $region);
        $portalId = $parsed['portal_id'];
        $region = $parsed['region'];
        if ($portalId === '') {
            return null;
        }

        $host = $region === 'na1' ? 'js.hsforms.net' : ('js-' . $region . '.hsforms.net');
        $src = 'https://' . $host . '/forms/embed/' . $portalId . '.js';
        if (!preg_match(self::SCRIPT_SRC_PATTERN, $src)) {
            return null;
        }

        return $src;
    }

    /**
     * @param array{portal_id?:string,form_id?:string,region?:string} $settings
     */
    public static function isComplete(array $settings): bool
    {
        $portalId = (string) ($settings['portal_id'] ?? '');
        $formId = (string) ($settings['form_id'] ?? '');
        $region = (string) ($settings['region'] ?? '');

        return $formId !== ''
            && preg_match(self::FORM_ID_PATTERN, $formId)
            && self::scriptSrc($region, $portalId) !== null;
    }

    /**
     * One loader tag per unique HubSpot src per request. Empty if already emitted.
     */
    public static function loaderTag(string $src): string
    {
        if (!preg_match(self::SCRIPT_SRC_PATTERN, $src) || isset(self::$registeredLoaders[$src])) {
            return '';
        }
        self::$registeredLoaders[$src] = true;

        $options = [
            'src' => $src,
            'defer' => true,
        ];
        Html::setNonce($options);

        return Html::tag('script', '', $options);
    }
}
