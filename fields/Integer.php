<?php
namespace mozzler\base\fields;

class Integer extends Base {
	
	public $type = 'Integer';

	public function init() {
		parent::init();

		// work around for default value of 0 appearing as empty
		// when populating a form
		if (is_numeric($this->default)) {
			$this->default = intval(strval($this->default));
		}
	}
	
	public function defaultRules() {
		return [
			'integer' => []
		];
	}
	
	// force integer value
	public function setValue($value) {
		if (isset($value))
			return intval($value);
	}
	
	public function applySearchRules($searchModel) {
		$searchModel->addRule($this->attribute, 'trim');
		$searchModel->addRule($this->attribute, 'integer');
	}
	
}

?>
