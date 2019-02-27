<?php

namespace mozzler\base\components;

use mozzler\base\models\Task;
use yii\base\Exception;
use \yii\helpers\ArrayHelper;
use \yii\base\Component;

/**
 * To use the Deploy manager, add it to the `console.php` or `common.php` with the required config.
 *
 * e.g
 *
 * ```
 * "components" => [
 * "deployManager" => [
 * "class" => "mozzler\base\components\DeployManager",
 * "versionParam" => "apiVersionNumber",
 * "init" => [
 * "indexes" => [
 * "command" => "deploy/sync",
 * "params" => []
 * ],
 * "adminUser" => [
 * "command" => "auth/init-credentials",
 * "params" => []
 * ],
 * "config" => [
 * "command" => "config/init",
 * "params" => []
 * ]
 * ],
 * "redeploy" => [
 * "indexes" => [
 * "command" => "deploy/sync",
 * "params" => []
 * ],
 * "config" => [
 * "command" => "config/init",
 * "params" => []
 * ],
 * "clearData" => [
 * "command" => "deploy/drop-collections",
 * "config" => ["app.favourites", "mozzler.auth.user"],
 * "version" => "0.1.1"
 * ]
 * ] ],
 * ]
 * ```
 */
class DeployManager extends Component
{
    public $init = [];
    public $redeploy = [];
    public $versionParam = "apiVersionNumber"; // e.g \Yii::$app->params['apiVersionNumber'] will be used for the version number


    /**
     * @param $commandSet string e.g 'init' or 'redeploy', which should correspond with the appropriate configuration
     * @return array
     * @throws \yii\base\Exception
     *
     * e.g $command
     */
    public function run($commandSet)
    {
        if (empty($commandSet)) {
            throw new Exception("The DeployManager run() command expects 'init' or 'redeploy' as commands, none provided");
        }

        $config = $this->$commandSet;
        $currentVersion = \Yii::$app->params[$this->versionParam];

        $stats = [
            'Command' => $commandSet,
            'TimeRun' => time(),
            'TimeRun Human Readable' => date('r'),
            'Current Version' => 'v' . $currentVersion,

            'Entries' => count($config),
            'Entries Run' => 0,
            'Entries Skipped' => 0,
            'Scripts Run' => [],

//            'Config' => $config, // Not needed to be output as the command already shows this before the confirmation step

            'Errors Count' => 0,
            'Errors' => [],
            'Log' => [],
        ];

        if (empty($config)) {
            $stats['Errors Count']++;
            $stats['Errors'][] = "Invalid Command or empty configuration, nothing to process";
            return $stats;
        }


        foreach ($config as $entryName => $entry) {

            // -- Validity check
            if (empty($entry) || (!isset($entry['command']) && !isset($entry['script']))) {
                $stats['Errors Count']++;
                $stats['Errors'][] = "Entry {$entryName} : The entry is empty, need a command or script. " . json_encode($entry);
                continue;
            }

            // -- Version Check
            if (isset($entry['version']) && $entry['version'] !== $currentVersion) {
                $stats['Entries Skipped']++;
                $stats['Log'][] = "Entry {$entryName} - Ignoring the entry as expected version: {$entry['version']} !== current version: {$currentVersion}";
                continue;
            }


            // Determine if running in Windows or *nix ( as per http://thisinterestsme.com/php-detect-operating-system-windows/ ) WINNT : Linux
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'; // Or could use > $isWindows = defined('PHP_WINDOWS_VERSION_MAJOR');
            $filePath = \Yii::getAlias('@app') . DIRECTORY_SEPARATOR; // e.g D:\www\bapp.viterra.com.au\

            $command = '';
            $params = isset($entry['params']) ? $entry['params'] : [];
            $paramShellArgs = '';
            if (!empty($params)) {
                foreach ($params as $paramIndex => $param) {
                    $paramShellArgs .= " " . escapeshellarg($param);
                }
            }
            $yiiFile = "{$filePath}yii" . (true === $isWindows ? '.bat' : '');
            // ---------------------------------------------------
            //   Yii Command
            // ---------------------------------------------------
            if (!empty($entry['command'])) {

                $command = escapeshellarg($yiiFile) . " " . escapeshellarg($entry['command']) . " " . $paramShellArgs;
            }

            // ---------------------------------------------------
            //   Script
            // ---------------------------------------------------
            if (!empty($entry['script'])) {
                // @todo: Get the script/run command to work
                $command = escapeshellarg($yiiFile) . " " . escapeshellarg("script/run") . " " . escapeshellarg($entry['script']) . " " . $paramShellArgs;
            }

            // ---------------------------------------------------
            //   General Terminal Command
            // ---------------------------------------------------
            // With great power comes great responsibility
            if (!empty($entry['windowsCommand']) && $isWindows) {
                $command = escapeshellarg($entry['windowsCommand']) . " {$paramShellArgs}";

            }
            if (!empty($entry['linuxCommand']) && !$isWindows) {
                $command = escapeshellarg($entry['linuxCommand']) . " {$paramShellArgs}";

            }

            $outputArray = [];
            $returnVar = null;
            // -------------------
            //  Run Serially
            // -------------------
            if ($isWindows) {
                $runCommand = $command;

                $stats['Log'][] = "Entry {$entryName} - Is on Windows and running the command: {$runCommand}";
                session_write_close(); // Getting around the possible concurrency issue described in http://de2.php.net/manual/en/function.exec.php#99781
                exec($runCommand, $outputArray, $returnVar);
                session_start();
//                pclose(popen($runCommand, "r")); // The async way of running it
            } else {
                $runCommand = "{$command} 2>&1";
                $stats['Log'][] = "Entry {$entryName} - Is on *nix and running the command: {$runCommand}";
                exec($runCommand, $outputArray, $returnVar);
            }
            $stats['Scripts Run'][] = $runCommand;

            $output = '';
            foreach ($outputArray as $outputLineNumber => $outputLine) {
                $output .= $outputLine . "\n";
            }
            $stats['Log'][] = "Entry {$entryName} Completed with the return: " . json_encode($returnVar) . " and the output\n------------------ {$entryName} ----------\n" . $output . "\n";

        }

        return $stats;
    }

}