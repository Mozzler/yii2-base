<?php
namespace mozzler\base\components;

use yii\base\Component;

class Tools extends Component {
	
	// widget:
	//  - mozzler.web.
	public function renderWidget($widget, $config=[]) {
		// locate widget
		$widget = \Yii::$widgetManager->getWidget($widget);
		return $widget->exec($config);
	}
	
}