<?php

namespace mozzler\base\components;

use mozzler\base\exceptions\BaseException;
use mozzler\base\models\Task;
use mozzler\base\scripts\ScriptBase;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

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
 * Note: Even admin users aren't allowed to update a Task, so the saves are done without permission checks.
 *
 */
class TaskManager extends \yii\base\Component
{

    /**
     * @param string $scriptClassName The script::class
     * @param string $threadName If you need to execute multiple tasks at the same time, then you need to give each a name or number
     * @param array $scriptConfig
     * @param int $scriptTimeout
     * @param bool $runNow if true then trigger the CLI TaskController command straight away which is the default as background tasks aren't supported, create a Cron entry instead
     * @return Task
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public static function schedule($scriptClassName, $scriptConfig = [], $scriptTimeout = 60, $runNow = true, $threadName = '')
    {
        $unixTimestampMinuteStarted = round(floor(time() / 60) * 60); // When this minute started - Used for identifying specific tasks
        $taskConfig =
            [
                'config' => $scriptConfig,
                'scriptClass' => $scriptClassName,
                'timeoutSeconds' => $scriptTimeout,
                'status' => Task::STATUS_PENDING,
                'name' => "{$unixTimestampMinuteStarted}-" . ($runNow ? Task::TRIGGER_TYPE_INSTANT : Task::TRIGGER_TYPE_BACKGROUND) . "-{$scriptClassName}" . (empty($threadName) ? '' : '-' . $threadName),
                'triggerType' => Task::TRIGGER_TYPE_INSTANT, // @todo: Support Task::TRIGGER_TYPE_BACKGROUND
            ];

        if ($runNow !== true) {
            \Yii::warning("Background task scheduling is not yet supported, trying instantly triggering it using runTask instead or running via Cron");
        }

        /** @var Task $task */
        $task = Tools::createModel(Task::class, $taskConfig);

        if (!$task->saveAndLogErrors()) {
            throw new BaseException("Unable to save the {$task->ident()}", 500, null, [
                'Save Error(s)' => $task->getErrors(),
                'Task' => $task->toArray()
            ]);
        }

        if (true === $runNow) {
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
            $task->save(true, null, false);
            return $task;
        }

        $task->status = $task::STATUS_INPROGRESS;
        $task->save(true, null, false);

        try {
            /** @var ScriptBase $script */
            $script = \Yii::createObject(ArrayHelper::merge($task->config, ['class' => $task->scriptClass]));
            $scriptReturn = $script->run($task); // !! Actually run the script (task)
        } catch (\Throwable $exception) {
            $task->status = Task::STATUS_ERROR;
            $task->addLog(\Yii::$app->t::returnExceptionAsString($exception), 'error');
            $task->save(true, null, false);
            return $task;
        }

        // -- Unless the script set the status to error, then save this as complete
        if ($task->status !== Task::STATUS_ERROR) {
            $task->status = Task::STATUS_COMPLETE;
        }

        $task->save(true, null, false);
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
        $filePath = self::yiiCommandLocation();

        $arguments = ["task/run", $taskId];
        $isBackground = true;

        $taskObject->addLog("About to run the {$taskObject->ident()} with: " . VarDumper::export(['filePath' => $filePath, 'arguments' => $arguments, 'isBackground' => $isBackground]));
        $taskObject->saveAndLogErrors();
        try {
            $runCommand = self::runCommand($filePath, $arguments, $isBackground);
            // Re-fetch the task object as it may have been updated when the command was run
            // which means the current $taskObject is stale
            $taskObject = Tools::getModel(Task::class, $taskId, false);
            $taskObject->addLog("Ran the {$taskObject->ident()} with the command {$runCommand['runCommand']} which had the exitCode {$runCommand['exitCode']} and the output:\n" . VarDumper::export($runCommand['output']));
            $taskObject->saveAndLogErrors();
        } catch (\Throwable $exception) {
            // Re-fetch the task object as it may have been updated when the command was run
            // which means the current $taskObject is stale
            $taskObject = Tools::getModel(Task::class, $taskId, false);
            // Save the error to the task for visibility
            $taskObject->addLog("Exception whilst running the {$taskObject->ident()} " . \Yii::$app->t::returnExceptionAsString($exception));
            $taskObject->saveAndLogErrors();
            throw $exception;
        }

