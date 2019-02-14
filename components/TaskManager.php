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
     * @return boolean
     */
    public static function run($taskObject, $async = true)
    {
        if (empty($taskObject)) {
            \Yii::error("Given an empty taskObject: " . var_export($taskObject, true));
            return false;
        }


        $taskId = $taskObject->getId();

        // Determine if running in Windows or *nix ( as per http://thisinterestsme.com/php-detect-operating-system-windows/ ) WINNT : Linux
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'; // Or could use > $isWindows = defined('PHP_WINDOWS_VERSION_MAJOR');

        $filePath = \Yii::getAlias('@app') . DIRECTORY_SEPARATOR . "Yii" . (true === $isWindows ? '.bat' : ''); // e.g D:\www\bapp.viterra.com.au\commands

        // If running in Windows use https://www.somacon.com/p395.php as per http://de2.php.net/manual/en/function.exec.php#35731
        // Note: On Windows exec() will first start cmd.exe to launch the command. If you want to start an external program without starting cmd.exe use proc_open() with the bypass_shell option set.

        // -------------------
        //  Run Async
        // -------------------
        if ($isWindows) {
            $runCommand = "\"$filePath\" \"task/run\" " . escapeshellarg($taskId);
            \Yii::info("Task {$taskObject->name}\nRunning Windows command: {$runCommand}");
            pclose(popen($runCommand, "r"));
        } else {
            $runCommand = "'{$filePath}' task/run " . escapeshellarg($taskId) . ' > /dev/null &';
            \Yii::info("Task {$taskObject->name}\nRunning Linux command: {$runCommand}");
            exec($runCommand);
        }

        // @todo: Create a version of this which runs individually

        self::gc();
    }

    protected static function gc()
    {
        // 1% of the time delete all records that are older than 30 days
    }

}
