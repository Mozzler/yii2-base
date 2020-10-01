<?php

namespace mozzler\base\controllers;

use mozzler\base\components\Tools;
use mozzler\base\controllers\ModelController as BaseController;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\behaviors\FileUploadBehaviour;
use mozzler\base\models\File;
use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;
use yii\web\HttpException;


/**
 * Class FileController
 * @package mozzler\base\controllers
 *
 * You'll need to ensure the FileController is included in the app config to get file uploads working
 *
 * e.g config/web.php
 *
 *     'controllerMap' => [
 * 'file' => [
 * 'class' => 'mozzler\base\controllers\FileController'
 * ],
 * ],
 */
class FileController extends BaseController
{

    // Disable CSRF validation for file uploads (until we can work out how to enable it with the FilePond file uploader)
    public $enableCsrfValidation = false;
    public $modelClass = 'mozzler\base\models\File';

    public static function rbac()
    {
        return ArrayHelper::merge(parent::rbac(), [
            'registered' => [
                'index' => ['grant' => true],
                'view' => ['grant' => true],
                'create' => ['grant' => true],
                'update' => ['grant' => true],
                'delete' => ['grant' => true]
            ],
            'admin' => [
                'index' => ['grant' => true],
                'view' => ['grant' => true],
                'create' => ['grant' => true],
                'update' => ['grant' => true],
                'delete' => ['grant' => true]
            ]
        ]);
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            // Manually setting create and delete to be what's used by the filepond uploader
            'create' => new UnsetArrayValue(),
            'delete' => [
                'class' => 'mozzler\base\actions\FileDeleteAction'
            ],
        ]);
    }

    /**
     * CREATE
     *
     * @return string
     * @throws BaseException
     * @throws HttpException
     */
    public function actionCreate()
    {

        // Example $file = {"modelType":"Client","name":"!Ikigai - A reason for Being.jpg","type":"image\/jpeg","tmp_name":"\/tmp\/phpuLPa2S","error":0,"size":109927,"fieldName":"driversLicenceFile"}
        $file = FileUploadBehaviour::getFileInfo();
        if (!empty($file)) {
            \Yii::debug("The filepond file information is: " . json_encode($file));
        } else {
            \Yii::error("No file uploaded: " . json_encode($_FILES));
            throw new BaseException("No filepond file uploaded", null, null, ['Mozzler Base Filepond Uploader' => 'Create Action in the File Controller', 'Files' => $_FILES]);
        }


        if ($file['error'] > 0) {
            // As per https://www.php.net/manual/en/features.file-upload.errors.php
            $phpFileUploadErrors = array(
                UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            );
            \Yii::error("Error with the uploaded file #{$file['error']} {$phpFileUploadErrors[$file['error']]}");
            throw new BaseException("Error with the uploaded file: {$phpFileUploadErrors[$file['error']]}", null, null, ['Mozzler Base Filepond Uploader' => 'Create Action in the File Controller', 'Files' => $_FILES]);
        }
        if (0 === $file['size']) {
            throw new BaseException("The uploaded file is empty", null, null, ['Mozzler Base Filepond Uploader' => 'Create Action in the File Controller', 'Files' => $_FILES]);
        }

        /** @var File $fileObject */
        $fileObject = \Yii::$app->t::createModel(File::class, [
            'filename' => $file['tmp_name'], // Need something for passing the validation, but this is to be re-written in the FileUploadBehaviour
            'filepath' => $file['tmp_name'], // Need something for passing the validation, but this is to be re-written in the FileUploadBehaviour
            'fieldName' => isset($file['fieldName']) ? $file['fieldName'] : null,
            'modelType' => isset($file['modelType']) ? $file['modelType'] : null,
            'originalFilename' => $file['name'],
            'size' => $file['size'],
            'mimeType' => $file['type'],  // Could also use: \yii\helpers\FileHelper::getMimeType($file['tmp_name']),
            'other' => [
                'fieldName' => isset($file['fieldName']) ? $file['fieldName'] : null,
                'modelType' => isset($file['modelType']) ? $file['modelType'] : null,
                'md5' => md5_file($file['tmp_name']),
            ]
        ]);

        // Sanity validation check - Ensure we haven't stuffed up the config somewhere
        $valid = $fileObject->validate();
        if (!$valid) {
            \Yii::error("Unable to start file upload, model validation failed: " . json_encode($fileObject->getErrors(), JSON_PRETTY_PRINT));
            throw new BaseException("Validation Errors - Unable to save file", 500, null, ['message' => 'Unable to validate the File model before even trying to do any file processing. Error(s): ' . json_encode($fileObject->getErrors(), JSON_PRETTY_PRINT), 'messageData' => $fileObject]);
        }

        // We use the fileUpload Behaviour on the model to do the file processing
        $saved = $fileObject->save(true, null, false); // Save without checking permissions
        if ($saved) {
            return $fileObject->getId();
        } else {
            throw new HttpException(500, "Unable to save file");
        }

    }


    /**
     *
     * DOWNLOAD
     *
     *
     * @throws BaseException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionDownload()
    {
        $fileId = \Yii::$app->request->get('id');

        if (!$fileId) {
            throw new \yii\web\NotFoundHttpException("No File ID specified");
        }
        /** @var File $fileModel */
        $fileModel = \Yii::$app->t::getModel(File::class, $fileId);
        if (empty($fileModel)) {
            throw new \yii\web\NotFoundHttpException("File with ID {$fileId} not found");
        }
        $fs = $fileModel->getFilesystem();
        $exists = $fs->has($fileModel->filepath);
        if (!$exists) {
            throw new \yii\web\NotFoundHttpException("File with Id {$fileId} not found");
        }

        $handle = $fs->readStream($fileModel->filepath);
        $options = [
            'size' => empty($fileModel->size) ? $fs->getSize($fileModel->filepath) : $fileModel->size,
            'inline' => true, // Show the file in the browser (assuming it's an image, or something)
            'mimeType' => empty($fileModel->mimeType) ? $fs->getMimetype($fileModel->filepath) : $fileModel->mimeType
        ];
        if ('application/octet-stream' === $options['mimeType']) {
            $options['inline'] = false; // Don't show unknown files in the browser, force the user to try and download them
        }

        $filename = empty($fileModel->originalFilename) ? $fileModel->filename : $fileModel->originalFilename; // Use the original filename if available

        // --------------------------------------
        //  Send file (as a stream)
        // --------------------------------------
        \Yii::$app->response->sendStreamAsFile($handle, $filename, $options);
    }


}
