<?php
namespace mozzler\base\controllers;

use yii\rest\ActiveController as BaseActiveController;

use mozzler\base\models\Model;

class ActiveController extends BaseActiveController
{
	
	// custom serializer to support scenario based responses
	public $serializer = '\mozzler\base\yii\rest\Serializer';
	
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
}
