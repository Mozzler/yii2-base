<?php
/**
 */

namespace mozzler\base\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;

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
    public function actionSync($modelsPath = '@app/models/')
    {
        // find all the models
        $models = [
            'app\models\Device'
        ];
        
        foreach ($models as $className) {
            $this->stdout('Processing model: '.$className."\n", Console::FG_GREEN);
            
            $indexes = $className::modelIndexes();
            foreach ($indexes as $indexName => $indexConfig)
            {
                $this->stdout("Processing index $indexName\n");
            }
        }

        return ExitCode::OK;
    }
}
