<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;

class UpdateModel extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'widget-model-update'
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
	public function code($templatify = false) {
		$config = $this->config();
		$t = new \mozzler\base\components\Tools;

		if (empty($config['model'])) {
		    \Yii::error("No model specified");
        }
		$config['attributes'] = $config['model']->activeAttributes();
		$config['items'] = [];
		$config['hiddenItems'] = [];
		$hasFileUpload = false;
		
		foreach ($config['attributes'] as $attribute) {
			$modelField = $config['model']->getModelField($attribute);
			if (!$modelField) {
				\Yii::warning("Non-existent attribute ($attribute) specified in scenario ".$config['model']->scenario." on ".$config['model']->className());
				continue;
			}
			
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
		
		return $config;
	}
	
}

?>