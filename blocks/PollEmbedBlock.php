<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use humhub\modules\thiscoveryForms\models\CustomForm;
use Yii;

class PollEmbedBlock extends BaseBlock
{
    public const TYPE = 'poll_embed';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Quick poll');
    }

    public function normalizeSettings(): array
    {
        return [
            'form_id' => $this->intOrNull('form_id'),
        ];
    }

    public function getForm(): ?CustomForm
    {
        $id = $this->intOrNull('form_id');
        if ($id === null || !class_exists(CustomForm::class)) {
            return null;
        }
        $form = CustomForm::findOne($id);
        if ($form && $form->isPoll() && !$form->isTemplate()) {
            return $form;
        }
        return null;
    }
}
