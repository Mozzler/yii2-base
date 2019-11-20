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


        // -------------------
        //  Run Async
        // -------------------
        if ($isWindows) {
            // If running in Windows use https://www.somacon.com/p395.php as per http://de2.php.net/manual/en/function.exec.php#35731
            // Note: On Windows exec() will first start cmd.exe to launch the command. If you want to start an external program without starting cmd.exe use proc_open() with the bypass_shell option set.


//            https://ss64.com/vb/run.html

            // First, try and see if the COM extension can be used
            if (extension_loaded('com_dotnet')) {
                $WshShell = new \COM("WScript.Shell");
                $runCommand = "\"$filePath\" task/run {$taskId}";

                \Yii::info("Task {$taskObject->name}\nRunning Windows command: {$runCommand}");
                $taskObject->addLog("Running the Windows command via WScript.Shell: $runCommand");
                $taskObject->save();
                $oExec = $WshShell->Run($runCommand, 0, false);

                return $runCommand;
                /*
                 * Run(strCommand, intWindowStyle, bWaitOnReturn)
                 * Settings for intWindowStyle:
                 *
                 * 0 Hide the window (and activate another window.)
                 * 1 Activate and display the window. (restore size and position) Specify this flag when displaying a window for the first time.
                 * 2 Activate & minimize.
                 * 3 Activate & maximize.
                 * 4 Restore. The active window remains active.
                 * 5 Activate & Restore.
                 * 6 Minimize & activate the next top-level window in the Z order.
                 * 7 Minimize. The active window remains active.
                 * 8 Display the window in its current state. The active window remains active.
                 * 9 Restore & Activate. Specify this flag when restoring a minimized window.
                 * 10 Sets the show-state based on the state of the program that started the application.
                 */
            }
//            else if (self::windowsCommandExists('psexec')) {
//                // -- This allows multiple tasks to run in parallel
            // ### UNFORTUNATELY THIS DOESN'T WORK WHEN RUN FROM THE WINDOWS TASK SCHEDULER ###
//                $runCommand = "psexec -d \"$filePath\" task/run {$taskId} > null 2>&1"; // A bad version

            // == To run async tasks on Windows ==
            // 1. You need to download psexec from https://docs.microsoft.com/en-au/sysinternals/downloads/psexec
            // 2. You need to extract it to a folder on the computer/server
            // 3. You need to set the path in the Windows -> Control Panel -> System -> Advanced System Settings -> Environment Variables -> System Variables [Path]
            // 4. You need to manually open up the cmd prompt, run 'psexec' and click [OK] to the alert box which appears (only needs to be done once per machine)

//            }
            else {
                // -- The following will wait for the command to complete, so tasks are run serially
                $runCommand = "\"$filePath\" task/run {$taskId}"; // A serial version
            }

            \Yii::info("Task {$taskObject->name}\nRunning Windows command: {$runCommand}");
            $taskObject->addLog("Running the Windows command: $runCommand");
            $taskObject->save();
            pclose(popen($runCommand, "r"));
        } else {
            $runCommand = "'{$filePath}' task/run " . escapeshellarg($taskId) . ' > /dev/null &';
            \Yii::info("Task {$taskObject->name}\nRunning Linux command: {$runCommand}");
            $taskObject->addLog("Running the Linux command: $runCommand");
            $taskObject->save();
            exec($runCommand);
        }

        return $runCommand;
    }


    /**
     * @param $programName string
     * @return bool
     *
     * A basic check to see if a command can be run from the command line
     * This is really only for checking if psexec is installed, it's very basic
     *
     * Windows error suppression based on https://stackoverflow.com/a/1262726/7299352
     *
     * Uses the 'where' command which is similar to 'which' on Linux.
     * But needs error supression otherwise it outputs to stderror (at least on Windows 10):
     *      "INFO: Could not find files for the given pattern(s)."
     */
    protected static function windowsCommandExists($programName)
    {
        $wherePsexec = []; // We don't need this
        $wherePsexecReturnVal = null; // This is what we are interested in // 1 = Not found, 0 = found
        exec("where {$programName} > nul 2>&1", $wherePsexec, $wherePsexecReturnVal);

        return $wherePsexecReturnVal === 0 ? true : false;
    }

}
