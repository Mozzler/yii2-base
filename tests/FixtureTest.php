<?php
namespace mozzler\base\tests;

use Yii;
use mozzler\base\components\Tools;

/**
 * vendor/bin/codecept run --debug unit tests/unit/base/RelationsTest.php
 */
class FixtureTest extends \Codeception\Test\Unit
{
    
    protected $fixtures = [];
    
    public function _fixtures()
    {
        // Initialise RBAC for models and force admin to disable RBAC permission checks
        $t = Yii::createObject(Tools::className());
        \Yii::$app->rbac->forceAdmin = true;
        
        $fixtures = $this->fixtures;
        
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
        
        return [];
    }

}
