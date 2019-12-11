<?php

namespace mozzler\base\controllers;

use mozzler\base\controllers\ModelController as BaseController;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\File;
use yii\helpers\ArrayHelper;
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
                'index' => [
                    'grant' => true
                ],
                'view' => [
                    'grant' => true
                ],
                'create' => [
                    'grant' => true
                ],
                'update' => [
                    'grant' => true
                ],
                'delete' => [
                    'grant' => true
                ]
            ],
            'admin' => [
                'create' => [
                    'grant' => true
                ],
                'update' => [
                    'grant' => true
                ],
                'delete' => [
                    'grant' => true
                ]
            ]
        ]);
    }

    public function actions()
    {
        return [
//			'create' => [
//	            'class' => 'mozzler\base\actions\ModelCreateAction'
//	        ],
            'view' => [
                'class' => 'mozzler\base\actions\ModelViewAction'
            ],
//	        'update' => [
//	            'class' => 'mozzler\base\actions\ModelUpdateAction'
//	        ],
            'index' => [
                'class' => 'mozzler\base\actions\ModelIndexAction'
            ],
//	        'delete' => [
//	            'class' => 'mozzler\base\actions\ModelDeleteAction'
//	        ]
        ];
    }

    public function actionCreate()
    {

        if (!empty($_FILES) && !empty($_FILES['filepond'])) {
            \Yii::debug("The filepond file information is: " . json_encode($_FILES['filepond']));
        } else {
            \Yii::error("No filepond file uploaded: " . json_encode($_FILES));
            throw new BaseException("No filepond file uploaded", null, null, ['Mozzler Base Filepond Uploader' => 'Create Action in the File Controller', 'Files' => $_FILES]);
        }

        // Example $file = {"name":"!!72484913_10156718971467828_53539529008611328_n.jpg","type":"image\/jpeg","tmp_name":"\/tmp\/phpQa226D","error":0,"size":35420}
        $file = $_FILES['filepond'];

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
            'filename' => $file['tmp_name'], // Need something for passing the validation
            'originalFilename' => $file['name'],
            'size' => $file['size'],
            'mimeType' => $file['type'],  // Could also use: \yii\helpers\FileHelper::getMimeType($file['tmp_name']),
        ]);
        // Use the fileUpload Behaviour on the model to do the file processing
        $saved = $fileObject->save(true, null, false); // Save without checking permissions
        if ($saved) {
            return $fileObject->getId();
        } else {
            throw new HttpException(500, "Unable to save file");
        }

    }


    public function actionDelete()
    {
        \Yii::error("Filepond file to be deleted: " . json_encode(['REQUEST' => $_REQUEST]));

        return true;
    }

}
