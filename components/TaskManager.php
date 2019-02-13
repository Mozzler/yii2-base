<?php
namespace mozzler\base\components;

use yii\helpers\ArrayHelper;

/**
 * To use the task manager, add it to the `web.php` components:
 * 
 * 'components' => [
 *      'taskManager' => [
 *          'class' => 'mozzler\base\components\TaskManager'
 *      ]
 * ]
 * 
 * To schedule a task
 * 
 * \Yii::$app->taskManager->schedule('app\scripts\MyCustomScript', ['option1' => true, 'option2' => false]);
 * 
 */
class TaskManager extends yii\base\Component
{

    public static $gcPercent = 1;

    public static $gcAgeDays = 30;

    /**
     * @param $taskClassName
     * @param array $taskConfig
     * @param bool $runNow if true then trigger the CLI TaskController command striaght away
     */
    public static function schedule($taskClassName, $taskConfig=[], $runNow = false)
    {

        // create a new instance of a mozzler\base\models\Task
        // populate the task
        // save the task
        // (background task cron job will execute any pending tasks)
    }

    // $taskModel is an instance of a mozzler\base\models\Task
    public static function run($taskModel)
    {
        // load the JSON config from task
        $config = $taskModel->config;
        $task = \Yii::createObject(ArrayHelper::merge([
            'class' => $taskModel->className
        ], $config));

        self::gc();
    }

    protected static function gc()
    {
        // 1% of the time delete all records that are older than 30 days
    }
    
}
