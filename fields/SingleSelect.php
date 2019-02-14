<?php
namespace mozzler\base\fields;

class SingleSelect extends Base {
	
	public $type = 'SingleSelect';
	public $operator = "=";
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
		if (!is_array($options)) {
			$options = [$options];
		}

		\Yii::trace(print_r($options,true));
		\Yii::trace(print_r($this->options,true));

		$result = [];
		foreach ($options as $option) {
			if (isset($this->options[$option])) {
				$result[] = $this->options[$option];
			} else {
				\Yii::warning('Invalid select option specified ('.$option.' for field '.$this->name);
			}
		}

		\Yii::trace(print_r($result,true));
		
		return $result;
	}
	
}

?>
