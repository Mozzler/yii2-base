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
	public $default = null;
	public $required = false;
	public $widgets = [
		'input' => 'mozzler\base\widgets\model\input\BaseField',
		'view' => 'mozzler\base\widgets\model\view\BaseField'
	];
	public $hidden = false;
	
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
		$rules = ArrayHelper::merge($this->defaultRules(), $this->rules);
		
		if ($this->required && !isset($customRules['required'])) {
			$rules['required'] = ['message' => $this->label.' cannot be blank'];
		}
		
		if ($this->default) {
			$rules['default'] = ['value' => $this->default];
		}
		
		return $rules;
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