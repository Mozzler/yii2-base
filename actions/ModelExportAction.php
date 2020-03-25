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

    public $limit = 100;

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

        // Partly inspired by https://appdividend.com/2019/05/09/php-array-values-example-php-array_values-function-tutorial/ and https://www.virendrachandak.com/techtalk/creating-csv-file-using-php-and-mysql/
        // The ob_start and ob_get_clean() was an important addition pointed out by https://stackoverflow.com/a/13474770/7299352

        // Create a file pointer connected to the output stream (but buffered because otherwise there's errors about not being able to set the headers)
        ob_start();
        $output = fopen('php://output', 'w');
        $modelCount = \Yii::$app->t::countModels($this->controller->modelClass);
        \Yii::debug("Loading up the {$modelCount} models");

        // ------------------------------------------------------------------
        //  Main Processing Loop
        // ------------------------------------------------------------------
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
                if (isset($rowContents['id']) && isset($rowContents['_id'])) {
                    // The toScenarioArray seems to output the _id and id fields, so you get the data duplicated, this removes that
                    unset($rowContents['id']);
                }
                if ($processedProducts === 0) {
                    // If the first entry then output the header (field names)
                    fputcsv($output, array_keys($rowContents));
                }

                $values = array_values($rowContents); // 0 indexed instead of key'd
                foreach ($values as $index => $value) {
                    if (is_array($value)) {
                        // To prevent `Array to string conversion` we are converting non-strings to them here
                        // Although you might want to use VarDumper instead of json_encode($value)
//                        $values[$index] = VarDumper::export($value);
                        $values[$index] = json_encode($value);
                    }
                }
//                \Yii::debug("Model #$modelIndex : " . VarDumper::export($values));
                fputcsv($output, $values);
                $processedProducts++;
            }

            if ($processedProducts >= $modelCount) {
                $processingProducts = false; // we've completed the set
            }
            $offset += $this->limit;
        }
        \Yii::debug("Processed the {$processedProducts} {$this->controller->modelClass} models");

        fclose($output);
        $csvContents = ob_get_clean();


        // ------------------------------------------
        //  Work out the Filename
        // ------------------------------------------
        $filename = empty($model->modelConfig['labelPlural']) ? $this->modelClass : $model->modelConfig['labelPlural'];
//        $filename .= " x{$processedProducts}"; // If you wanted to add the count of entries
        // Add the current date (using the formatter if defined)
        if (\Yii::$app->formatter) {
            $filename .= " " . \Yii::$app->formatter->asDate(new \DateTime(), \Yii::$app->formatter::FORMAT_WIDTH_LONG);
        } else {
            $filename .= " " . date('Y-m-d'); // e.g  2020-03-25
        }

        $filename .= ".csv";

        \Yii::info("Now sending the file to the browser with the name '$filename'");
        \Yii::$app->response->sendContentAsFile($csvContents, $filename, ['mimeType' => 'text/csv', 'inline' => false]);
        return;
    }
}