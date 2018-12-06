<?php
namespace mozzler\base\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use mozzler\base\helpers\WidgetHelper;

class BaseWidget extends Widget {
	
	protected $viewName = null;
	public $config = [];
	
	public function defaultConfig()
	{
		return [
			'widgetConfig' => []
		];
	}
	
	public function config($templatify=false) {
		$config = ArrayHelper::merge($this->defaultConfig(), $this->config);
		
		if ($templatify) {
			$config = WidgetHelper::templatifyConfig($config, ['widget' => $config]);
		}
		
		return $config;
	}
	
	public function init() {
		parent::init();
		
		if (!$this->viewName) {
			$class = new \ReflectionClass($this);
			$pathInfo = pathinfo($class->getFileName());
			$this->viewName = $pathInfo['filename'];
		}
	}
	
	public function run() {
		$config = $this->code();
		return $this->html($config);
	}
	
	// take $config and process it to generate final config
	public function code() {
		return $this->config();
	}
	
	public function html($config=[]) {
		return $this->render($this->viewName, [
			'widget' => $config
		]);
	}
	
	public function getViewPath()
    {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName());
    }
	
}

?>