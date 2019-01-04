<?php
namespace mozzler\base\widgets\model;

use mozzler\base\widgets\BaseWidget;

class FilterModel extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'row widget-model-filter'
			],
			'container' => [
				'tag' => 'div',
				'options' => [
					'class' => 'col-md-12'
				]
			],
			'form' => [
				'options' => []
			],
			'filterModel' => null
		];
	}
	
	// take $config and process it to generate final config
	/*public function code() {
		$config = $this->config(true);
		
		return $config;
	}*/
	
}

?>