<?php

namespace mozzler\base\tests;

use Codeception\Util\Debug;
use mozzler\base\components\IndexManager;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\Model;
use Yii;
use mozzler\base\components\Tools;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\mongodb\Connection;

/**
 * vendor/bin/codecept run -vv tests/unit/base/FixtureTest.php
 *
 * Note as of 2021-11-23rd:
 * - Breaking Change: This now bulkInserts into the database by default, instead of saving each model and triggering the behaviours and validation rules. This can be reverted by changing $_fixturesBatchInsert to false
 * - Can set $_fixturesDropAllCollections to true if you want ALL the collections dropped (or if false their contents are removed before each run)
 * - Can set $_fixturesCreateIndexesAfterEmptyDatabase to create indexes for ALL the models it can find. Might be useful if you want to programmatically add fixtures per test
 * - By default with $_fixturesCreateIndexesBeforeFixtureCreation set to true indexes will be created for each class/collection you have set data for
 */
class FixtureTest extends \Codeception\Test\Unit
{

    // -- Fixture Config
    //   You can adjust how the fixtures are created - Batch inserted or individually saved
    //   and how the collections are emptied, dropped or left alone - Dropping ALL of them or clearing just the ones with fixture data
    //   and also where or if the indexes are created - You can create indexes for all found models or just the ones you have fixtures for
    // Thus the configuration allows for a variety of scenarios, from minimal changes to the database to a full re-creation, depending on your needs
    public $_fixturesBatchInsert = true; // If false then it runs model save on each entry, which also triggers the behaviours, validators, etc..
    public $_fixturesDropAllCollections = false; // If true then it runs drop() on all the collections, if false then it only runs remove and clears out the existing collections
    public $_fixturesEmptyCollectionBeforeInserting = true; // If true then emptyDatabase is called before inserting the fixtures, best set to true when $_fixturesDropAll is false, so you clear the collection before inserting new fixture data
    public $_fixturesCreateIndexesAfterEmptyDatabase = false; // If true then we use the indexManager to try and create the database indexes for every model we can find. Likely creating a lot of collections not needed
    public $_fixturesCreateIndexesBeforeFixtureCreation = true; // If true then we use the indexManager to create the database index for each collection that has fixture data. If this is true then $this->_fixturesCreateIndexesAfterEmptyDatabase is best set to false, meaning you'll only create indexes as needed. If both are false no extra indexes are created beyond MongoDB's default _id, which you might want if you want to create conflicting entries with unique fields or just don't want indexing in the way of testing


    /**
     * Associative array of fixtures to load, keyed by class name
     * with the value a list of database rows. ie:
     *
     * [
     *   'app\models\User' [
     *    [
     *      '_id' => new ObjectId('619c85a86d7783eb77fd7ae2'),
     *      'name' => 'Chris',
     *      'mobile' => '0421000123',
     *      'createdAt' => time(),
     *    ],
     *    [
     *      '_id' => new ObjectId('619c85b06d7783eb77fd7ae3'),
     *      'name' => 'Steve',
     *      'mobile' => '0421000456',
     *      'createdAt' => 1637647849,
     *      'updatedAt' => 1637648888,
     *    ]
     * ]]
     *
     */
    protected $fixtures = [];

    /**
     *
     *
     * Associative array of fixture files to load, keyed by class name
     * with value the filename. e.g:
     * Each file should return an array of rows to insert
     *
     * [
     *    'app\models\User' => codeception_data_dir() . 'users.php'
     * ]
     *
     * If it contains numerically indexed file names then
     * those files are expected to contain multiple different classes and already be in the className format as per $this->fixtures
     * [
     *    __DIR__ . '/../../fixtures/basic_fixtures.php',
     *    __DIR__ . '/../../fixtures/added_extras.php',
     * ]
     *
     */
    protected $fixtureFiles = [];

    public function _fixtures()
    {
        // ==============================================================
        //   Load the Fixtures (this makes a lot of debugging noise)
        // ==============================================================
        $this->emptyDatabase();
        $this->fixtures = $this->getFixtures();
        $this->loadFixtureFiles($this->fixtureFiles); // Must be called before loadFixtures()
        $this->loadFixtures($this->fixtures);
        unset($this->fixtures); // Reduce memory usage
//        parent::_fixtures();
    }

