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
            return true;
        }
        return parent::run();
    }
    
}
