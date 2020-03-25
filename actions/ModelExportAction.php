<?php

namespace mozzler\base\actions;

use mozzler\base\models\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\web\HttpException;

class ModelExportAction extends BaseModelAction
{
    public $id = 'export';

    /**
     * @var string the export scenario to define which fields to be exported
     */
    public $scenario = Model::SCENARIO_EXPORT;

    public $limit = 5;

    /**
     */
    public function run()
    {

        /** @var Model $model */
        $model = $this->controller->getModel();
        $model->setScenario($this->scenario);

        $rbacFilter = \Yii::$app->rbac->canAccessAction($this);
        if (true !== $rbacFilter) {
            \Yii::error("Can't access the action as the RBAC filter contains: " . VarDumper::export($rbacFilter));
            throw new HttpException(403); // 403 unauthorised
        }

        // ------------------------------------------
        //  Work out the Filename
        // ------------------------------------------
        $filename = empty($model->modelConfig['labelPlural']) ? $this->modelClass : $model->modelConfig['labelPlural'];
        // Add the current date (using the formatter if defined)
        if (\Yii::$app->formatter) {
            $filename .= "_" . \Yii::$app->formatter->asDate(new \DateTime(), \Yii::$app->formatter::FORMAT_WIDTH_LONG);
        } else {
            $filename .= "_" . date('Y-m-d'); // e.g  2020-03-25
        }

        $filename .= ".csv";

        // Partly inspired by https://appdividend.com/2019/05/09/php-array-values-example-php-array_values-function-tutorial/ and https://www.virendrachandak.com/techtalk/creating-csv-file-using-php-and-mysql/
        // Create a file pointer connected to the output stream
        $output = fopen('php://temp', 'w');

        $model->setScenario($this->scenario);

        // This will just contain the keys with null values as it's not a model that's been loaded up, but we just want the headers
        $headings = $model->scenarios()[$this->scenario];
        // Example $scenarioArray :
        // [
        //    'id' => '',
        //    'name' => null,
        //    'createdAt' => null,
        //    'createdUserId' => null,
        //    'updatedAt' => null,
        //    'updatedUserId' => null,
        // ]

        \Yii::debug("The scenario Array is: " . VarDumper::export($headings));


        // Output the column headings
//        fputcsv($output, $headings);

        $modelCount = \Yii::$app->t::countModels($this->controller->modelClass);
        \Yii::debug("Loading up the {$modelCount} models");

        $offset = 0;
        $processingProducts = true;
        $processedProducts = 0;
        while ($processingProducts === true) {

            $models = \Yii::$app->t::getModels($this->controller->modelClass, [], [
                'limit' => $this->limit,
                'offset' => $offset,
                'orderBy' => ['_id' => 1], // Ensuring First to last
                'checkPermissions' => true
            ]);
            if (empty($models)) {
                $processingProducts = false;
                break; // Exit out of the while loop
            }

            foreach ($models as $modelIndex => $model) {
                $model->setScenario($this->scenario);
                $rowContents = $model->toScenarioArray();
                if (!empty($rowContents['id']) && !empty($rowContents['_id'])) {
                    // The toScenarioArray seems to output the _id and id fields, so you get the data duplicated, this removes that
                    unset($rowContents['id']);
                }
                if ($processedProducts === 0) {
                    // If the first entry then output the header (field names)
                    fputcsv($output, array_keys($model->toScenarioArray()));
                }


                fputcsv($output, array_values($model->toScenarioArray()));
//                \Yii::debug("Model #{$modelIndex} contains: " . VarDumper::export($model->toScenarioArray()));
                $processedProducts++;
            }


            if ($processedProducts >= $modelCount) {
                $processingProducts = false; // we've completed the set
            }
            $offset += $this->limit;
        }

        \Yii::debug("Processed the {$processedProducts} models ");


        // Output headers so that the file is downloaded rather than displayed
//        $headers = \Yii::$app->response->headers;
//        $headers->add('Content-Type: text/csv; charset=utf-8');
//        $headers->add("Content-Disposition: attachment; filename={$filename}");
//        \Yii::$app->response->format = \Yii::$app->response::FORMAT_RAW;
        \Yii::$app->response->sendStreamAsFile($output, $filename, ['mimeType' => 'text/csv', 'inline' => false]);
        fclose($output);
        return;
    }
}