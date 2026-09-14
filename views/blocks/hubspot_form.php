<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryPageBuilder\blocks\HubspotFormBlock;
use humhub\modules\thiscoveryPageBuilder\helpers\HubSpotForm;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;

/** @var HubspotFormBlock $block */
/** @var EngagementPage $page */
/** @var array $settings */

$title = trim((string) ($settings['title'] ?? ''));
if (!HubSpotForm::isComplete($settings)) {
    return;
}

$portalId = (string) $settings['portal_id'];
$formId = (string) $settings['form_id'];
$region = (string) $settings['region'];
$src = HubSpotForm::scriptSrc($region, $portalId);
if ($src === null) {
    return;
}

$loader = HubSpotForm::loaderTag($src);
?>
<section class="ep-block ep-hubspot-form">
    <?= $loader ?>
    <?php if ($title !== ''): ?>
        <h2 class="ep-hubspot-form__title"><?= Html::encode($title) ?></h2>
    <?php endif; ?>
    <div class="hs-form-frame"
         data-region="<?= Html::encode($region) ?>"
         data-form-id="<?= Html::encode($formId) ?>"
         data-portal-id="<?= Html::encode($portalId) ?>"></div>
</section>
