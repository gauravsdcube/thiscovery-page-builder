<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\permissions;

use humhub\modules\admin\components\BaseAdminPermission;
use Yii;

class CreateGlobalPage extends BaseAdminPermission
{
    protected $id = 'engagement_pages_create_global';
    protected $moduleId = 'thiscovery-page-builder';

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Create global engagement pages');
        $this->description = Yii::t(
            'ThiscoveryPageBuilderModule.base',
            'Allows creating network-level public engagement pages.'
        );
    }
}
