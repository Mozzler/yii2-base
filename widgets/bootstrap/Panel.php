<?php
namespace mozzler\base\widgets\bootstrap;

use mozzler\base\widgets\BaseWidget;

class Panel extends BaseWidget {
	
	/**
     * Bootstrap Contextual Color Types
     */
    const TYPE_DEFAULT = 'default'; // use default
    const TYPE_PRIMARY = 'primary';
    const TYPE_INFO = 'info';
    const TYPE_DANGER = 'danger';
    const TYPE_WARNING = 'warning';
    const TYPE_SUCCESS = 'success';
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'panel'
			],
			'style' => self::TYPE_DEFAULT,
			'heading' => [
				'tag' => 'div',
				'options' => [
					'class' => 'panel-heading'
				],
				'title' => [
					'tag' => 'div',
					'options' => [
						'class' => 'panel-title'
					],
					'content' => ''
				],
				'content' => ''
			],
			'body' => [
				'tag' => 'div',
				'options' => [
					'class' => 'panel-body'
				],
				'content' => ''
			],
			'footer' => [
				'tag' => 'div',
				'options' => [
					'class' => 'panel-footer'
				],
				'content' => ''
			]
		];
	}
	
	// take $config and process it to generate final config
	public function code($templatify = false) {
		$config = $this->config();
		
		if (isset($config['style'])) {
			$config['options']['class'] .= ' panel-' . $config['style'];
		}
				
		return $config;
	}
	
}

?>