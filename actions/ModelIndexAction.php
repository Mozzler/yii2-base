<?php
namespace mozzler\base\actions;

use yii\helpers\ArrayHelper;

use mozzler\base\models\Model;
use yii\helpers\Html;
use mozzler\base\helpers\WidgetHelper;

class ModelIndexAction extends BaseModelAction
{
	public $id = 'index';
    
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_LIST;
    
    public function defaultConfig() {
	    return ArrayHelper::merge(parent::defaultConfig(),[
		    'gridViewConfig' => [
			    'columns' => [
				    ['class' => '\kartik\grid\ActionColumn']
			    ],
			    'panel' => [
				    'before' => '{{ t.renderWidget("mozzler.base.widgets.model.FilterModel", {"model": widget.model}) }}',
				    'heading' => '{{ widget.model.getModelConfig("labelPlural") }}'
			    ],
			    'toolbar' => [
				    '{filterButton}',
				    //'exportButton',
			    ],
			    'replaceTags' => [
				    '{filterButton}' => '<a href="" class="btn btn-sm btn-default">Filter</a>',
			    ]
		    ],
	    ]);
    }

    /**
     */
    public function run()
    {   
        $model = $this->controller->data['model'];
        $model->setScenario($this->scenario);
        
        $dataProvider = $model->search(\Yii::$app->request->get());
		$columns = $this->buildColumns($model);
		
		$config = ArrayHelper::merge([
			'gridViewConfig' => [
				'dataProvider' => $dataProvider,
				'columns' => $columns
			]
		], $this->config());
		
		$config['model'] = $model;
		$config = WidgetHelper::templatifyConfig($config, ['widget' => $config]);
        
        $this->controller->data['config'] = $config;
        $this->controller->data['model'] = $model;
        
        return parent::run();
    }
    
    protected function buildColumns($model) {
	    $base = \Yii::$app->getModule('mozzlerBase');
		$fieldGridConfig = $base->fieldGridConfig;
		
	    $attributes = $model->activeAttributes();
		$columns = [];
		foreach ($attributes as $attribute) {
			$field = $model->getModelField($attribute);
			$customFieldConfig = [];
			$columns[] = $fieldGridConfig->getFieldConfig($field, $customFieldConfig);
		}
		
		return $columns;
    }
}