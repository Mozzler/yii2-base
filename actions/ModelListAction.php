<?php
namespace mozzler\base\actions;

use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

use mozzler\base\models\Model;
use yii\helpers\Html;

class ModelListAction extends BaseModelAction
{
	public $id = 'list';
    
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_LIST;
    
    public function defaultConfig() {
	    return ArrayHelper::merge(parent::defaultConfig(),[
		    'gridViewConfig' => [
			    'columns' => [
				    ['class' => '\kartik\grid\ActionColumn']
			    ]
		    ],
	    ]);
    }

    /**
     */
    public function run()
    {
	    if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }
        
        $model = $this->controller->data['model'];
        $model->setScenario($this->scenario);
        
        // build data provider
		$dataProvider = new ActiveDataProvider([
			'query' => $model::find()
		]);
		
		$base = \Yii::$app->getModule('mozzlerBase');
		$fieldGridConfig = $base->fieldGridConfig;
		$attributes = $model->activeAttributes();
		$columns = [];
		foreach ($attributes as $attribute) {
			$field = $model->getModelField($attribute);
			$customFieldConfig = [];
			$columns[] = $fieldGridConfig->getFieldConfig($field, $customFieldConfig);
		}
		
		$config = ArrayHelper::merge([
			'gridViewConfig' => [
				'dataProvider' => $dataProvider,
				'columns' => $columns
			]
		], $this->config());
        
        $this->controller->data['config'] = $config;
        $this->controller->data['model'] = $model;
        
        return parent::run();
    }
}