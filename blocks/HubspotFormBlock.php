<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use humhub\modules\thiscoveryPageBuilder\helpers\HubSpotForm;
use Yii;

class HubspotFormBlock extends BaseBlock
{
    public const TYPE = 'hubspot_form';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'HubSpot form');
    }

    public function normalizeSettings(): array
    {
        $snippet = $this->string('embed_code');
        $fromSnippet = $snippet !== ''
            ? HubSpotForm::parseSnippet($snippet)
            : ['portal_id' => '', 'form_id' => '', 'region' => ''];

        $portalId = $this->string('portal_id');
        $formId = $this->string('form_id');
        $region = $this->string('region');

        if ($fromSnippet['portal_id'] !== '') {
            $portalId = $fromSnippet['portal_id'];
        }
        if ($fromSnippet['form_id'] !== '') {
            $formId = $fromSnippet['form_id'];
        }
        if ($snippet !== '' && $fromSnippet['region'] !== '') {
            $region = $fromSnippet['region'];
        }

        $ids = HubSpotForm::normalize($portalId, $formId, $region);

        return [
            'title' => $this->string('title'),
            'portal_id' => $ids['portal_id'],
            'form_id' => $ids['form_id'],
            'region' => $ids['region'],
        ];
    }
}
