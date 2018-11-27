<?php
namespace mozzler\base\components;

use yii\base\Component;

class WidgetManager extends Component {
	
	private $widgetMap;
	
	public function init() {
		$this->widgetMap = [];
		parent::init();
	}
	
	public function loadModuleWidgets($module, $widgetList) {
		
	}
	
}