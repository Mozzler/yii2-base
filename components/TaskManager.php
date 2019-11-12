<?php

namespace mozzler\base\components;

use mozzler\base\models\Task;
use mozzler\base\scripts\ScriptBase;
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

    /**
     * @param string $scriptClassName The script::class
     * @param string $threadName If you need to execute multiple tasks at the same time, then you need to give each a name or number
     * @param array $scriptConfig
     * @param int $scriptTimeout
     * @param bool $runNow if true then trigger the CLI TaskController command straight away
     * @return Task
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public static function schedule($scriptClassName, $scriptConfig = [], $scriptTimeout = 60, $runNow = false, $threadName = '')
    {
        $unixTimestampMinuteStarted = round(floor(time() / 60) * 60); // When this minute started - Used for identifying specific tasks
        $taskConfig =
            [
                'config' => $scriptConfig,
                'scriptClass' => $scriptClassName,
                'timeoutSeconds' => $scriptTimeout,
                'status' => Task::STATUS_PENDING,
                'name' => "{$unixTimestampMinuteStarted}-" . ($runNow ? Task::TRIGGER_TYPE_INSTANT : Task::TRIGGER_TYPE_BACKGROUND) . "-{$scriptClassName}" . (empty($threadName) ? '' : '-' . $threadName),
                'triggerType' => $runNow ? Task::TRIGGER_TYPE_INSTANT : Task::TRIGGER_TYPE_BACKGROUND
            ];

        if (Task::TRIGGER_TYPE_BACKGROUND === $taskConfig['triggerType']) {
            throw new \yii\base\NotSupportedException("Background task scheduling is not yet supported, sorry :(");
        }

        /** @var Task $task */
        $task = Tools::createModel(Task::class, $taskConfig);

        if (!$task->save(true, null, false)) {
            throw new \Exception("Unable to save a task for execution: " . json_encode($task->getErrors()));
        }

        if (Task::TRIGGER_TYPE_INSTANT === $task->triggerType) {
            self::triggerTask($task);
        }

        return $task;
    }

    /**
     * @param $task Task
     * @return mixed
     */
    public static function runTask($task)
    {
        if (Task::STATUS_PENDING !== $task->status) {
            $task->addLog("Refusing to re-run task as it is not in a pending state", 'error');
            $task->save();
            return $task;
        }

        $task->status = $task::STATUS_INPROGRESS;
        $task->save();

        try {
            /** @var ScriptBase $script */
            $script = \Yii::createObject(ArrayHelper::merge($task->config, ['class' => $task->scriptClass]));
            $scriptReturn = $script->run($task); // !! Actually run the script (task)
        } catch (\Throwable $exception) {
            $task->status = Task::STATUS_ERROR;
            $task->addLog(Tools::returnExceptionAsString($exception), 'error');
            $task->save();
            return $task;
        }

        // -- Unless the script set the status to error, then save this as complete
        if ($task->status !== Task::STATUS_ERROR) {
            $task->status = Task::STATUS_COMPLETE;
        }

        $task->save();
        return $task;
    }

    /**
     * Trigger a task to be fired via the command line.
     * Called by the schedule command
     *
     * @param $taskObject \mozzler\base\models\Task - An instance of a task which should have the
     * @return boolean
     * @throws \yii\base\InvalidConfigException
     */
    protected static function triggerTask($taskObject)
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
    }

}
