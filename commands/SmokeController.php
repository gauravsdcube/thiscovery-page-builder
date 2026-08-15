<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryPageBuilder\commands;

use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use humhub\modules\thiscoveryPageBuilder\services\BlockRegistry;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class SmokeController extends Controller
{
    public function actionIndex()
    {
        $m = Yii::$app->getModule('thiscovery-page-builder');
        $this->stdout('module=' . ($m ? get_class($m) : 'null') . "\n", Console::FG_GREEN);
        $this->stdout('table=' . (Yii::$app->db->getTableSchema('engagement_page') ? 'yes' : 'no') . "\n");
        $this->stdout('types=' . implode(',', array_keys(BlockRegistry::types())) . "\n");

        $page = new EngagementPage();
        $page->title = 'Smoke Test Page';
        $page->slug = 'smoke-test-' . time();
        $page->status = EngagementPage::STATUS_DRAFT;
        $page->sections = [
            ['type' => 'hero', 'settings' => ['headline' => 'Hello']],
            ['type' => 'rich_text', 'settings' => ['title' => 'About', 'body' => 'Body']],
            ['type' => 'survey_cta', 'settings' => ['button_label' => 'Take survey']],
            ['type' => 'downloads', 'settings' => ['items' => [['label' => 'Doc', 'url' => 'https://example.com/doc.pdf']]]],
        ];

        if (!$page->validate(['title', 'slug', 'status', 'sections'])) {
            $this->stderr("validation failed\n", Console::FG_RED);
            print_r($page->getErrors());
            return ExitCode::DATAERR;
        }

        $this->stdout("validation=ok\n", Console::FG_GREEN);
        $this->stdout('normalized_sections=' . count($page->getSections()) . "\n");
        return ExitCode::OK;
    }
}
