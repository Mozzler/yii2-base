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
     * e.g: ['index', 'views', 'create', 'update', 'delete']
     */
    public static $rbacPublicActionsDisabled = ['index', 'views', 'create', 'update', 'delete'];

    // custom serializer to support scenario based responses
    public $serializer = [
        'class' => 'mozzler\base\yii\rest\Serializer',
        'collectionEnvelope' => 'items',
        'itemEnvelope' => 'item'
    ];


    /**
     * @return array
     *
     * By default, don't allow public access, but do allow registered (logged in) access.
     *
     * This uses the Controller's public static $rbacPublicActionsDisabled array, which you can adjust accordingly if adding new actions
     * and want to keep the public locked out but registered (logged in) users being able to access them.
     *
     * If you want full control you can manually overwrite this by declaring your own rbac function. e.g
     *
     * public static function rbac() {
     * return [
     * "public" => [
     *   'specialActionName' => [
     *     'grant' => true
     * ],
     *   'index' => [
     *     'grant' => false
     * ],
     *   'view' => [
     *     'grant' => false
     * ],
     *   'create' => [
     *     'grant' => false
     * ],
     *   'update' => [
     *     'grant' => false
     * ],
     *   'delete' => [
     *   'grant' => false
     * ]],
     * "registered" => [
     *   'specialActionName' => [
     *     'grant' => true
     * ],
     *   'index' => [
     *     'grant' => true
     * ],
     *   'view' => [
     *     'grant' => true
     * ],
     *   'create' => [
     *     'grant' => true
     * ],
     *   'update' => [
     *     'grant' => true
     * ]
     *   'delete' => [
     *     'grant' => true
     * ]]];
     * }
     */
    public static function rbac()
    {

        $rbac = [
            'public' => [],
            'registered' => [],
        ];

        $actions = static::$rbacPublicActionsDisabled;
        foreach ($actions as $actionIndex => $actionName) {
            $rbac['public'][$actionName] = [
                'grant' => false
            ];
            $rbac['registered'][$actionName] = [
                'grant' => true
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
