<?php
namespace mozzler\base\tests;

use Yii;
use mozzler\base\components\Tools;

/**
 * vendor/bin/codecept run --debug unit tests/unit/base/RelationsTest.php
 */
class FixtureTest extends \Codeception\Test\Unit
{
    
    /**
     * Associative array of fixtures to load, keyed by class name
     * with the value a list of database rows. ie:
     *
     * [
     *    [
     *      'name' => 'Chris',
     *      'mobile' => '0421000123',
     *    ],
     *    [
     *      'name' => 'Steve',
     *      'mobile' => '0421000456',
     *    ]
     * ]
     *
     */
    protected $fixtures = [];
    
    /**
     * Associative array of fixture files to load, keyed by class name
     * with value the filename. ie:
     *
     * [
     *    'app\models\User' => codeception_data_dir() . 'users.php'
     * ]
     *
     * Each file should return an array of rows to insert
     */
    protected $fixtureFiles = [];
    
    public function _fixtures()
    {
        // Initialise RBAC for models and force admin to disable RBAC permission checks
        \Yii::$app->rbac->forceAdmin = true;
        
        $this->loadFixtures($this->fixtures);
        $this->loadFixtureFiles($this->fixtureFiles);
        
        \Yii::$app->rbac->forceAdmin = false;
        return [];
    }
    
    protected function loadFixtures($fixtures) {
        $t = Yii::createObject(Tools::className());
        
        foreach ($fixtures as $fixtureClassName => $fixtureData) {            
            // Remove all models in the collection
            $model = $t->createModel($fixtureClassName, []);
            $model->getCollection()->remove();
            
            foreach ($fixtureData as $modelData) {
                $model = $t->createModel($fixtureClassName, $modelData);
                $model->scenario = 'default';
                if (!$model->save()) {
                    throw new \Exception('Unable to save fixture '.$fixtureClassName.': '.json_encode($model->getErrors()));
                }
            }
        }
    }
    
    protected function loadFixtureFiles($fixtureFiles) {
        $fixtures = [];
        
        foreach ($fixtureFiles as $className => $filePath) {
            \Codeception\Util\Debug::debug($filePath);
            $fileData = include $filePath;
            $fixtures[$className] = $fileData;
        }
        
        $this->loadFixtures($fixtures);
    }

}
