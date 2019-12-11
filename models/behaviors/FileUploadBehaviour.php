<?php

namespace mozzler\base\models\behaviors;

use mozzler\base\exceptions\BaseException;
use mozzler\base\models\File;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use League\Flysystem\AdapterInterface;

/**
 * AuditLog Behaviour for logging all changes to an entity
 * and who made those changes
 */
class FileUploadBehaviour extends Behavior
{

    /**
     *
     * @var int $gcProbability the probability (parts per million) that garbage collection (GC) should be performed
     * when running the cron.
     * Defaults to 10000, meaning 1% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */

    /** @var string $baseFolder */
    public $baseFolder = 'uploads';

    /** @var string $filesystemComponentName */
    public $filesystemComponentName = 'fs';

    /** @var bool $visibilityPrivate */
    public $visibilityPrivate = true;

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'uploadFile',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'uploadFile',
        ];
    }


    /**
     * @return array
     * Deal with the file saving
     *
     * You can override this in your model to do more fancy file saving.
     * @var $file \yii\web\UploadedFile
     * @var $fieldName string
     */
    public function uploadFile($event)
    {
        \Yii::error("Filepond uploadFile behaviour");
        /** @var File $fileModel */
        $fileModel = $this->owner;

        // -- Basic file validation checks
        if (!empty($_FILES) && !empty($_FILES['filepond'])) {
            \Yii::debug("The filepond file information is: " . json_encode($_FILES['filepond']));
        } else {
            \Yii::error("No filepond file uploaded: " . json_encode($_FILES));
            return false;
        }
        // Example $file = {"name":"!!72484913_10156718971467828_53539529008611328_n.jpg","type":"image\/jpeg","tmp_name":"\/tmp\/phpQa226D","error":0,"size":35420}
        $file = $_FILES['filepond'];
        if (!is_file($file['tmp_name'])) {
            throw new BaseException("Unable to find the uploaded file", 500, null, ['Developer note' => "The temporary file {$file['tmp_name']} could not be found", 'file' => $file]);
        }

        // -- Check the FileSystem has been defined
        if (!\Yii::$app->has($this->filesystemComponentName)) {
            throw new BaseException("Unable to find the {$this->filesystemComponentName} filesystem", 500, null, ['Developer note' => "In order to upload a file you need to define an {$this->filesystemComponentName} filesystem in the config/common.php component see https://github.com/creocoder/yii2-flysystem for more information"]);
        }
        // Use the FlySystem that's been defined
        $fsName = $this->filesystemComponentName;
        $fs = \Yii::$app->$fsName;
        // If you have defined a filesystem component using https://github.com/creocoder/yii2-flysystem
        \Yii::info("Using the Flysystem Filesystem you've defined");

        // ----------------------------------
        //   Prepare the file
        // ----------------------------------

        $md5 = md5_file($file['tmp_name']);
        $md5DirectoryChars = $md5[0] . $md5[1]; // Get the first 2 characters as the directory name
        $filename = $md5 . '.' . $this->getExtension($file['name']);
        $filepath = $md5DirectoryChars . '/' . $filename;
        \Yii::debug("creating the directory: {$md5DirectoryChars} as part of the filepath: $filepath");
        $fs->createDir($md5DirectoryChars);
        $visibilty = $this->visibilityPrivate ? AdapterInterface::VISIBILITY_PRIVATE : AdapterInterface::VISIBILITY_PUBLIC; // Defaults to Private

        // ----------------------------------
        //   Save the file
        // ----------------------------------

        $stream = fopen($file['tmp_name'], 'r+');
        $fs->writeStream($filepath, $stream, ['visibility' => $visibilty]); // Save to the filesystem (locally, Amazon S3... Whatever you've defined)


        // ----------------------------------
        //   Save the File fields
        // ----------------------------------

        $fileModel->filename = $filename;
        $fileModel->filepath = $filepath;

        return true;

        // Optional way of doing it locally without the fs filesystem
        /*
//        $folder = "uploads/"; // Will default to the 'web' folder and this will be public
//        // -------------------------
//        //  Sort out the folder
//        // -------------------------
//        $modelConfig = $this->modelConfig();
//        if (!empty($modelConfig['label'])) {
//            // Add the model name
//            $folder .= strtolower(str_ireplace(' ', '_', $modelConfig['label'])) . "/";
//        }
//        // Add the field name
//        $folder .= "{$fieldName}/";
//
//        // Create the directory if needed
//        if (!is_dir($folder)) {
//            \Yii::info("Creating the folder {$folder}");
//            mkdir($folder, 0777, true);
//        }
//
//        $filename = "{$md5}.{$file->extension}";
//        $filePath = $folder . $filename;
//        $file->saveAs($filePath);
//        // Return the fields used in the file: 'filename', 'filepath', 'type', 'mimeType', 'size', 'version', 'other'
//        return [
//            'filename' => $filename,
//            'filepath' => $filePath,
//            'type' => File::TYPE_LOCAL,
//            'size' => $file->size,
//            'mimeType' => \yii\helpers\FileHelper::getMimeType($filePath),
//            'originalFilename' => $file->name,
//        ]; // Return the info saved to the file model
        return true;
        */
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
     * @param $originalFilename
     * @return string file extension
     *
     * Based off vendor/yiisoft/yii2/web/UploadedFile.php
     */
    public function getExtension($originalFilename)
    {
        return strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    }

}