    public function emptyDatabase($dropAll = null)
    {
        if (is_null($dropAll)) {
            $dropAll = $this->_fixturesDropAllCollections; // Likely defaults to false so you remove (clear) the database entries instead of fully removing everything
        }
        \Yii::$app->rbac->forceAdmin = true;

        // -- Clear all the collections first
        /** @var Connection $mongodb */
        $mongodb = \Yii::$app->get('mongodb');
        $database = $mongodb->getDatabase();
        $collectionsList = $database->listCollections();
//        Debug::debug("Collections list is: " . VarDumper::export($collectionsList));
        $originalIgnoredCollections = \Yii::$app->rbac->ignoredCollections;
        foreach ($collectionsList as $collectionInfo) {
            try {
                \Yii::$app->rbac->ignoredCollections[] = $collectionInfo['name']; // Added here so we
//                Debug::debug($dropAll === true ? 'Dropping' : 'Clearing' . " Collection {$collectionInfo['name']}");
                $collection = $database->getCollection($collectionInfo['name']);
                if ($dropAll) {
                    $collection->drop();
                } else {
                    $collection->remove();
                }
            } catch (\Throwable $exception) {
                \Yii::warning(\Yii::$app->t::returnExceptionAsString($exception));
            }
        }


        if ($this->_fixturesCreateIndexesAfterEmptyDatabase) {

            // -- Try to create the Database Indexes for ALL the entries... Except that's probably a waste for fixtures?
            /** @var IndexManager $indexManager */
            if (\Yii::$app->has('indexManager')) {
                $indexManager = \Yii::$app->indexManager;
            } else {
                $indexManager = \Yii::createObject(IndexManager::class);
            }
            // find all the models
            $models = $indexManager->buildModelClassList();
            foreach ($models as $className) {
                $indexManager->syncModelIndexes($className);
            }
        }


        \Yii::$app->rbac->ignoredCollections = $originalIgnoredCollections;
        /* Example $collections =  [
            [
              'name' => 'app.user',
              'type' => 'collection',
              'options' => [],
              'info' => [
                  'readOnly' => false,
                  'uuid' => unserialize('C:19:"MongoDB\\BSON\\Binary":56:{a:2:{s:4:"data";s:16:" ";s:4:"type";i:4;}}'),
              ],
              'idIndex' => [
                  'v' => 2,
                  'key' => [
                      '_id' => 1,
                  ],
                  'name' => '_id_',
                  'ns' => 'test.app.user',
              ],
            ],
            ...
          ];
        */
    }

    /**
     * @return array
     *
     * This is an empty example and meant to be filled
     *
     * Example usage:
     *
     *  return [
     *   'app\models\Account' => [
     *     [
     *        '_id' => $this->accountId,
     *        'name' => 'UNIT TESTING Account',
     *        'billingPlan' => 'full',
     *        'createdAt' => 1620208147,
     *        'updatedAt' => 1636909180,
     *        'createdUserId' => new ObjectId('5de5c889c5ff1f54717b4ad2'), // Use new ObjectId() without a string if you just want any random entry created
     *        'updatedUserId' => new ObjectId('5de5c889c5ff1f54717b4ad2'),
     *     ],
     *    // ... Add more Account entries here
     *   ],
     * //  Add more class based model arrays here
     * ];
     */
    public function getFixtures()
    {
        return [];
    }

    /**
     * @param $fixtureFiles
     * Expects the files to be returning with the classname e.g
     * <\?php
     * use app\models\Car;
     * use MongoDB\BSON\ObjectId;
     * return [
     *  Car::class => [
     *     [ '_id' => new ObjectId('5e007631c5ff1f07ed6976e1'), 'name' => 'Ford Fairlane', 'createdAt' => time(), 'updatedAt' => time()],
     *   ]
     * ];
     */
    protected function loadFixtureFiles($fixtureFiles)
    {
        if (empty($fixtureFiles)) {
            $fixtureFiles = $this->fixtureFiles;
        }
        $fixtures = [];
        foreach ($fixtureFiles as $className => $filePath) {

            $realpath = realpath($filePath);
            if (empty($realpath) || !is_readable($realpath)) {
                throw new BaseException("Unable to load Fixture File " . VarDumper::export(['className' => $className, 'filePath' => $filePath, 'realpath' => $realpath]), 500, null, ['className' => $className, 'filePath' => $filePath, 'realpath' => $realpath]);
            }
            Debug::debug("Loading Fixtures from file '{$realpath}'");
            $fileData = include $realpath;
            if (is_numeric($className)) {
                // The file can contain multiple different classes
                $fixtures = ArrayHelper::merge($fixtures, $fileData);
            } else {
                // The file contains a single class, but there could already be fixture data loaded
                $fixtures[$className] = ArrayHelper::merge($fixtures[$className] ?? [], $fileData);
            }
        }
        // It's assumed that $this->loadFixtures($this->fixtures);  will be called after this
        $this->fixtures = ArrayHelper::merge($this->fixtures, $fixtures);
    }

    protected function loadFixtures($fixtures)
    {
        \Yii::$app->rbac->forceAdmin = true;

        if (true == $this->_fixturesCreateIndexesBeforeFixtureCreation) {
            /** @var IndexManager $indexManager */
            if (\Yii::$app->has('indexManager')) {
                $indexManager = \Yii::$app->indexManager;
            } else {
                $indexManager = \Yii::createObject(IndexManager::class);
            }
        }
        foreach ($fixtures as $fixtureClassName => $fixtureData) {
            /** @var Model $model */
            $model = \Yii::$app->t->createModel($fixtureClassName, []);

            if (true == $this->_fixturesCreateIndexesBeforeFixtureCreation) {
                $indexManager->syncModelIndexes($fixtureClassName);
            }

            if (true === $this->_fixturesBatchInsert) {
                // -- Batch Insert
                $collection = $model->getCollection();

                if (true === $this->_fixturesEmptyCollectionBeforeInserting) {
                    $collection->remove();
                }

                $collection->batchInsert($fixtureData);

            } else {
                // -- Save each entry
                foreach ($fixtureData as $modelData) {
                    $model = \Yii::$app->t::createModel($fixtureClassName, $modelData);
                    $model->scenario = 'default';
                    if (!$model->save()) {
                        throw new \Exception('Unable to save fixture ' . $fixtureClassName . ': ' . json_encode($model->getErrors()));
                    }
                }
            }
        }
        \Yii::$app->rbac->forceAdmin = false;
    }

    protected function _before()
    {
        Debug::debug("\n\n\n\n--------=== Start Test ===--------\n");
    }

    protected function _after()
    {
        Debug::debug("\n=======--- End Test ---=======\n\n");
    }


}
