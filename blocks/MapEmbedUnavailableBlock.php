<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use humhub\modules\thiscoveryPageBuilder\helpers\MappingAvailability;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;

/**
 * Keeps existing map_embed sections valid when Mapping is not installed/enabled.
 * Not added to the studio palette.
 */
class MapEmbedUnavailableBlock extends BaseBlock
{
    public function getType(): string
    {
        return MappingAvailability::MAP_EMBED_TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Map');
    }

    public function normalizeSettings(): array
    {
        return [
            'map_id' => $this->intOrNull('map_id'),
            'height' => max(280, min(720, (int)($this->settings['height'] ?? 480))),
        ];
    }

    public function render(EngagementPage $page): string
    {
        $html = '<section class="ep-block ep-map-embed"><p class="text-muted">'
            . Yii::t(
                'ThiscoveryPageBuilderModule.base',
                'This map requires the Thiscovery Mapping module to be installed and enabled.'
            )
            . '</p></section>';
        return $this->wrapAligned($html);
    }
}
