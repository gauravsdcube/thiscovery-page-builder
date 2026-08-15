<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\permissions;

use humhub\libs\BasePermission;
use humhub\modules\space\models\Space;
use Yii;

class CreatePage extends BasePermission
{
    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_MODERATOR,
    ];

    protected $fixedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_USER,
        Space::USERGROUP_GUEST,
    ];

    protected $moduleId = 'thiscovery-page-builder';

    public function getTitle()
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Create engagement pages');
    }

    public function getDescription()
    {
        return Yii::t(
            'ThiscoveryPageBuilderModule.base',
            'Allows creating engagement project pages in this space.'
        );
    }
}
