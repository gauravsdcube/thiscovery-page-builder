<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\blocks;

use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryForms\models\CustomForm;
use humhub\modules\thiscoveryPageBuilder\helpers\Url as PageUrl;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;
use yii\helpers\Url;

class ButtonBlock extends BaseBlock
{
    public const TYPE = 'button';

    public const ACTION_LINK = 'link';
    public const ACTION_PAGE = 'page';
    public const ACTION_FORM = 'form';
    public const ACTION_SPACE = 'space';
    public const ACTION_LOGIN = 'login';
    public const ACTION_REGISTER = 'register';
    public const ACTION_MAILTO = 'mailto';
    public const ACTION_TEL = 'tel';
    public const ACTION_SCROLL = 'scroll';
    public const ACTION_CUSTOM = 'custom';

    public const TONE_PRIMARY = 'primary';
    public const TONE_SECONDARY = 'secondary';
    public const TONE_OUTLINE = 'outline';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryPageBuilderModule.base', 'Button');
    }

    public static function actionOptions(): array
    {
        return [
            self::ACTION_LINK => Yii::t('ThiscoveryPageBuilderModule.base', 'Link (URL)'),
            self::ACTION_PAGE => Yii::t('ThiscoveryPageBuilderModule.base', 'Page'),
            self::ACTION_FORM => Yii::t('ThiscoveryPageBuilderModule.base', 'Form / survey'),
            self::ACTION_SPACE => Yii::t('ThiscoveryPageBuilderModule.base', 'Space'),
            self::ACTION_LOGIN => Yii::t('ThiscoveryPageBuilderModule.base', 'Login'),
            self::ACTION_REGISTER => Yii::t('ThiscoveryPageBuilderModule.base', 'Register'),
            self::ACTION_MAILTO => Yii::t('ThiscoveryPageBuilderModule.base', 'Email (mailto)'),
            self::ACTION_TEL => Yii::t('ThiscoveryPageBuilderModule.base', 'Phone (tel)'),
            self::ACTION_SCROLL => Yii::t('ThiscoveryPageBuilderModule.base', 'Scroll to section'),
            self::ACTION_CUSTOM => Yii::t('ThiscoveryPageBuilderModule.base', 'Custom URL'),
        ];
    }

    public static function toneOptions(): array
    {
        return [
            self::TONE_PRIMARY => Yii::t('ThiscoveryPageBuilderModule.base', 'Primary'),
            self::TONE_SECONDARY => Yii::t('ThiscoveryPageBuilderModule.base', 'Secondary'),
            self::TONE_OUTLINE => Yii::t('ThiscoveryPageBuilderModule.base', 'Outline'),
        ];
    }

    public function normalizeSettings(): array
    {
        $action = (string) ($this->settings['action'] ?? self::ACTION_LINK);
        if (!isset(self::actionOptions()[$action])) {
            $action = self::ACTION_LINK;
        }
        $tone = (string) ($this->settings['tone'] ?? self::TONE_PRIMARY);
        if (!isset(self::toneOptions()[$tone])) {
            $tone = self::TONE_PRIMARY;
        }

        return [
            'label' => $this->string('label') ?: Yii::t('ThiscoveryPageBuilderModule.base', 'Learn more'),
            'action' => $action,
            'url' => $this->string('url'),
            'page_id' => (int) ($this->settings['page_id'] ?? 0),
            'form_id' => (int) ($this->settings['form_id'] ?? 0),
            'space_id' => (int) ($this->settings['space_id'] ?? 0),
            'mailto' => $this->string('mailto'),
            'tel' => $this->string('tel'),
            'scroll_target' => ltrim($this->string('scroll_target'), '#'),
            'new_tab' => !empty($this->settings['new_tab']),
            'tone' => $tone,
            'icon' => $this->string('icon'),
        ];
    }

    /**
     * @return array{href:string,target:?string,attrs:array}
     */
    public function resolveLink(?EngagementPage $page = null): array
    {
        $s = $this->normalizeSettings();
        $href = '#';
        $target = !empty($s['new_tab']) ? '_blank' : null;
        $attrs = [];

        switch ($s['action']) {
            case self::ACTION_PAGE:
                $targetPage = $s['page_id'] ? EngagementPage::findOne((int) $s['page_id']) : null;
                if ($targetPage && $targetPage->isPublished()) {
                    $href = PageUrl::toPublic($targetPage);
                }
                break;
            case self::ACTION_FORM:
                if ($s['form_id'] && class_exists(CustomForm::class)) {
                    $form = CustomForm::findOne((int) $s['form_id']);
                    if ($form) {
                        try {
                            $href = $form->getUrl();
                        } catch (\Throwable $e) {
                            $href = '#';
                        }
                    }
                }
                break;
            case self::ACTION_SPACE:
                $spaceId = $s['space_id'] ?: ($page && $page->hasAttribute('bound_space_id') ? (int) $page->bound_space_id : 0);
                $space = $spaceId ? Space::findOne($spaceId) : null;
                if ($space) {
                    $href = $space->getUrl();
                }
                break;
            case self::ACTION_LOGIN:
                $href = Url::to(['/user/auth/login']);
                break;
            case self::ACTION_REGISTER:
                $href = Url::to(['/user/auth/register']);
                break;
            case self::ACTION_MAILTO:
                $email = $s['mailto'];
                $href = $email !== '' ? 'mailto:' . $email : '#';
                break;
            case self::ACTION_TEL:
                $tel = preg_replace('/\s+/', '', $s['tel']);
                $href = $tel !== '' ? 'tel:' . $tel : '#';
                break;
            case self::ACTION_SCROLL:
                $href = $s['scroll_target'] !== '' ? '#' . $s['scroll_target'] : '#';
                $attrs['data-ep-scroll'] = $s['scroll_target'];
                break;
            case self::ACTION_CUSTOM:
            case self::ACTION_LINK:
            default:
                $href = $s['url'] !== '' ? $s['url'] : '#';
                break;
        }

        return ['href' => $href, 'target' => $target, 'attrs' => $attrs];
    }
}
