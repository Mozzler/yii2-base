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
				    'before' => '{{ t.renderWidget("mozzler.base.widgets.model.FilterModel", {"filterModel": widget.filterModel}) }}',
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
        $indexModel = $this->controller->data['model'];
        $indexModel->setScenario($this->scenario);
        
        $searchModel = $indexModel->generateSearchModel();
        $dataProvider = $searchModel->search(\Yii::$app->request->get());
		$columns = $this->buildColumns($indexModel);
		
		$config = ArrayHelper::merge([
			'gridViewConfig' => [
				'dataProvider' => $dataProvider,
				'filterModel' => $searchModel,
				'columns' => $columns
			]
		], $this->config());
		
		$config['model'] = $indexModel;
		$config['filterModel'] = $searchModel;
		$config = WidgetHelper::templatifyConfig($config, ['widget' => $config]);
        
        $this->controller->data['config'] = $config;
        $this->controller->data['model'] = $indexModel;
        
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