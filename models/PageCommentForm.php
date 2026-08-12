<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\engagementPages\models;

use Yii;
use yii\base\Model;

/**
 * Public comment form (guests + members).
 */
class PageCommentForm extends Model
{
    public $author_name;
    public $author_email;
    public $body;
    public $captcha;

    public bool $requireCaptcha = true;
    public bool $requireEmail = true;
    public bool $requireName = true;

    public function rules()
    {
        $rules = [
            [['body'], 'required'],
            [['body'], 'string', 'min' => 3, 'max' => 4000],
            [['author_name'], 'string', 'max' => 120],
            [['author_email'], 'email'],
            [['author_email'], 'string', 'max' => 255],
        ];

        if ($this->requireName) {
            $rules[] = [['author_name'], 'required'];
            $rules[] = [['author_name'], 'string', 'min' => 2, 'max' => 120];
        }

        if ($this->requireEmail) {
            $rules[] = [['author_email'], 'required'];
        }

        if ($this->requireCaptcha) {
            $rules[] = [['captcha'], 'required'];
            $rules[] = [['captcha'], Yii::$app->captcha->getValidatorClass()];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'author_name' => Yii::t('EngagementPagesModule.base', 'Name'),
            'author_email' => Yii::t('EngagementPagesModule.base', 'Email'),
            'body' => Yii::t('EngagementPagesModule.base', 'Comment'),
            'captcha' => Yii::t('EngagementPagesModule.base', 'Verification code'),
        ];
    }

    /**
     * Display / storage name when the block does not ask for a name.
     */
    public function resolvedAuthorName(): string
    {
        $name = trim((string) $this->author_name);
        if ($name !== '') {
            return $name;
        }
        return Yii::t('EngagementPagesModule.base', 'Anonymous');
    }
}
