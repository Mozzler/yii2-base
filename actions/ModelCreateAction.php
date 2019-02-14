<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use yii\helpers\Url;

class ModelCreateAction extends BaseModelAction
{
	public $id = 'create';
    
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_CREATE;
    
    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully created.
     */
    public $viewAction = 'view';

    /**
     * Creates a new model.
     * @return \yii\db\ActiveRecordInterface the model newly created
     * @throws ServerErrorHttpException if there is any error when creating the model
     */
    public function run()
    {
        /* @var $model \yii\db\ActiveRecord */
        $model = $this->controller->getModel();
        $model->setScenario($this->scenario);

		// load default values
		$model->loadDefaultValues();

		// populate the model with any GET data
		$model->load(\Yii::$app->request->get(),"");
		
		// populate the model with any POST data
		if ($model->load(\Yii::$app->request->post())) {
	        if ($model->save()) {
	            $response = \Yii::$app->getResponse();
	            $response->setStatusCode(201);
	            $id = implode(',', array_values($model->getPrimaryKey(true)));
	            
	            return $this->controller->redirect([$this->viewAction, 'id' => $id]);
	        } elseif (!$model->hasErrors()) {
	            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
	        }
        }
        
        $this->controller->data['model'] = $model;

		return parent::run();
    }
}