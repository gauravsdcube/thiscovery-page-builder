<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\permissions;

use humhub\modules\admin\components\BaseAdminPermission;
use Yii;

class CreateGlobalPage extends BaseAdminPermission
{
    protected $id = 'engagement_pages_create_global';
    protected $moduleId = 'engagement-pages';

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->title = Yii::t('EngagementPagesModule.base', 'Create global engagement pages');
        $this->description = Yii::t(
            'EngagementPagesModule.base',
            'Allows creating network-level public engagement pages.'
        );
    }
}
