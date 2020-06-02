<?php
/**
 */

namespace mozzler\base\commands;

use mozzler\base\components\Tools;
use \yii\mongodb\Connection;
use yii\helpers\Console;
use yii\console\ExitCode;

use mozzler\base\helpers\IndexHelper;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DeployController extends BaseController
{
    // Specify the modelPaths to sync indexes (or use null for the default)
    public $modelPaths = null; // or you can specify them, e.g  ['@app/models/', '@mozzler/base/models/'];

    /**
     * @var bool if true then there's no confirmation step
     * e.g the databases are dropped without user confirmation being requested
     */
    public $force = false; // If true it won't request confirmation to drop the collections

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

            $this->stdout('Processing model: ' . $className . "\n", Console::FG_GREEN);

            $indexManager->syncModelIndexes($className);
            $this->outputLogs($indexManager->logs);
        }

        return ExitCode::OK;
    }


    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['force',] // global for all actions
//            , $actionID === 'dropCollections' ? ['templateFile'] : [] // action create
        );
    }

    /**
     * @param string $collections - As CSV list of the DB collection names to drop
     *
     * Example usage:
     * ./yii deploy/drop-collections app.cache,app.config,mozzler.auth.access_tokens
     *
     * You can also specify the force param so you don't need to confirm deletion:
     * ./yii deploy/drop-collections --force=1 app.cache,app.config,mozzler.auth.access_tokens
     */
    public function actionDropCollections($collectionsCSV)
    {

        if (empty($collectionsCSV)) {
            $this->stderr("No collections specified. Please provide a CSV list of the collections you wish to drop.\n" .
                "e.g ./yii deploy/drop-collections app.cache,app.config,mozzler.auth.access_tokens");
            return ExitCode::USAGE;
        }

        $collections = explode(',', $collectionsCSV);
        $totalDocuments = 0;
        $collectionReference = count($collections) > 1 ? "collections" : "collection";

        $this->stdout("You requested to drop the {$collectionReference}:\n\n");
        foreach ($collections as $collectionIndex => $collectionName) {
            try {
                \Yii::$app->rbac->ignoreCollection($collectionName);
                /** @var \yii\mongodb\Collection $databaseCollection */
                $collection = \Yii::$app->mongodb->getCollection($collectionName);
                if (empty($collection)) {
                    $this->stderr("Warning: Invalid collection {$collectionName}");
                    unset($collections[$collectionIndex]);
                } else {
                    $documentsCount = $collection->count();
                    $totalDocuments += $documentsCount;
                    $this->stdout("- {$collectionName} : {$documentsCount} documents\n");
                }
            } catch (\Throwable $exception) {
                $this->stderr("Error: Issue getting collection count for {$collectionName}\n" . \Yii::$app->t::returnExceptionAsString($exception));
                unset($collections[$collectionIndex]); // Assume there's an issue with this collection, most likely it's already been dropped
            }
        }
        $this->stdout("\n");

        if (empty($collections)) {
            $this->stderr("Error: No valid {$collectionReference} to drop");
            return ExitCode::USAGE;
        }


        if (false === boolval($this->force) && $this->confirm("Are you sure you want to delete the " . count($collections) . " {$collectionReference} with {$totalDocuments} total documents ?")) {
            \Yii::debug("User requested to drop the {$collectionReference}");
        } else if (true === boolval($this->force)) {
            $this->stdout("Force = " . json_encode($this->force) . "\n");
        } else {
            $this->stdout("Not dropping the {$collectionReference}\n");
            return ExitCode::NOUSER;
        }

        // -----------------------------------------------
        //   Drop the Collections
        // -----------------------------------------------
        foreach ($collections as $collectionIndex => $collectionName) {
            try {

                /** @var \yii\mongodb\Collection $databaseCollection */
                $collection = \Yii::$app->mongodb->getCollection($collectionName);
                $drop = $collection->drop();
                $this->stdout((true === $drop ? "✓ Dropped" : "✗ Failed to drop") . " collection {$collectionName}\n");
            } catch (\Throwable $exception) {
                $this->stderr("✗ Error: Unable to drop collection {$collectionName}\n" . \Yii::$app->t::returnExceptionAsString($exception));
            }
        }
        return ExitCode::OK;
    }


    public function actionInit()
    {

        if (!isset(\Yii::$app->deployManager)) {
            $this->stderr("The deployManager is not defined, please check your console.php configuration");
        }
        $this->stdout("Init Commands: " . print_r(\Yii::$app->deployManager->init, true) . "\n");
        if (false === boolval($this->force) && $this->confirm("Are you sure you want to run the deploy/init commands?")) {
            \Yii::debug("User requested to run the init commands");
        } else if (true === boolval($this->force)) {
            $this->stdout("Force = " . json_encode($this->force) . "\n");
        } else {
            $this->stdout("Not running the init commands\n");
            return ExitCode::NOUSER;
        }
        $this->stdout("Running the initial deployment commands please wait.\n");

        // -- Run the init
        $stats = \Yii::$app->deployManager->run('init');

        // -- Output
        $this->stdout("Init commands ran with: \n");
        $this->stdout(print_r($stats, true));
    }


    public function actionRedeploy()
    {

        if (!isset(\Yii::$app->deployManager)) {
            $this->stderr("The deployManager is not defined, please check your console.php configuration");
        }


        // -- Auto-set the --force=1 param otherwise the drop-collections waits forever
        foreach (\Yii::$app->deployManager->redeploy as $entryName => $entry) {
            if ('deploy/drop-collections' === $entry['command']
                && is_array($entry['params'])
                && empty(array_filter($entry['params'], function ($var) {
                    // -- Ensure force hasn't already been set
                    return false !== stripos($var, '--force');
                }))
            ) {
                $redeploy = \Yii::$app->deployManager->redeploy;
                $redeploy[$entryName]['params'][] = '--force=1';
                \Yii::$app->deployManager->redeploy = $redeploy;
                $this->stdout("Note: Auto-added '--force=1' to the params for the {$entryName} drop collections command.\n", Console::FG_PURPLE);
            }
        }


        $this->stdout("Redeploy Commands: " . print_r(\Yii::$app->deployManager->redeploy, true) . "\n");
        if (false === boolval($this->force) && $this->confirm("Are you sure you want to run the deploy/redeploy commands?")) {
            \Yii::debug("User requested to run the redeploy commands");
        } else if (true === boolval($this->force)) {
            $this->stdout("Force = " . json_encode($this->force) . "\n");
        } else {
            $this->stdout("Not running the redeploy commands\n");
            return ExitCode::NOUSER;
        }
        $this->stdout("Running the redeploy deployment commands please wait.\n");

        // -- Run the init
        $stats = \Yii::$app->deployManager->run('redeploy');

        // -- Output
        $this->stdout("Redeploy commands ran with: \n");
        $this->stdout(print_r($stats, true));
    }

    protected function outputLogs($logs)
    {
        foreach ($logs as $entry) {
            $this->stdout($entry['message'] . "\n", $entry['type'] == 'error' ? Console::FG_RED : null);
        }
    }

}
