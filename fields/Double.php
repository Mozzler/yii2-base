<?php
namespace mozzler\base\fields;

class Double extends Base {
	
	public $type = 'Double';
	
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

?>
