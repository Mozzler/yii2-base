<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use yii\helpers\ArrayHelper;

class ModelViewAction extends BaseModelAction
{
	public $id = 'view';
    
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_VIEW;
    
    public function defaultConfig() {
	    return ArrayHelper::merge(parent::defaultConfig(),[
		    "containerClass" => "col-md-12",
		    "headerConfig" => [
		        "leftButtons" => [
		            "back" => [
		                "href" =>"{{ widget.model.getUrl(\"list\") }}",
		                "label" =>"&laquo Back"
		            ]
		        ],
		        "leftButtonsOrder" =>"back",
		        "leftButtonsConfig" => [],
		        "rightButtons" => [
		            "update" => [
		                "href" =>"{{ widget.model.getUrl(\"update\") }}",
		                "label" =>"<span class=\"glyphicon glyphicon-pencil\"></span>"
		            ],
		            "delete" => [
		                "href" =>"{{ widget.model.getUrl(\"delete\") }}",
		                "label" =>"<span class=\"glyphicon glyphicon-trash\"></span>",
		                "options" => [
		                    "data-confirm" =>"Are you sure you want to delete this {{ widget.model.getConfig(\"label\") }}?"
		                ]
		            ]
		        ],
		        "rightButtonsOrder" =>"update,delete",
		        "rightButtonsConfig" => []
		    ],
		    "viewConfig" => [
		        "action" =>"view",
		        "widgetConfig" => [
		            "panel" => [
		                "heading" =>"{{ widget.model.getConfig(\"label\") }}"
		            ]
		        ],
		        "fieldsConfig" => []
		    ],
		    "relatePanelsConfig" => [
		        "header" => [
		            "actions" => [
		                "buttonDefaults" => [
		                    "options" => [
		                        "class" =>"btn btn-primary"
		                    ]
		                ],
		                "buttons" => [
		                    "create" => [
		                        "content" =>"<span class=\"glyphicon glyphicon-plus\"></span> {{ widget.relatedModel.getConfig(\"label\") }}"
		                    ]
		                ]
		            ]
		        ]
		    ]
	    ]);
    }

    /**
     */
    public function run()
    {
		$id = \Yii::$app->request->get('id');
		\Yii::trace('calling find model');
	    $model = $this->findModel($id);
	    
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->getUniqueId(), $model);
        }
        
        if ($model) {
	        $model->setScenario($this->scenario);
	        $this->controller->data['model'] = $model;
        }
        
        $this->controller->data['config'] = $this->config();

        return parent::run();
    }
}