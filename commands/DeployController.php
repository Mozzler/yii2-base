<?php
/**
 */

namespace mozzler\base\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;

use mozzler\base\helpers\IndexHelper;

/*
    'uniqueMobileDeviceId' => [
				'columns' => ['mobileDeviceId'],
				'metadata' => [
					'unique' => 1
				],
				'duplicateMessage' => ['Mobile device already exists']
			]
 */

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DeployController extends Controller
{
    public $modelPaths = ['@app/models/'];

    /**
     * This command syncs all the indexes found in the application models.
     *
     * - New indexes are created
     * - Existing indexes are updated if they're different
     * - Deleted indexes are removed
     *
     * Everything is auto-detected based on the current indexes in the collection
     *
     * @return int Exit code
     */
    public function actionSync()
    {   
        $indexManager = \Yii::createObject('mozzler\base\components\IndexManager');

        // find all the models
        $models = $indexManager->buildModelClassList($this->modelPaths);
        
        foreach ($models as $className) {
            $indexManager->logs = [];

            $this->stdout('Processing model: '.$className."\n", Console::FG_GREEN);
            
            $indexManager->syncModelIndexes($className);
            $this->outputLogs($indexManager->logs);
        }

        return ExitCode::OK;
    }
    
    protected function outputLogs($logs) {
        foreach ($logs as $entry) {
            $this->stdout($entry['message']."\n", $entry['type'] == 'error' ? Console::FG_RED : null);
        }
    }
    
}
