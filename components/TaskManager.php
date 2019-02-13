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
class TaskManager extends \yii\base\Component
{

    public static $gcPercent = 1;

    public static $gcAgeDays = 30;

    /**
     * @param $taskClassName
     * @param array $taskConfig
     * @param bool $runNow if true then trigger the CLI TaskController command striaght away
     */
    public static function schedule($taskClassName, $taskConfig = [], $runNow = false)
    {

        // create a new instance of a mozzler\base\models\Task
        // populate the task
        // save the task
        // (background task cron job will execute any pending tasks)
    }


    /**
     * @param $taskObject \mozzler\base\models\Task - An instance of a task which should have the
     * @param $async boolean - If we should return straight away or wait until the command has been run
     * @throws \yii\base\InvalidConfigException
     */
    public static function run($taskObject, $async = true)
    {
        // load the JSON config from task
//        $config = $taskObject->config;
//        $task = \Yii::createObject(ArrayHelper::merge([
//            'class' => $taskObject->className
//        ], $config));

        if (empty($taskObject)) {
            return false;
        }

        $taskObject->getId();

        // Determine if running in Windows or *nix ( as per http://thisinterestsme.com/php-detect-operating-system-windows/ ) WINNT : Linux
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'; // Or could use > $isWindows = defined('PHP_WINDOWS_VERSION_MAJOR');

        $filePath = Yii::getAlias('@app') ."Yii" . (true === $isWindows ? '.bat' : ''); // e.g D:\www\bapp.viterra.com.au\commands

        // If running in Windows use https://www.somacon.com/p395.php as per http://de2.php.net/manual/en/function.exec.php#35731
        // Note: On Windows exec() will first start cmd.exe to launch the command. If you want to start an external program without starting cmd.exe use proc_open() with the bypass_shell option set.
//        pclose(popen("start \"bla\" \"" . $exe . "\" " . escapeshellarg($args), "r"));

        self::gc();
    }

    protected static function gc()
    {
        // 1% of the time delete all records that are older than 30 days
    }

}
