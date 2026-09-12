<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder;

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\thiscoveryPageBuilder\helpers\Url as PageUrl;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\models\PageHome;
use humhub\modules\thiscoveryPageBuilder\permissions\CreateGlobalPage;
use humhub\modules\thiscoveryPageBuilder\permissions\ManageGlobalPage;
use humhub\modules\space\controllers\SpaceController;
use humhub\modules\space\models\Space;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\space\widgets\Menu;
use humhub\widgets\TopMenu;
use Yii;
use yii\base\ActionEvent;
use humhub\modules\user\events\UserEvent;

class Events
{
    public static function onSpaceMenuInit($event): void
    {
        $space = $event->sender->space ?? null;
        if ($space === null || !$space->moduleManager->isEnabled('thiscovery-page-builder')) {
            return;
        }

        $event->sender->addItem([
            'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder'),
            'group' => 'modules',
            'url' => $space->createUrl('/thiscovery-page-builder/page/index'),
            'icon' => '<i class="fa fa-th-large"></i>',
            'isActive' => (
                Yii::$app->controller->module
                && Yii::$app->controller->module->id === 'thiscovery-page-builder'
                && Yii::$app->controller->id === 'page'
            ),
        ]);
    }

    public static function onAdminMenuInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        // Don't put permission checks only in isVisible — same pattern as Thiscovery Forms.
        $allowed = Yii::$app->user->isAdmin()
            || Yii::$app->user->can(ManageModules::class)
            || Yii::$app->user->can(ManageGlobalPage::class)
            || Yii::$app->user->can(CreateGlobalPage::class);

        if (!$allowed) {
            return;
        }

        $module = Yii::$app->getModule('thiscovery-page-builder');
        if ($module === null || (method_exists($module, 'getIsEnabled') && !$module->getIsEnabled())) {
            return;
        }

        /** @var AdminMenu $menu */
        $menu = $event->sender;
        $menu->addEntry(new MenuLink([
            'label' => Yii::t('ThiscoveryPageBuilderModule.base', 'Thiscovery Page Builder'),
            'id' => 'thiscovery-page-builder-admin',
            'icon' => 'th-large',
            'url' => ['/thiscovery-page-builder/global/index'],
            'sortOrder' => 555,
            'isActive' => ControllerHelper::isActivePath('thiscovery-page-builder', 'global'),
            'isVisible' => true,
        ]));
    }

    /**
     * When thiscovery-page-builder is enabled on a Space, the wall stream is admins-only.
     */
    public static function onSpaceControllerBeforeAction($event): void
    {
        /** @var SpaceController $controller */
        $controller = $event->sender;
        $action = $controller->action->id ?? '';
        if (!in_array($action, ['home', 'stream'], true)) {
            return;
        }

        $space = $controller->contentContainer ?? null;
        if (!$space instanceof Space) {
            return;
        }
        if (
            class_exists(\humhub\modules\thiscoverySpaceExperience\helpers\Experience::class)
            && \humhub\modules\thiscoverySpaceExperience\helpers\Experience::isActive($space)
        ) {
            return;
        }
        if (!$space->moduleManager->isEnabled('thiscovery-page-builder')) {
            return;
        }
        if ($space->isAdmin()) {
            return;
        }

        if ($action === 'stream') {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            Yii::$app->response->data = [
                'content' => '',
                'errors' => Yii::t('ThiscoveryPageBuilderModule.base', 'The Space stream is only available to administrators.'),
            ];
            Yii::$app->end();
        }

        $event->isValid = false;
        Yii::$app->response->content = $controller->render('@thiscovery-page-builder/views/space/stream_restricted', [
            'space' => $space,
        ]);
        Yii::$app->end();
    }

    public static function onTopMenuInit($event): void
    {
        if (class_exists(\humhub\modules\thiscoveryNavigation\helpers\Navigation::class)
            && \humhub\modules\thiscoveryNavigation\helpers\Navigation::isActive()) {
            return;
        }

        if (!Yii::$app->getModule('thiscovery-page-builder')) {
            return;
        }

        try {
            if (!(new EngagementPage())->hasAttribute('show_in_top_menu')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $isGuest = Yii::$app->user->isGuest;
        $pages = EngagementPage::find()
            ->where([
                'show_in_top_menu' => 1,
                'status' => EngagementPage::STATUS_PUBLISHED,
                'is_template' => 0,
            ])
            ->orderBy(['top_menu_sort_order' => SORT_ASC, 'title' => SORT_ASC])
            ->all();

        /** @var TopMenu $menu */
        $menu = $event->sender;
        foreach ($pages as $page) {
            $visibility = (string) ($page->top_menu_visibility ?: EngagementPage::TOP_MENU_ALL);
            if ($visibility === EngagementPage::TOP_MENU_GUESTS && !$isGuest) {
                continue;
            }
            if ($visibility === EngagementPage::TOP_MENU_USERS && $isGuest) {
                continue;
            }
            if (!$page->canAccessPublic()) {
                continue;
            }

            $label = trim((string) ($page->top_menu_label ?: $page->title));
            try {
                $tt = Yii::$app->getModule('thiscovery-translate');
                if ($tt && method_exists($tt, 'getIsEnabled') && $tt->getIsEnabled()
                    && class_exists(\humhub\modules\thiscoveryTranslate\services\PageBuilderHook::class)) {
                    $translated = \humhub\modules\thiscoveryTranslate\services\PageBuilderHook::translateTopMenuLabel($page);
                    if ($translated !== '') {
                        $label = $translated;
                    }
                }
            } catch (\Throwable $e) {
                // keep source label
            }
            $url = PageUrl::toPublic($page);
            $menu->addEntry(new MenuLink([
                'id' => 'thiscovery-page-' . $page->id,
                'label' => $label,
                'url' => $url,
                'icon' => 'file-text-o',
                'sortOrder' => (int) ($page->top_menu_sort_order ?: 400),
                'isActive' => (Yii::$app->request->url === $url)
                    || str_starts_with(ltrim(Yii::$app->request->pathInfo, '/'), ltrim($page->getPublicPath(), '/')),
                'isVisible' => true,
            ]));
        }
    }

    public static function onApplicationBeforeAction(ActionEvent $event): void
    {
        if (Yii::$app->request->isConsoleRequest) {
            return;
        }
        try {
            if (Yii::$app->db->schema->getTableSchema('thiscovery_page_home') === null) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $homepageUrl = PageHome::resolveHomeUrl();
        if ($homepageUrl) {
            if (Yii::$app->homeUrl !== $homepageUrl) {
                Yii::$app->homeUrl = $homepageUrl;
            }
            if (Yii::$app->user->isGuest && Yii::$app->user->loginUrl !== $homepageUrl) {
                $userModule = Yii::$app->getModule('user');
                if ($userModule && !$userModule->settings->get('auth.allowGuestAccess')) {
                    Yii::$app->user->loginUrl = $homepageUrl;
                }
            }
        }
    }

    public static function onAfterLogin(UserEvent $event): void
    {
        if (!Yii::$app->user->identity) {
            return;
        }
        if (Yii::$app->user->getReturnUrl() !== Yii::$app->homeUrl) {
            return;
        }
        $homepageUrl = PageHome::getUrlForUser();
        if ($homepageUrl) {
            Yii::$app->response->redirect($homepageUrl)->send();
        }
    }
}
