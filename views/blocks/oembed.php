<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\models\UrlOembed;
use humhub\modules\thiscoveryEditor\helpers\EditorHtml;
use humhub\modules\thiscoveryEditor\helpers\EmbedUrl;
use humhub\modules\thiscoveryPageBuilder\blocks\OembedBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var OembedBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$url = trim((string) ($settings['url'] ?? ''));
$title = trim((string) ($settings['title'] ?? ''));
if ($url === '' || !EmbedUrl::isSupported($url)) {
    return;
}

$html = '';
try {
    $html = trim((string) (UrlOembed::getOEmbed($url, true) ?? ''));
} catch (\Throwable $e) {
    $html = '';
}

if ($html === '') {
    $html = trim((string) (EmbedUrl::iframeHtml($url, $title) ?? ''));
} elseif (!preg_match('/<iframe\b/i', $html) && EmbedUrl::isSupported($url)) {
    $html = trim((string) (EmbedUrl::iframeHtml($url, $title) ?? $html));
}

if ($html !== '') {
    $html = preg_replace(
        '#(https?:)?//(?:www\.)?youtube\.com/embed/#i',
        'https://www.youtube-nocookie.com/embed/',
        $html
    ) ?? $html;
    $html = EditorHtml::purify($html);
}

if (trim($html) === '') {
    return;
}
?>
<figure class="ep-block ep-oembed">
    <?php if ($title !== ''): ?>
        <div class="ep-oembed__title"><?= Html::encode($title) ?></div>
    <?php endif; ?>
    <div class="ep-oembed__media">
        <?= $html ?>
    </div>
</figure>
