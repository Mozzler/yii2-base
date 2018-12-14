<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use yii\helpers\Url;

class ModelUpdateAction extends BaseModelAction
{
	public $id = 'update';
	
	/**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_UPDATE;
    
    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully created.
     */
    public $viewAction = 'view';
	
    public function run()
    {
	    $id = \Yii::$app->request->get('id');
	    $model = $this->findModel($id);
	    
	    if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->getUniqueId(), $model);
        }
        
        // set scenario so we load and save the correct fields
        $model->setScenario($this->scenario);

		// load default values
		$model->loadDefaultValues();

		// populate the model with any GET data
		$model->load(\Yii::$app->request->post(),"");
		
		// populate the model with any POST data
		if ($model->load(\Yii::$app->request->post())) {
	        if ($model->save()) {
	            $response = \Yii::$app->getResponse();
	            $response->setStatusCode(201);
	            $id = implode(',', array_values($model->getPrimaryKey(true)));
	            
	            return $this->controller->redirect([$this->viewAction, 'id' => $id]);
	        } elseif (!$model->hasErrors()) {
	            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
	        }
        }
        
        $this->controller->data['model'] = $model;

		return parent::run();
    }
}