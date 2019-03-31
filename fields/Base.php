<?php
namespace mozzler\base\fields;

use yii\base\Component;
use yii\helpers\ArrayHelper;

class Base extends Component {
	
	public $type = 'Base';
	public $label;
	public $hint;
	public $config = [];
	public $rules = [];
	public $model;
	public $attribute;
	public $filterType = "=";
	public $default = null;
	public $required = false;
	public $widgets = [];
	public $hidden = false;
	
	/**
	 * Should this field be saved to the database?
	 */
	public $save = true;
	
	//public $format = 'text';	// see i18n/Formatter
	//public $options;
	//public $filter;
	//public $help;
	//public $multiple;
	//public $readOnly;
	
	public function init() {
		parent::init();


		// set default input / view widgets based on this field type
		$this->widgets['input'] = ArrayHelper::merge([
			'class' => 'mozzler\base\widgets\model\input\\'.$this->type.'Field',
			'config' => []
		], isset($this->widgets['input']) && is_array($this->widgets['input'])? $this->widgets['input'] : []);

		if ($this->hidden) {
			$this->widgets['input']['class'] = 'mozzler\base\widgets\model\input\\HiddenField';
		}
		
		$this->widgets['view'] = ArrayHelper::merge([
			'class' => 'mozzler\base\widgets\model\view\\'.$this->type.'Field',
			'config' => []
		], isset($this->widgets['view']) && is_array($this->widgets['view']) ? $this->widgets['view'] : []);

		$this->widgets['filter'] = ArrayHelper::merge([
			'class' => 'mozzler\base\widgets\model\filter\\'.$this->type.'Field',
			'config' => []
		], isset($this->widgets['filter']) && is_array($this->widgets['filter']) ? $this->widgets['filter'] : []);

//        \Yii::debug("Base Field: widgets after merging is: ". var_export($this->widgets, true));
	}
	
	/**
	 * format: [validator, parameter => value]
	 */
	public function rules() {
		$rules = ArrayHelper::merge($this->defaultRules(), $this->rules);
		
		if ($this->required && !isset($customRules['required'])) {
			$rules['required'] = ['message' => $this->label.' cannot be blank'];
			
			// required may be an array of scenarios where it is required
			if (is_array($this->required)) {
				$rules['required']['on'] = $this->required;
			}
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
	
	/**
     * Helper method that generates a query filter based on the `$operator`
     * property of this field
     */
	public function generateFilter($model, $attribute) {
    	switch ($this->operator) {
        	case '=':
        	    return [$attribute => $model->$attribute];
        	    break;
        	case 'LIKE':
        	    return [$attribute => ['like' => $model->$attribute]];
        	    break;
    	}
	}
	
}