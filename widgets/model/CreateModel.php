<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;

class CreateModel extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'widget-model-create'
			],
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
			],
			'formConfig' => [
				'options' => []
			],
			'model' => null
		];
	}
	
	// take $config and process it to generate final config
	public function code() {
		$config = $this->config();
		$t = new \mozzler\base\components\Tools;
		
		$config['attributes'] = $config['model']->activeAttributes();
		$form = $t->load('yii.widgets.ActiveForm');
		$config['items'] = [];
		$config['hiddenItems'] = [];
		$hasFileUpload = false;
		
		foreach ($config['attributes'] as $attribute) {
			$modelField = $config['model']->getModelField($attribute);
			
			if ($modelField->hidden) {
				$config['hiddenItems'][] = $attribute;
			} else {
				$config['items'][] = $attribute;
			}
			
			if ($modelField->type == 'FileUpload') {
				$hasFileUpload = true;
			}
		}
		
		if ($hasFileUpload) {
			$config['formConfig']['options']['enctype'] = 'multipart/form-data';
		}
		
		$config['form'] = $form;
		
		return $config;
	}
	
}

?>