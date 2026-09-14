<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\services;

use Yii;

/**
 * Named theme tokens compiled to CSS under #ep-page. Empty values inherit the site theme.
 */
class PageStyleService
{
    public const ROOT = '#ep-page';

    /**
     * @return array<int, array{id: string, label: string, fields: array}>
     */
    public function groups(): array
    {
        $root = self::ROOT;
        return [
            [
                'id' => 'page',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Page'),
                'fields' => $this->boxFields($root, ['background', 'color', 'maxWidth', 'padding']),
            ],
            [
                'id' => 'headings',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Headings'),
                'fields' => $this->textFields(
                    $root . ' h1, ' . $root . ' h2, ' . $root . ' h3',
                    ['color', 'fontWeight']
                ),
            ],
            [
                'id' => 'body',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Body text'),
                'fields' => $this->textFields($root, ['color', 'fontSize']),
            ],
            [
                'id' => 'links',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Links'),
                'fields' => [
                    $this->field('color', Yii::t('ThiscoveryPageBuilderModule.base', 'Link colour'), 'color', $root . ' a:not(.btn)'),
                ],
            ],
            [
                'id' => 'hero',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Hero'),
                'fields' => $this->boxFields($root . ' .ep-hero', [
                    'background', 'color', 'borderColor', 'borderRadius', 'padding',
                ]),
            ],
            [
                'id' => 'cards',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Cards'),
                'fields' => $this->boxFields(
                    $root . ' .ep-contact__card, ' . $root . ' .ep-contact-card__person, '
                    . $root . ' .ep-callout, ' . $root . ' .ep-updates, ' . $root . ' .ep-collection-card, '
                    . $root . ' .ep-events__item',
                    ['background', 'color', 'borderColor', 'borderRadius']
                ),
            ],
            [
                'id' => 'buttons',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Buttons'),
                'fields' => [
                    $this->field('background', Yii::t('ThiscoveryPageBuilderModule.base', 'Primary background'), 'background-color', $root . ' .btn-primary, ' . $root . ' .ep-btn--primary'),
                    $this->field('color', Yii::t('ThiscoveryPageBuilderModule.base', 'Primary text'), 'color', $root . ' .btn-primary, ' . $root . ' .ep-btn--primary'),
                    $this->field('borderRadius', Yii::t('ThiscoveryPageBuilderModule.base', 'Corner radius'), 'border-radius', $root . ' .btn, ' . $root . ' .ep-btn'),
                ],
            ],
            [
                'id' => 'contact_card',
                'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Contact card'),
                'fields' => [
                    $this->field('accent', Yii::t('ThiscoveryPageBuilderModule.base', 'Accent colour'), 'border-color', $root . ' .ep-contact-card__person, ' . $root . ' .ep-contact-card__photo-wrap'),
                    $this->field('emailColor', Yii::t('ThiscoveryPageBuilderModule.base', 'Email colour'), 'color', $root . ' .ep-contact-card__email a'),
                    $this->field('borderRadius', Yii::t('ThiscoveryPageBuilderModule.base', 'Corner radius'), 'border-radius', $root . ' .ep-contact-card__person'),
                ],
            ],
        ];
    }

    public function compile(array $style): string
    {
        $rules = [];
        foreach ($this->groups() as $group) {
            $values = $style[$group['id']] ?? [];
            if (!is_array($values)) {
                continue;
            }
            foreach ($group['fields'] as $field) {
                $raw = trim((string) ($values[$field['name']] ?? ''));
                if ($raw === '') {
                    continue;
                }
                $safe = $this->sanitizeCssValue($field['css'], $raw);
                if ($safe === null) {
                    continue;
                }
                $selector = $field['selector'];
                if (!isset($rules[$selector])) {
                    $rules[$selector] = [];
                }
                $rules[$selector][$field['css']] = $safe;
            }
        }

        $accent = trim((string) (($style['contact_card']['accent'] ?? '')));
        if ($accent !== '') {
            $safeAccent = $this->sanitizeCssValue('color', $accent);
            if ($safeAccent !== null) {
                if (!isset($rules[self::ROOT])) {
                    $rules[self::ROOT] = [];
                }
                $rules[self::ROOT]['--ep-t-danger'] = $safeAccent;
            }
        }

        $out = [];
        foreach ($rules as $selector => $decls) {
            $parts = [];
            foreach ($decls as $prop => $val) {
                $parts[] = $prop . ': ' . $val;
            }
            $out[] = $selector . ' { ' . implode('; ', $parts) . '; }';
        }
        return implode("\n", $out);
    }

    public function normalize(array $style): array
    {
        $clean = [];
        foreach ($this->groups() as $group) {
            $values = $style[$group['id']] ?? [];
            if (!is_array($values)) {
                continue;
            }
            $row = [];
            foreach ($group['fields'] as $field) {
                $raw = trim((string) ($values[$field['name']] ?? ''));
                if ($raw === '' || $this->sanitizeCssValue($field['css'], $raw) === null) {
                    continue;
                }
                $row[$field['name']] = $raw;
            }
            if ($row) {
                $clean[$group['id']] = $row;
            }
        }
        return $clean;
    }

    public function mergeStyles(array $base, array $overlay): array
    {
        $out = $base;
        foreach ($overlay as $groupId => $values) {
            if (!is_array($values)) {
                continue;
            }
            if (!isset($out[$groupId]) || !is_array($out[$groupId])) {
                $out[$groupId] = [];
            }
            foreach ($values as $name => $raw) {
                $raw = trim((string) $raw);
                if ($raw === '') {
                    continue;
                }
                $out[$groupId][$name] = $raw;
            }
        }
        return $this->normalize($out);
    }

    public static function sanitizeCustomCss(string $css): string
    {
        $css = trim($css);
        if ($css === '') {
            return '';
        }
        $css = preg_replace('/<\/style/i', '', $css);
        $css = preg_replace('/@import\b/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/-moz-binding\s*:/i', '', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        return trim((string) $css);
    }

    public static function swatchHex(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-f]{3})$/i', $value, $m)) {
            $h = $m[1];
            return '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        if (preg_match('/^#([0-9a-f]{6})(?:[0-9a-f]{2})?$/i', $value, $m)) {
            return '#' . $m[1];
        }
        return '#ffffff';
    }

    protected function boxFields(string $selector, array $names): array
    {
        $map = [
            'background' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Background'), 'css' => 'background-color'],
            'color' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Text colour'), 'css' => 'color'],
            'borderColor' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Border colour'), 'css' => 'border-color'],
            'borderRadius' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Corner radius'), 'css' => 'border-radius'],
            'padding' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Padding'), 'css' => 'padding'],
            'maxWidth' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Max width'), 'css' => 'max-width'],
        ];
        $fields = [];
        foreach ($names as $name) {
            if (isset($map[$name])) {
                $fields[] = $this->field($name, $map[$name]['label'], $map[$name]['css'], $selector);
            }
        }
        return $fields;
    }

    protected function textFields(string $selector, array $names): array
    {
        $map = [
            'color' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Text colour'), 'css' => 'color'],
            'fontSize' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Font size'), 'css' => 'font-size'],
            'fontWeight' => ['label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Font weight'), 'css' => 'font-weight', 'type' => 'weight'],
        ];
        $fields = [];
        foreach ($names as $name) {
            if (!isset($map[$name])) {
                continue;
            }
            $fields[] = $this->field($name, $map[$name]['label'], $map[$name]['css'], $selector, $map[$name]['type'] ?? 'text');
        }
        return $fields;
    }

    protected function field(string $name, string $label, string $css, string $selector, string $type = 'text'): array
    {
        if ($type === 'text' && in_array($css, ['color', 'background-color', 'border-color'], true)) {
            $type = 'color';
        }
        $field = [
            'name' => $name,
            'label' => $label,
            'css' => $css,
            'selector' => $selector,
            'type' => $type,
        ];
        if ($type === 'weight') {
            $field['options'] = [
                '' => Yii::t('ThiscoveryPageBuilderModule.base', 'Theme default'),
                'normal' => Yii::t('ThiscoveryPageBuilderModule.base', 'Normal'),
                '600' => Yii::t('ThiscoveryPageBuilderModule.base', 'Semibold'),
                '700' => Yii::t('ThiscoveryPageBuilderModule.base', 'Bold'),
            ];
        }
        return $field;
    }

    protected function sanitizeCssValue(string $property, string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, ';') || str_contains($value, '{') || str_contains($value, '}')) {
            return null;
        }
        if (in_array($property, ['color', 'background-color', 'border-color'], true)) {
            if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
                return $value;
            }
            if (preg_match('/^(?:rgb|rgba|hsl|hsla)\(\s*[\d.%,\s\/]+\s*\)$/i', $value)) {
                return $value;
            }
            if (preg_match('/^var\(--[a-z0-9\-]+\)$/i', $value)) {
                return $value;
            }
            if (preg_match('/^(transparent|inherit|currentColor|none)$/i', $value)) {
                return strtolower($value);
            }
            return null;
        }
        if ($property === 'font-weight') {
            return preg_match('/^(normal|bold|[1-9]00)$/i', $value) ? strtolower($value) : null;
        }
        if (in_array($property, ['font-size', 'border-radius', 'max-width', 'padding'], true)) {
            if (preg_match('/^(?:auto|none|inherit|0)$/i', $value)) {
                return strtolower($value);
            }
            $parts = preg_split('/\s+/', $value) ?: [];
            if ($property !== 'padding' && count($parts) !== 1) {
                return null;
            }
            if ($property === 'padding' && (count($parts) < 1 || count($parts) > 4)) {
                return null;
            }
            foreach ($parts as $part) {
                if (!preg_match('/^-?\d+(?:\.\d+)?(?:px|rem|em|%)$/', $part)) {
                    return null;
                }
            }
            return $value;
        }
        return null;
    }
}
