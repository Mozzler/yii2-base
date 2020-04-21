<?php
namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;

class MultiSelectField extends SingleSelectField
{
	
	public function defaultConfig()
	{
		return ArrayHelper::merge(parent::defaultConfig(), [
			'widgetConfig' => [
				'options' => ['placeholder' => 'Select {{ widget.model.getModelField(widget.attribute).label }} ...'],
				'pluginOptions' => [
					'multiple' => true,
					'allowClear' => true
				]
			]
		]);
	}
	
}

