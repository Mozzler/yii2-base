<?php
namespace mozzler\base\fields;

class Base extends \yii\base\Component {
	
	public $type;
	public $options;
	public $label;
	public $hint;
	public $filter;
	public $help;
	public $config;
	public $format = 'text';	// see i18n/Formatter
	public $model;
	public $attribute;
	public $multiple;
	public $operator = "=";
	public $readOnly;
	public $_field;
	
	/**
	 * format: [validator, parameter => value]
	 */
	public function rules($config=[]) {
		return self::mergeConfig($this->defaultRules(), $config);
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