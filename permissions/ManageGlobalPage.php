<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\permissions;

use humhub\modules\admin\components\BaseAdminPermission;
use Yii;

class ManageGlobalPage extends BaseAdminPermission
{
    protected $id = 'thiscovery_page_builder_manage_global';
    protected $moduleId = 'thiscovery-page-builder';

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->title = Yii::t('ThiscoveryPageBuilderModule.base', 'Manage global engagement pages');
        $this->description = Yii::t(
            'ThiscoveryPageBuilderModule.base',
            'Allows editing and deleting network-level public engagement pages.'
        );
    }
}
