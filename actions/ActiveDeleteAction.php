<?php
namespace mozzler\base\actions;

use mozzler\base\models\Model;
use Yii;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;

class ActiveDeleteAction extends \yii\rest\DeleteAction
{

    public function run($id)
    {
        $model = $this->findModel($id);

        if ($model && $model->delete()) {
            return ['success' => true];
        }

        // -- If the above failed then try the base delete method
        parent::run($id); // Throws an exception if it fails.
        return ['success' => true];
    }
    
}
