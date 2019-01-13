<?php
namespace mozzler\base\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\web\View as WebView;

use mozzler\base\helpers\WidgetHelper;

class BaseWidget extends Widget {
	
	public $viewName = null;
	public $dirName = null;
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
		
		$config['id'] = $this->id;
		
		return $config;
	}
	
	public function init() {
		parent::init();
		
		if (!$this->viewName) {
			$class = new \ReflectionClass($this);
			$pathInfo = pathinfo($class->getFileName());
			$this->viewName = $pathInfo['filename'];
			$this->dirName = $pathInfo['dirname'];
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
		$this->outputCss();
		return $this->render($this->viewName, [
			'widget' => $config
		]);
	}
	
	public function getViewPath()
    {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName());
    }
    
    // take an object and output it to Javascript for this widget
    public function outputJsData($jsData) {
	    $view = \Yii::$app->controller->getView();
	    $view->registerJs(" m.widgets['".$this->id."'] = ".json_encode($jsData)."; ", WebView::POS_HEAD);
    }
    
    /**
	 * This is a quick and dirty way to dynamically include a CSS file linked to a widget.
	 * If the same widget is included twice in a page this CSS will be included twice (not good).
	 * Need to refactor this to use SCSS and conver the SCSS into CSS using a core theme CSS files
	 */
    public function outputCss()
    {
	    $view = \Yii::$app->controller->getView();
	    $cssFile = $this->dirName . DIRECTORY_SEPARATOR . $this->viewName.'.css';
	    
	    if (file_exists($cssFile))
	    {
		    $view->registerCss(file_get_contents($cssFile));
	    }
    }
	
}

?>