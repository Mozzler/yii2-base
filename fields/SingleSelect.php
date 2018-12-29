<?php
namespace mozzler\base\fields;

class SingleSelect extends Base {
	
	public $type = 'SingleSelect';
	public $operator = "~";
	public $options = [];
	
	public function defaultRules() {
		return [
			'string' => [
				'max' => 255
			]
		];
	}
	
	/**
	 * Take an array of option keys and return the values
	 */
	public function getOptionLabels($options=[]) {
		$result = [];
		foreach ($options as $option) {
			if (isset($this->options[$option])) {
				$result[] = $this->options[$option];
			} else {
				\Yii::warning('Invalid select option specified ('.$option.' for field '.$this->name);
			}
		}
		
		return $result;
	}
	
}

?>
