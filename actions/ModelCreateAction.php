<?php

namespace mozzler\base\actions;

use mozzler\base\fields\File;
use mozzler\base\models\Model;
use yii\helpers\Url;
use yii\web\UploadedFile;

class ModelCreateAction extends BaseModelAction
{
    public $id = 'create';

    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */

    public $scenario = Model::SCENARIO_CREATE;

    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully created.
     */
    public $viewAction = 'view';

    /**
     * Creates a new model.
     * @return \yii\db\ActiveRecordInterface the model newly created
     * @throws \yii\web\ServerErrorHttpException if there is any error when creating the model
     * @throws \yii\web\ForbiddenHttpException if insert permission is not found
     */
    public function run()
    {
        // Get the defaults for the model
        /** @var \yii\base\Model $model */
        $model = $this->loadModel();

        // Populate the model with any POST data
        if ($model->load(\Yii::$app->request->post())) {
            try {
                if ($model->validate()) {
                    // -- Check if has a file
                    foreach ($model->modelFields() as $fieldName => $fieldAttributes) {
                        if (isset($fieldAttributes['type']) && 'File' === $fieldAttributes['type']) {
                            // Found a file field, check the uploads as per https://www.yiiframework.com/doc/guide/2.0/en/input-file-upload
                            \Yii::info("Saving the $fieldName file field");

                            $uploadFile = UploadedFile::getInstance($model, $fieldName);
                            $fileInfo = $model->uploadFile($fieldName, $uploadFile);
                            /** @var \mozzler\base\models\File $fileInfoModel */
                            $fileInfoModel = \Yii::$app->t::createModel(\mozzler\base\models\File::class, $fileInfo);
                            $saved = $fileInfoModel->save(true, null, false);

                            \Yii::info("Uploaded: " . json_encode(['uploaded info' => $fileInfo, 'savedCorrectly' => $saved, 'saveErrors' => $fileInfoModel->getErrors()]));
                            $model->$fieldName = $fileInfoModel->_id; // Save the relation
                        }
                    }
                }

                if ($model->save()) {
                    $response = \Yii::$app->getResponse();
                    $response->setStatusCode(201);
                    $id = implode(',', array_values($model->getPrimaryKey(true)));

                    return $this->controller->redirect([$this->viewAction, 'id' => $id]);
                } elseif (!$model->hasErrors()) {
                    throw new \yii\web\ServerErrorHttpException('Failed to create the object for unknown reason.');
                }
            } catch (\mozzler\rbac\PermissionDeniedException $e) {
                throw new \yii\web\ForbiddenHttpException('You do not have permission to perform this operation');
            }
        }
        $this->controller->data['model'] = $model;

        return parent::run();
    }

}