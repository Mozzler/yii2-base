<?php

namespace mozzler\base\models\behaviors;

use MongoDB\BSON\ObjectId;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\File;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use League\Flysystem\AdapterInterface;
use yii\helpers\VarDumper;

/**
 * AuditLog Behaviour for logging all changes to an entity
 * and who made those changes
 */
class FileUploadBehaviour extends Behavior
{
    /** @var string $filesystemComponentName */
    public $filesystemComponentName = 'fs';

    /** @var bool $visibilityPrivate */
    public $visibilityPrivate = true;

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'uploadFile',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'uploadFile',
            BaseActiveRecord::EVENT_BEFORE_DELETE => 'deleteFile',
        ];
    }

    /**
     * Deal with the file saving
     *
     * You can replace this with a different behaviour to do more fancy file saving if you want.
     * @param $event
     * @throws BaseException
     */
    public function uploadFile($event)
    {
        /** @var File $fileModel */
        $fileModel = $this->owner;


        // -- Basic file validation checks
        // Example $file = {"name":"!!72484913_10156718971467828_53539529008611328_n.jpg","type":"image\/jpeg","tmp_name":"\/tmp\/phpQa226D","error":0,"size":35420}
        $fileInfo = self::getFileInfo();
        if (!empty($fileInfo)) {
            \Yii::debug("The file information is: " . json_encode($fileInfo));
        } else {
            \Yii::error("No file uploaded" . json_encode(['Error' => 'No valid $_FILES info defined', '_FILES' => $_FILES, '$file' => $fileInfo]));
            return false;
        }
        if (!is_file($fileInfo['tmp_name'])) {
            throw new BaseException("Unable to find the uploaded file", 500, null, ['Developer note' => "The temporary file {$fileInfo['tmp_name']} could not be found", 'file' => $fileInfo]);
        }
        $fs = $fileModel->getFilesystem();


        if (empty($fileModel->_id)) {
            $fileModel->_id = new ObjectId(); // Create a new model ID in case you want to use that in the filename, but do it after getting the Filesystem
        }

        // ----------------------------------------
        //   Check associated model fields
        // ----------------------------------------
        $convertFunctionReference = null;
        if (!empty($fileModel->modelType) && !empty($fileModel->fieldName)) {
            $associatedModelField = $fileModel->getAssociatedModelField();
            if (empty($associatedModelField)) {
                \Yii::warning("Invalid model field yet the modelType and fieldName are set");
            } else {

                if (!empty($associatedModelField['filenameTwigTemplate'])) {
                    $fileModel->filenameTwigTemplate = $associatedModelField['filenameTwigTemplate'];
                    \Yii::debug("Using the {$fileModel->modelType} specially associated filenameTwigTemplate");
                }
                if (isset($associatedModelField['folderpathTwigTemplate'])) {
                    $fileModel->folderpathTwigTemplate = $associatedModelField['folderpathTwigTemplate'];
                    \Yii::debug("Using the {$fileModel->modelType} specially associated folderpathTwigTemplate");
                }

                // We accept the model.modelField.convertFunction to be:
                // A string with a name of the method on the object
                // A function (closure) itself
                // A callable style array e.g ['\component\avatarCreator', 'convertMethod']
                if (isset($associatedModelField['convertFunction'])) {

                    if (is_string($associatedModelField['convertFunction'])) {
                        // Is it a  method name on the associated model?
                        $associatedModel = $fileModel->createAssociatedModel();
                        if (is_callable([$associatedModel, $associatedModelField['convertFunction']])) {
                            $convertFunctionReference = [$associatedModel, $associatedModelField['convertFunction']];
                            \Yii::debug("Using the {$fileModel->modelType} specially associated convertFunctionReference of the method name {$associatedModelField['convertFunction']} on the model");
                        } else if (is_callable($associatedModelField['convertFunction'])) {
                            // Likely a static function reference
                            \Yii::debug("Using the {$fileModel->modelType} specially associated convertFunctionReference of the static string name {$associatedModelField['convertFunction']} on the model");
                        }
                    } else if (is_callable($associatedModelField['convertFunction'])) {
                        // Is a function or an array entry pointing to a function which call_user_func will accept
                        $convertFunctionReference = $associatedModelField['convertFunction'];
                        \Yii::debug("Using the {$fileModel->modelType} specially associated convertFunctionReference of the possibly function or maybe array on the model");
                    }
                }
            }
        }

        // -- Convert the file, if needed
        if (is_null($convertFunctionReference) && method_exists($fileModel, 'convert')) {
            $convertFunctionReference = [$fileModel, 'convert'];
            \Yii::debug("Using the {$fileModel->ident()} associated convert method");
        }
        if (!is_null($convertFunctionReference)) {
            // We later save the $file['tmp_name'] entry to the file system (e.g S3 or Google Cloud)
            \Yii::debug("Running the custom convert method on the {$fileModel->ident()}");
            // $convertFunctionReference is a function which can convert the file to a new type (e.g PNG to resized JPG)
            // The function will be given the $fileInfo and the $file object and expects the $fileInfo returned
            // Accepts a closure e.g: function($fileInfo, $file) { /* Do stuff...*/ return $fileInfo;}
            // Also accepts a string pointing to a method on the model e.g: 'avatarFileConvert'
            // Or accepts the array style callable e.g: '/class/Name', 'methodName']
            $fileInfo = call_user_func($convertFunctionReference, $fileInfo, $fileModel); // If you need to do some conversion, e.g converting .png images to .jpg
        }

        // ----------------------------------
        //   Prepare the file
        // ----------------------------------

        $extension = $fileModel->getExtension($fileInfo['name']);
        $twigData = ['fileModel' => $fileModel, 'extension' => $extension, 'fsName' => $fileModel->filesystemName, 'REQUEST' => \Yii::$app->request]; // The REQUEST lets you do things like {{REQUEST.GET.state}} to access a query string

        $filename = \Yii::$app->t::renderTwig($fileModel->filenameTwigTemplate, $twigData);
        $twigData['filename'] = $filename;
        $folderpath = \Yii::$app->t::renderTwig($fileModel->folderpathTwigTemplate, $twigData);
        \Yii::debug("Creating the directory: {$folderpath} (the directory could already exist) with the filename being {$filename}");
        $fs->createDir($folderpath); // Creating it
        $visibilty = $this->visibilityPrivate ? AdapterInterface::VISIBILITY_PRIVATE : AdapterInterface::VISIBILITY_PUBLIC; // Defaults to Private

        $filepath = $folderpath . $filename;
        $exists = $fs->has($filepath);
        if (!$exists) {
            // ----------------------------------
            //   Save the file
            // ----------------------------------
            $stream = fopen($fileInfo['tmp_name'], 'r+');
            $fs->writeStream($filepath, $stream, ['visibility' => $visibilty]); // Save to the filesystem (locally, Amazon S3... Whatever you've defined)
        } else {
            // This is a duplicate file
            \Yii::warning("This is a duplicate file, you've already uploaded {$filepath}");
        }

        // ----------------------------------
        //   Save the File fields
        // ----------------------------------
        $fileModel->filename = $filename;
        $fileModel->filepath = $filepath; // The filepath is the full location

        \Yii::debug("Final File Model - " . VarDumper::export($fileModel->toArray()));
        \Yii::debug("Final \$fileInfo - " . VarDumper::export($fileInfo));
        @unlink($fileInfo['tmp_name']); // PHP will automatically remove temporary files, but if the convert() method is pointing to a new file then we need to directly remove that
        return $fileModel;
    }

    public function deleteFile($event)
    {
        /** @var File $fileModel */
        $fileModel = $this->owner;

        // Use the FlySystem that's been defined
        $fs = $fileModel->getFilesystem();

        $exists = $fs->has($fileModel->filepath);
        if ($exists) {
            $deleted = $fs->delete($fileModel->filepath);
            \Yii::error("Deleted file {$fileModel->filepath} based on the File ID: {$fileModel->getId()}");
            // Note: There could be other file documents pointing to the same file (multiple uploads of the same file).
            // Might need to delete the other duplicate File entries? (If so, trigger that in the FileController actionDelete method, not here)
            return $deleted;
        } else {
            \Yii::error("Can't find so thus can't delete {$fileModel->filepath}");
        }
        return false; // Doesn't stop the processing
    }


    /**
     * @param $originalFilename
     * @return string original file base name
     *
     * Based off vendor/yiisoft/yii2/web/UploadedFile.php
     */
    public function getBaseName($originalFilename)
    {
        // https://github.com/yiisoft/yii2/issues/11012
        $pathInfo = pathinfo('_' . $originalFilename, PATHINFO_FILENAME);
        return mb_substr($pathInfo, 1, mb_strlen($pathInfo, '8bit'), '8bit');
    }

    /**
     * Get File Info
     *
     * Only expecting a single file response
     * @return array|bool
     *
     * Example response: {"modelType":"Client","name":"!Ikigai - A reason for Being.jpg","type":"image\/jpeg","tmp_name":"\/tmp\/phpuLPa2S","error":0,"size":109927,"fieldName":"driversLicenceFile"}
     */
    public static function getFileInfo()
    {
        // Example $_FILES = {"Client":{"name":{"driversLicenceFile":"!!72484913_10156718971467828_53539529008611328_n.jpg"},"type":{"driversLicenceFile":"image\/jpeg"},"tmp_name":{"driversLicenceFile":"\/tmp\/phpmypYid"},"error":{"driversLicenceFile":0},"size":{"driversLicenceFile":35420}}}
        if (empty($_FILES)) {
            return false;
        }
        $modelType = key($_FILES);
        $file = ['modelType' => $modelType];
        foreach ($_FILES[$modelType] as $fieldField => $infoEntry) {

            $fieldName = key($infoEntry);
            // e.g name = image.jpg
            $file[$fieldField] = $infoEntry[$fieldName];

        };
        $file['fieldName'] = isset($fieldName) ? $fieldName : null;
        return $file;
    }

}
