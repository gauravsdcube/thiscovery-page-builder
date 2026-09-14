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
        $this->stdout('table=' . (Yii::$app->db->getTableSchema('thiscovery_page') ? 'yes' : 'no') . "\n");
        $this->stdout('types=' . implode(',', array_keys(BlockRegistry::types())) . "\n");

        $page = new EngagementPage();
        $page->title = 'Smoke Test Page';
        $page->slug = 'smoke-test-' . time();
        $page->status = EngagementPage::STATUS_DRAFT;
        $page->sections = [
            [
                'type' => 'hero',
                'settings' => ['headline' => 'Hello', 'border_radius' => 16],
            ],
            ['type' => 'rich_text', 'settings' => ['title' => 'About', 'body' => 'Body']],
            ['type' => 'survey_cta', 'settings' => ['button_label' => 'Take survey']],
            ['type' => 'downloads', 'settings' => ['items' => [['label' => 'Doc', 'url' => 'https://example.com/doc.pdf']]]],
            [
                'type' => 'contact_card',
                'settings' => [
                    'title' => 'The team',
                    'people' => [
                        [
                            'name' => 'Prof Graham Martin',
                            'role' => 'Research Professor',
                            'organisation' => 'THIS Institute, University of Cambridge',
                            'email' => 'graham.martin@thisinstitute.cam.ac.uk',
                            'image_guid' => 'card-photo-guid',
                        ],
                    ],
                ],
            ],
        ];

        if (!$page->validate(['title', 'slug', 'status', 'sections'])) {
            $this->stderr("validation failed\n", Console::FG_RED);
            print_r($page->getErrors());
            return ExitCode::DATAERR;
        }

        $this->stdout("validation=ok\n", Console::FG_GREEN);
        $this->stdout('normalized_sections=' . count($page->getSections()) . "\n");

        $types = array_column($page->getSections(), 'type');
        if (!in_array('contact_card', $types, true)) {
            $this->stderr("contact_card missing after normalize\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $guids = \humhub\modules\thiscoveryPageBuilder\helpers\FileHelper::collectGuidsFromSections($page->getSections());
        if (!in_array('card-photo-guid', $guids, true)) {
            $this->stderr("contact_card photo guid not collected\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $this->stdout("contact_card=ok\n", Console::FG_GREEN);

        $hero = $page->getSections()[0]['settings'] ?? [];
        if (($hero['border_radius'] ?? null) !== 16) {
            $this->stderr("hero border_radius not persisted\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $this->stdout("border_radius=ok\n", Console::FG_GREEN);

        $themeTable = Yii::$app->db->getTableSchema('thiscovery_page_theme', true);
        $this->stdout('theme_table=' . ($themeTable ? 'yes' : 'no') . "\n");
        if ($themeTable) {
            $css = (new \humhub\modules\thiscoveryPageBuilder\services\PageStyleService())->compile([
                'page' => ['background' => '#ffffff'],
            ]);
            if (!str_contains($css, '#ep-page')) {
                $this->stderr("style compile missing #ep-page\n", Console::FG_RED);
                return ExitCode::DATAERR;
            }
            $this->stdout("style_compile=ok\n", Console::FG_GREEN);
        }

        $versioning = class_exists(\humhub\modules\thiscoveryPageBuilder\services\PageVersionService::class)
            && \humhub\modules\thiscoveryPageBuilder\services\PageVersionService::isAvailable();
        $this->stdout('page_versioning=' . ($versioning ? 'on' : 'off') . "\n");

        $withEdition = (new \yii\db\Query())
            ->from('thiscovery_page')
            ->where(['is_template' => 0])
            ->andWhere(['not', ['current_edition_id' => null]])
            ->count();
        $this->stdout("pages_with_edition={$withEdition}\n");
        foreach (['sparcs2-survey-information', 'sparcs2-interview-information'] as $slug) {
            $row = (new \yii\db\Query())
                ->select(['id', 'status', 'current_edition_id', 'theme_id'])
                ->from('thiscovery_page')
                ->where(['slug' => $slug])
                ->one();
            if (!$row) {
                $this->stdout("{$slug}=missing\n");
                continue;
            }
            $this->stdout($slug . '=id' . $row['id'] . ',status=' . $row['status']
                . ',edition=' . ($row['current_edition_id'] ?: 'none')
                . ',theme=' . ($row['theme_id'] ?: 'none') . "\n");
        }

        return ExitCode::OK;
    }
}
