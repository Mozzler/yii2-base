<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;

class ViewModel extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'row widget-model-view'
			],
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
			],
			'model' => null,
			'panelConfig' => [
				'heading' => [
					'title' => [
						'content' => '<div class="pull-right"><a href="{{ widget.model.getUrl("update") }}" class="btn btn-success btn-sm">Edit {{ widget.model.getModelConfig(\'label\') }}</a></div>{{ widget.model.getModelConfig("label") }}'
					]
				],
				'body' => [],
				'footer' => false
			]
		];
	}
	
	// take $config and process it to generate final config
	public function code() {
		$config = $this->config(true);
		$model = $config['model'];
		$t = new \mozzler\base\components\Tools;

		$attributes = $model->activeAttributes();
		
		$items = [];
		
		foreach ($attributes as $attribute) {
			$modelField = $model->getModelField($attribute);
			if (!$modelField) {
				\Yii::warning("Non-existent attribute ($attribute) specified in scenario ".$model->scenario." on ".$model->className());
				continue;
			}
			
			if (in_array($modelField->type, ['RelateMany', 'RelateManyMany'])) {
				// Don't render relate many fields in the view
				continue;
			}
			
			$fieldConfig = [
				'model' => $model,
				'attribute' => $attribute
			];
			
			$items[] = $t->renderWidget('mozzler.base.widgets.model.view.RenderField', $fieldConfig);
		}
		
		$config['items'] = $items;
		return $config;
	}
	
}

?>