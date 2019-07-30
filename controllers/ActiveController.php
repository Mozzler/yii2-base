<?php
namespace mozzler\base\controllers;

use yii\rest\ActiveController as BaseActiveController;

use mozzler\base\models\Model;

use yii\helpers\ArrayHelper;

use mozzler\auth\yii\oauth\auth\HttpBearerAuth;
use mozzler\rbac\filters\CompositeAuth;
use mozzler\rbac\filters\RbacFilter;

class ActiveController extends BaseActiveController
{
	
	// custom serializer to support scenario based responses
	public $serializer = [
        'class' => 'mozzler\base\yii\rest\Serializer',
        'collectionEnvelope' => 'items',
        'itemEnvelope' => 'item'
    ];

    /**
	 * Model class associated with this controller
	 */
    public $modelClass = null;
    
    public function init()
    {
        if ($this->modelClass === null) {
            throw new InvalidConfigException(get_class($this) . '::$modelClass must be set.');
        }
        
        // Instantiate an instance of the model class to ensure the database collectoin
        // is configured for RBAC
        \Yii::createObject($this->modelClass);
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'class' => 'mozzler\base\actions\ActiveIndexAction',
                'modelClass' => $this->modelClass
            ],
            'view' => [
                'class' => 'mozzler\base\actions\ActiveViewAction',
                'modelClass' => $this->modelClass
            ],
            'create' => [
                'class' => 'mozzler\base\actions\ActiveCreateAction',
                'modelClass' => $this->modelClass
            ],
            'update' => [
                'class' => 'mozzler\base\actions\ActiveUpdateAction',
                'modelClass' => $this->modelClass
            ],
            'delete' => [
                'class' => 'mozzler\base\actions\ActiveDeleteAction',
                'modelClass' => $this->modelClass
            ],
            'deleteMany' => [
                'class' => 'mozzler\base\actions\ActiveDeleteManyAction',
                'modelClass' => $this->modelClass
            ]
        ]);
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
                ],
                'deleteMany' => [
                    'grant' => false
                ]
            ],
            'admin' => [
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
		        ],
                'deleteMany' => [
                    'grant' => true
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
		            'grant' => false
		        ],
                'deleteMany' => [
                    'grant' => false
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
	        /**
			 * Enable OAuth2 authentication as this is an API request
			 */
            'authenticator' => [
                'class' => CompositeAuth::className(),
                'authMethods' => [
                    ['class' => HttpBearerAuth::className()]
                ]
            ],
            /**
			 * Enable RBAC permission checks on controller actions
			 */
            'rbacFilter' => [
                'class' => RbacFilter::className()
            ]
        ]);
    }

    public function getModel() {
		return \Yii::createObject($this->modelClass);
	}
}