        return $runCommand;
    }

    public static function isWindows()
    {
        // Determine if running in Windows or *nix ( as per http://thisinterestsme.com/php-detect-operating-system-windows/ ) WINNT : Linux
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'; // Or could use > $isWindows = defined('PHP_WINDOWS_VERSION_MAJOR');
    }


    public static function yiiCommandLocation()
    {
        return \Yii::getAlias('@app') . DIRECTORY_SEPARATOR . "yii" . (true === self::isWindows() ? '.bat' : ''); // e.g \app\yii or C:\www\yii.bat
    }


    /**
     * @param $filePath string The command to run
     * @param array $arguments An array of arguments for the command, please note: YOU WILL NEED TO escapeshellarg them as needed!
     * @param bool $runAsync if on Linux you want the command to run in the background or not (In Windows this is predicated on the \COM module being installed
     * @param string $currentDirectory
     * @param bool $escapeArgs If the arguments should be automatically escaped (wrapped in 'single quotes')
     * @return array ['runCommand', 'exitCode', 'output' ] the command that was run, the exitCode ( more than 0 is a failure on *nix ) and the output
     */
    public static function runCommand($filePath, $arguments = [], $runAsync = true, $currentDirectory = null, $escapeArgs = true, $redirectOutput = '2>&1')
    {

        if (!empty($currentDirectory)) {
            chdir($currentDirectory);
        }

        // Allow for people to have escaped arguments already (possibly needed if wrapping every arg in 'single quotes' causes issues, but use with extreme caution)
        $argumentsEscaped = array_map($escapeArgs ? 'escapeshellarg' : null, $arguments); // Escape each set of arguments

        // The run command
        $runCommand = "\"$filePath\" " . implode(' ', $argumentsEscaped);
        $exitCode = null;
        $output = [];
        // -------------------
        //  Run Async
        // -------------------
        if (self::isWindows()) {
            // If running in Windows use https://www.somacon.com/p395.php as per http://de2.php.net/manual/en/function.exec.php#35731
            // Note: On Windows exec() will first start cmd.exe to launch the command. If you want to start an external program without starting cmd.exe use proc_open() with the bypass_shell option set.

            // First, try and see if the COM extension can be used for triggering tasks async
            // Note: In the php.ini file (or extensions directory) you'll likely need to add the following to enable COM:
            // extension=com_dotnet
            try {

                if (extension_loaded('com_dotnet') && $runAsync) {
                    $WshShell = new \COM("WScript.Shell");
                    \Yii::info("Running Windows command via WScript.Shell:  {$runCommand}");
                    $oExec = $WshShell->Run($runCommand, 0, false);
                    return ['runCommand' => $runCommand, 'exitCode' => $exitCode, 'output' => $oExec];
                    /*
                     * https://ss64.com/vb/run.html
                     *
                     * $WshShell->Run(strCommand, intWindowStyle, bWaitOnReturn)
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
            } catch (\Throwable $exception) {
                \Yii::error("Error whilst trying to run the Windows command async using com_dotnet and WScript.Shell: " . \Yii::$app->t::returnExceptionAsString($exception));
            }
            \Yii::info("Running Windows command using popen/pclose: {$runCommand}");
            pclose(popen($runCommand, "r"));
        } else {

            $runCommand .= ' ' . $redirectOutput; // Redirect stderr to stdout for capturing in the $output (or you might want to ignore stdError by changing it to 2>/dev/null or whatever else is needed )
            if ($runAsync) {
                // Run as a background task
                $runCommand .= ' &';
            }
            \Yii::info("Running Linux command: {$runCommand}");

            $exitCode = null;
            exec($runCommand, $output, $exitCode);
            \Yii::info("Ran the command {$runCommand}\n" . VarDumper::export(['exitCode' => $exitCode, 'output' => $output]));
        }

        return ['runCommand' => $runCommand, 'exitCode' => $exitCode, 'output' => $output];
    }

    /**
     * Process Task
     *
     * For running a task directly.
     * This is only expected to be used for unit tests which can't trigger background tasks properly.
     *
     * For app code you should instead use \Yii::$app->taskManager->schedule();
     *
     * @param $scriptClass string
     * @param $config array any task specific configuration settings, e.g customerId
     * @param $timeoutSeconds int maximum running time
     * @return Task
     * @throws \yii\base\InvalidConfigException
     * @see schedule
     */
    public function processTask($scriptClass, $config = [], $timeoutSeconds = 60)
    {
        $unixTimestampMinuteStarted = round(floor(time() / 60) * 60); // When this minute started - Used for identifying specific tasks

        // Unfortunately we can't use the $taskManager->schedule as it doesn't support background tasks and we don't want this to run in an async thread outside of the test environment
        // $taskManager->schedule($this->queuePushNotificationsScriptClass, [], 60, false);
        $taskConfig =
            [
                'config' => $config,
                'scriptClass' => $scriptClass,
                'timeoutSeconds' => $timeoutSeconds,
                'status' => Task::STATUS_PENDING,
                'name' => "{$unixTimestampMinuteStarted}-" . Task::TRIGGER_TYPE_BACKGROUND . "-{$scriptClass}",
                'triggerType' => Task::TRIGGER_TYPE_BACKGROUND
            ];
        /** @var Task $task */
        $task = \Yii::createObject(Task::class);
        $task->setAttributes($taskConfig);

        // Run the task
        return \Yii::$app->taskManager->runTask($task);
    }
}
