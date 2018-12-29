<?php
namespace mozzler\base\controllers;

use yii\rest\ActiveController as BaseActiveController;

use mozzler\base\models\Model;

use yii\helpers\ArrayHelper;
use yii\filters\auth\QueryParamAuth;
use mozzler\auth\yii\oauth\auth\CompositeAuth;
use mozzler\auth\yii\oauth\auth\HttpBearerAuth;

use mozzler\base\yii\oauth\auth\ErrorToExceptionFilter;
//use filsh\yii2\oauth2server\filters\ErrorToExceptionFilter;

class ActiveController extends BaseActiveController
{
	
	// custom serializer to support scenario based responses
	public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items'
    ];
	
	/**
     * @var string the scenario used for updating a model.
     * @see \yii\base\Model::scenarios()
     */
    public $updateScenario = Model::SCENARIO_UPDATE;
    
    /**
     * @var string the scenario used for creating a model.
     * @see \yii\base\Model::scenarios()
     */
    public $createScenario = Model::SCENARIO_CREATE;
    
    /**
     * @var string the scenario used for viewing a model.
     * @see \yii\base\Model::scenarios()
     */
    public $viewScenario = Model::SCENARIO_VIEW;
    
    /**
     * @var string the scenario used for listng models.
     * @see \yii\base\Model::scenarios()
     */
    public $listScenario = Model::SCENARIO_VIEW;

    public function actions()
    {
        return [
            'index' => [
                'class' => 'mozzler\base\actions\ActiveIndexAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->listScenario
            ],
            'view' => [
                'class' => 'mozzler\base\actions\ActiveViewAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->viewScenario
            ],
            'create' => [
                'class' => 'mozzler\base\actions\ActiveCreateAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->createScenario
            ],
            'update' => [
                'class' => 'mozzler\base\actions\ActiveUpdateAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->updateScenario,
                'viewScenario' => $this->viewScenario
            ]
        ];
    }
    
    public static function rbac() {
		return [
			'public' => [
				'create' => [
		            'grant' => false
		        ],
		        'view' => [
		            'grant' => false
		        ],
		        'update' => [
		            'grant' => false
		        ],
		        'index' => [
		            'grant' => false
		        ],
		        'delete' => [
		            'grant' => false
		        ]
	        ],
	        'registered' => [
				'create' => [
		            'grant' => true
		        ],
		        'view' => [
		            'grant' => true
		        ],
		        'update' => [
		            'grant' => true
		        ],
		        'index' => [
		            'grant' => true
		        ],
		        'delete' => [
		            'grant' => true
		        ]
	        ]
	    ];
	}
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'class' => CompositeAuth::className(),
                'authMethods' => [
                    ['class' => HttpBearerAuth::className()]
                ]
            ]
        ]);
    }
}
