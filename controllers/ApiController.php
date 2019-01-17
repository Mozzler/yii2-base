<?php
namespace mozzler\base\controllers;

use yii\rest\Controller as BaseController;

use yii\helpers\ArrayHelper;

use mozzler\auth\yii\oauth\auth\HttpBearerAuth;
use mozzler\rbac\filters\CompositeAuth;
use mozzler\rbac\filters\RbacFilter;

class ApiController extends BaseController
{

    /**
     * Specify a list of actions that should deny public access
     * 
     * ie: ['index', 'view']
     */
    public static $rbacPublicActionsDisabled = [];
	
	// custom serializer to support scenario based responses
	public $serializer = [
        'class' => 'mozzler\base\yii\rest\Serializer',
        'collectionEnvelope' => 'items',
        'itemEnvelope' => 'item'
    ];
    
    public static function rbac() {
        $rbac = [];

        $rbac['public'] = [];
        foreach (self::$rbacPublicActionsDisabled as $actionName) {
            $rbac['public'] = [$actionName] = [
                'grant' => false
            ];
        }

        return $rbac;
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
}
