<?php
namespace mozzler\base\actions;

use yii\helpers\Url;

class ModelDeleteAction extends BaseModelAction
{
	public $id = 'delete';

    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully deleted.
     */
    public $viewAction = 'index';

    /**
     * Deletes a model.
     * @return \yii\db\ActiveRecordInterface the model newly created
     * @throws ServerErrorHttpException if there is any error when creating the model
     */
    public function run()
    {
	    $id = \Yii::$app->request->get('id');
        $model = $this->findModel($id);
	    
        if ($model && $model->delete()) {
	        return $this->controller->redirect([$this->viewAction]);
        }

        return parent::run();
    }
}