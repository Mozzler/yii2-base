<?php
namespace mozzler\base\fields;

class Double extends Base {
	
	public $type = 'Double';

	public function init() {
		parent::init();

		// work around for default value of 0 appearing as empty
		// when populating a form
		if (is_numeric($this->default)) {
			$this->default = strval($this->default);
		}
	}
	
	public function defaultRules() {
		return [
			'double' => []
		];
	}
	
	// force double value
	public function setValue($value) {
		if (isset($value))
			return doubleval($value);
	}
	
	public function applySearchRules($searchModel) {
		$searchModel->addRule($this->attribute, 'trim');
		$searchModel->addRule($this->attribute, 'double');
	}
	
}
