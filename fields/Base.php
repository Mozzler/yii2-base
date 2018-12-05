<?php
namespace mozzler\base\fields;

use yii\base\Component;
use yii\helpers\ArrayHelper;

class Base extends Component {
	
	public $type;
	public $label;
	public $hint;
	public $config = [];
	public $rules = [];
	public $model;
	public $attribute;
	public $operator = "=";
	public $widgets = [
		'input' => 'mozzler\base\widgets\model\input\BaseField',
		'view' => 'mozzler\base\widgets\model\view\BaseField'
	];
	
	//public $format = 'text';	// see i18n/Formatter
	//public $options;
	//public $filter;
	//public $help;
	//public $multiple;
	//public $readOnly;
	
	/**
	 * format: [validator, parameter => value]
	 */
	public function rules() {
		return ArrayHelper::merge($this->defaultRules(), $this->rules);
	}
	
	public function defaultRules() {
		return [];
	}
	
	// get stored value -- convert db value to application value
	public function getValue($value) {
		return $value;
	}
	
	// set stored value -- convert application value to db value
	public function setValue($value) {
		return $value;
	}
	
}