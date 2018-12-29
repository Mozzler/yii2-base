<?php
namespace mozzler\base\fields;

use yii\helpers\ArrayHelper;

class MultiSelect extends SingleSelect {
	
	public $type = 'MultiSelect';
	
	public function defaultRules() {
		return ArrayHelper::merge(parent::defaultRules(),[
			'string' => new \yii\helpers\UnsetArrayValue()
		]);
		
	}
	
}

?>
