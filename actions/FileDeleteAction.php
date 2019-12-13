<?php
namespace mozzler\base\actions;

use mozzler\base\exceptions\BaseException;
use mozzler\base\models\File;
use yii\helpers\Url;

class FileDeleteAction extends ModelDeleteAction
{
	public $id = 'delete';

    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully deleted.
     */
    public $viewAction = 'index';
    /**
     * Deletes a file model accepting the filepond style or the normal request way.
     * @return \yii\db\ActiveRecordInterface
     * @throws BaseException
     */
    public function run()
    {
        $request = \Yii::$app->request;
        if ($request->isDelete && !empty($request->getRawBody())) {
            $fileIdToDelete = $request->getRawBody();
            /** @var File $fileToDelete */
            $fileToDelete = \Yii::$app->t::getModel(File::class, $fileIdToDelete);
            if (empty($fileToDelete)) {
                throw new BaseException("Can't file {$fileIdToDelete} to Delete", 404);
            }
            \Yii::info("Deleting filepond file with ID: $fileIdToDelete");
            $deleted = $fileToDelete->delete();
            return $deleted;
        }

        return parent::run();
    }
}