<?php
namespace mozzler\base\controllers;

use yii\helpers\ArrayHelper;

class ApiMetadataController extends ApiController {

    public static function rbac()
    {
        return ArrayHelper::merge(parent::rbac(), [
            'public' => [
                'streams' => [
                    'grant' => false
                ]
            ],
            'registered' => [
                'streams' => [
                    'grant' => true
                ]
            ]
        ]);
    }

    public function actionStreams() {
        $indexManager = \Yii::createObject('mozzler\base\components\IndexManager');

        $modelClassList = $indexManager->buildModelClassList(['@app/models/', '@mozzler/base/models/', '@mozzler/auth/models/']);

        $result = [
            'userId' => \Yii::$app->user->getIdentity()->id,
            'models' => []
        ];

        foreach ($modelClassList as $className) {
            $model = \Yii::createObject($className);
            $permissionFilter = \Yii::$app->rbac->can($model, 'find',[]);

            if ($permissionFilter === true) {
                $permissionFilter = [];
            }

            if ($permissionFilter !== false) {
                $permissionFilter = \Yii::$app->mongodb->getQueryBuilder()->buildCondition($permissionFilter);
            }

            $result['models'][$className] = [
                'collection' => $model->collectionName(),
                'permissionFilter' => $this->buildStreamFilter($permissionFilter)
            ];
        }

        return $result;
    }

    public function behaviors()
	{
		$behaviors = parent::behaviors();

		// remove authentication filter
		$auth = $behaviors['authenticator'];
		unset($behaviors['authenticator']);

		$rbacFilter = $behaviors['rbacFilter'];
		unset($behaviors['rbacFilter']);
		
		// add CORS filter
		$behaviors['corsFilter'] = [
			'class' => \yii\filters\Cors::className(),
		];
		
		// re-add authentication filter
		$behaviors['authenticator'] = $auth;
		// avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
		$behaviors['authenticator']['except'] = ['options'];

		$behaviors['rbacFilter'] = $rbacFilter;
	
		return $behaviors;
    }

    /**
     * Take a standard MongoDB filter and convert it to a stream filter
     */
    protected function buildStreamFilter($filter) {
        if (!is_array($filter)) {
            return $filter;
        }

        $finalFilter = [];

        foreach ($filter as $key => $value) {
            if (!is_numeric($key)) {
                if ($key == '_id') {
                    $key = 'documentKey.'.$key;
                }
                else if (substr($key,0,1) != '$') {
                    $key = 'fullDocument.'.$key;
                }
            }

            if ($value instanceof \MongoDB\BSON\ObjectId) {
                $finalFilter[$key] = strval($value);
            }
            else if (is_array($value)) {
                $finalFilter[$key] = $this->buildStreamFilter($value);
            } else {
                $finalFilter[$key] = $value;
            }
        }

        return $finalFilter;
    }
}
