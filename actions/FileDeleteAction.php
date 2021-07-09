<?php

namespace mozzler\base\actions;

use mozzler\base\exceptions\BaseException;
use mozzler\base\models\File;
use yii\helpers\ArrayHelper;
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

            // --------------------------------------------------------------------
            //   Check and delete associated file
            // --------------------------------------------------------------------
            // There's cases where you create a 2nd file based on the first
            // e.g a thumbnail or 1 page PDF version
            // This lets you also delete the associated file
            // @todo: Allow an array of files? Don't have that need yet
            $associatedFileId = ArrayHelper::getValue($fileToDelete, 'other.__ASSOCIATED_FILE', null);
            if (!is_null($associatedFileId)) {
                $associatedFileToDelete = \Yii::$app->t::getModel(File::class, $associatedFileId);
                if (empty($associatedFileToDelete)) {
                    \Yii::error("Can't find, so can't delete the \"__ASSOCIATED_FILE\" associated fileId {$associatedFileId}");
                } else {
                    \Yii::info("Deleting the associated {$associatedFileToDelete->ident()} (based on the other.__ASSOCIATED_FILE");
                    $associatedFileToDelete->delete();
                }
            }

            \Yii::info("Deleting filepond file with ID: $fileIdToDelete");
            $deleted = $fileToDelete->delete();
            return $deleted;
        }

        return parent::run();
    }
}