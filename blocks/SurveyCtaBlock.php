<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\blocks;

use humhub\modules\thiscoveryForms\helpers\Url as FormUrl;
use humhub\modules\thiscoveryForms\models\CustomForm;
use Yii;

class SurveyCtaBlock extends BaseBlock
{
    public const TYPE = 'survey_cta';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('EngagementPagesModule.base', 'Survey CTA');
    }

    public function normalizeSettings(): array
    {
        return [
            'form_id' => $this->intOrNull('form_id'),
            'button_label' => $this->string(
                'button_label',
                Yii::t('EngagementPagesModule.base', 'Take the survey')
            ),
            'intro' => $this->string('intro'),
        ];
    }

    public function getForm(): ?CustomForm
    {
        $id = $this->intOrNull('form_id');
        if ($id === null || !class_exists(CustomForm::class)) {
            return null;
        }
        return CustomForm::findOne($id);
    }

    public function getFormUrl(): ?string
    {
        $form = $this->getForm();
        if ($form === null) {
            return null;
        }
        return FormUrl::toView($form);
    }
}
