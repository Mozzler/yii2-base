<?php
namespace mozzler\base\widgets\model\subpanel;

use mozzler\base\widgets\BaseWidget;

class SubPanels extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'widget-model-subpanels-subpanels'
            ],
            'scenario' => 'subpanel,list',
            'panelConfig' => [
                'options' => [
                    'widget-model-subpanels-subpanels-panel col-md-12'
				],
				'applyRbacToActionColumn' => true
            ],
            'relateConfigs' => [],
			'model' => null,
		];
    }
    
}