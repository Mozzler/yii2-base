<?php

namespace mozzler\base\components;

use mozzler\base\widgets\modelReport\ModelReport;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use Yii;
use yii\web\JsExpression;
use yii\base\BaseObject;

/**
 * Class ReportsManager
 * @package app\components
 *
 * Helps create the Chart.js, Panel and Table based reports
 */
class ReportsManager extends BaseObject
{
    public $apiEndpoint = '/v1/reports/reportItem';


    public function init()
    {
        parent::init();

        // Initialization after configuration is applied
        $this->coloursCount = count($this->colours);
        $this->colourGradients = $this->getColourGradients(); // Get all the colour gradients including the automatically created 'colours' gradient
    }

    // Default colours, with names
    public $colours = [
        'maximum-yellow-red' => 'rgb(245, 184, 46)',
        'baby-blue-eyes' => 'rgb(171, 210, 250)',
        'pink' => 'rgb(255, 193, 207)',
        'tea-green' => 'rgb(213, 255, 217)',
        'plum-web' => 'rgb(221, 166, 222)',
        'vivid-tangerine' => 'rgb(238, 168, 149)',
        'alice-blue' => 'rgb(214, 228, 235)',
        'light-slate-gray' => 'rgb(123, 143, 163)',
        'light-green' => "rgb(141, 214, 195)",
        'light-blue' => "rgb(98, 173, 212)",
        'puke-green' => "rgb(222, 228, 172)",
        'not-quite-yellow' => "rgb(253, 233, 163)",
        'sort-of-orange' => "rgb(252, 200, 112)",
    ];

    public $dataProviderConfig = ['pagination' => false];
    public $coloursCount = null;

    /**
     * @var array
     * This will be prefilled with the 'colours' gradient if you use \Yii::$app->reportsManager->getColourGradients
     */
    public $colourGradients = [ ];


    /**
     * @param $model Model
     * @param string $reportItemName
     * @param $rbacFilter array|null based on `$rbacFilter = \Yii::$app->rbac->canAccessAction($this);` but inside an action
     * @param null|array $searchParams defaults to \Yii::$app->request->get()
     * @return array|mixed
     * @throws BaseException
     * @throws \yii\base\InvalidConfigException
     */
    public function returnDataAndConfig($model, $reportItemName, $rbacFilter = null, $searchParams = null)
    {
        $modelClassname = \Yii::$app->t::getModelClassName($model);
        if (!method_exists($model, 'reportItems')) {
            throw new BaseException("$modelClassname is invalid and doesn't have any report items");
        }

        $reportItems = $model->reportItems();
        if (empty($reportItems[$reportItemName])) {
            throw new BaseException("Invalid Report Item Name $reportItemName for model $modelClassname");
        }

        $reportItem = $reportItems[$reportItemName];
        \Yii::debug("Processing the report Item $reportItemName for model $modelClassname");
        $config = ArrayHelper::getValue($reportItem, 'widgetConfig', []);

        // The widgetClass is required as it tells us which type it is (e.g 'app\widgets\modelReport\items\Panel' or 'app\widgets\modelReport\items\Chart')
        if (empty($reportItem['widgetClass'])) {
            throw new BaseException("$modelClassname reportItem['$reportItemName'] configured incorrectly, it doesn't have a 'widget' class (programming error)");
        }
        /** @var ModelReport $modelReport */
        $modelReport = \Yii::createObject($reportItem['widgetClass']); // Create the associated report widget, where things like colours and helper functions are


        if (empty($searchParams)) {
            $searchParams = \Yii::$app->request->get();
        }


        $dataFilter = ArrayHelper::getValue($reportItem, 'data.filter', null);
        $filter = []; // A merged filter (but the RBAC and data filter could be null)
        if (!empty($filter)) {
            $filter[] = $dataFilter;
        }
        if (!empty($rbacFilter) && is_array($rbacFilter)) {
            $filter[] = $rbacFilter;
        }
//        \Yii::debug("The data provider filter is: " . VarDumper::export($filter));
//        \Yii::debug("The searchParams are: " . VarDumper::export($searchParams));
        $dataProvider = $model->search($searchParams, null, $filter ? $filter : null, $this->dataProviderConfig);

        if (is_callable(ArrayHelper::getValue($reportItem, 'data.generateConfigFunction', null))) {
            // Call the generateConfigFunction
            $config = ArrayHelper::merge($config, $reportItem['data']['generateConfigFunction']($model, $modelReport, $dataProvider));
            \Yii::debug("Ran the generateConfigFunction");
        }

        if (is_callable(ArrayHelper::getValue($reportItem, 'data.generateDataFunction', null))) {
            $config['data'] = $reportItem['data']['generateDataFunction']($model, $modelReport, $dataProvider, $config);
            \Yii::debug("Ran the generateDataFunction");
        } else {
            $config['data'] = $this->generateData($reportItem, $model, $modelReport, $dataProvider, $config);
            \Yii::debug("Ran this->generateData on the reportItem");
        }

        // -- Formatter
        if (is_callable(ArrayHelper::getValue($reportItem, 'data.formatter', null))) {
            $config['data'] = ArrayHelper::getValue($reportItem, 'data.formatter')($config['data']);
        }

        // -- Allow the model report (e.g Table) to do any special processing
        if (method_exists($modelReport, 'processConfigAndDataResponse')) {
            $config = $modelReport->processConfigAndDataResponse($config, $reportItem);
        }

        return $config;
    }

    /**
     * @param $reportItem
     * @param $model
     * @param $modelReport
     * @param $dataProvider ActiveDataProvider
     * @param $config array
     * @return array|int|\MongoDB\Driver\Cursor|null
     * @throws \yii\mongodb\Exception
     */
    public function generateData($reportItem, $model, $modelReport, $dataProvider, $config = ['data' => null])
    {
        // Create report queries built as AND queries that combine:
        //
        // - Current search query (without the “assigned to” value) -- pulled from \Yii::$app->get()?
        // - Support customisation per report item (come from $model->reportItems()[$reportItemName]
        // - Support setting default search filters (e.g default deal date or delivered date to this month) -- this will be part of the customisation per report item in the model
        $filter = ArrayHelper::getValue($reportItem, 'data.filter', null); // A prefilter query, e.g only getting entries in the last x days
        $query = ArrayHelper::getValue($reportItem, 'data.query', null);
        $aggregationPipeline = ArrayHelper::getValue($reportItem, 'data.aggregationPipeline', []);


        if (!empty($query)) {

            // Based on yii2/data/ActiveDataProvider.php::prepareTotalCount()
            $dataProviderQuery = clone $dataProvider->query;
            $dataProviderQuery->limit(-1)->offset(-1)->orderBy([]);
            $dataProviderQuery->andFilterWhere($query);
            if (!empty($filter)) {
                $dataProviderQuery->andFilterWhere($filter);
            }
            return (int)$dataProviderQuery->count('*', $dataProvider->db);
        }

        if (!empty($aggregationPipeline)) {
            $aggregationPipeline = $this->addAggregationMatchPipelineFromDataProvider($model, $aggregationPipeline, $dataProvider, $filter);
            $collection = \Yii::$app->mongodb->getCollection($model::collectionName());
            return $collection->aggregate($aggregationPipeline);
        }

        // No Query, no Aggregation pipeline and to get to this method means it also doesn't have a data.generateDataFunction
        return $config['data'];
    }

    /**
     * Will add a $match pipeline to the aggregationPipeline
     * @param $aggregationPipeline array
     * @param $dataProvider ActiveDataProvider
     * @param null|array $matchQuery
     * @return mixed
     * @throws BaseException
     */
    public function addAggregationMatchPipelineFromDataProvider($model, $aggregationPipeline, $dataProvider, $matchQuery = null)
    {
        $match = $this->getAggregationMatchPipelineFromDataProvider($model, $dataProvider, $matchQuery);

        if (empty($aggregationPipeline)) {
            // There's probably something wrong if this is the case
            $aggregationPipeline = [];
        }
        \Yii::debug("The match is: " . VarDumper::export($match));
        if (!empty($match)) {
            // -- Add it as the first pipeline
            array_unshift($aggregationPipeline, $match);
        }
        return $aggregationPipeline;
    }

    /**
     * @param $dataProvider ActiveDataProvider
     * @param $model Model
     * @param $matchQuery null|array An optional query that will be added into the $match, along with the Search and RBAC filters
     * @return array|null
     * @throws BaseException
     */
    public function getAggregationMatchPipelineFromDataProvider($model, $dataProvider, $matchQuery = null)
    {
        if (empty($dataProvider) || !isset($dataProvider->query)) {
            throw new BaseException('Programming Error: Expected a Data Provider'); // Nope, you are using it wrong
        }


        $dataProviderQuery = clone $dataProvider->query;

        // We need the model because we need to get the RBAC rules (if they apply)
        // The $modelFilter returns true, false or an array with the RBAC policies
        $modelFilter = \Yii::$app->rbac->can($model, 'find', []);
        \Yii::debug("The modelFilter is " . VarDumper::export($modelFilter));
        if (false === $modelFilter) {
            throw new BaseException('You are unable to access this');
        } else if (is_array($modelFilter)) {
            // It's an array of filters to be applied to the query, e.g
            // ['OR', ['$and' => [['dealType' => ['$in' => ['used', 'wholesale',],],], ['dealershipId' => unserialize('C:21:"MongoDB\\BSON\\ObjectId":48:{a:1:{s:3:"oid";s:24:"5f3aa890c5ff1f12435c6463";}}'),],],],];
            $dataProviderQuery->andFilterWhere($modelFilter);
        }


        if (!empty($matchQuery)) {
            $dataProviderQuery->andFilterWhere($matchQuery);
        }
        // The $dataProviderQuery->where would be: [ 'and', [ 'AND', [ 'clientId' => [ 'oid' => '5f356e4fc5ff1f292434652b', ], ], [ 'dealershipId' => [ 'oid' => '5f3aa813c5ff1f12435c6462', ], ], [ 'AND', [ '>=', 'testDriveStarted', 1612099800, ], [ '<=', 'testDriveStarted', 1614518940, ], ], ], [ 'timedOut' => true, ], ]
        // After buildCondition is it now something like // e.g [ '$and' => [ [ '$and' => [ [ 'clientId' => [ 'oid' => '5f356e4fc5ff1f292434652b', ], ], [ 'dealershipId' => [ 'oid' => '5f3aa813c5ff1f12435c6462', ], ], [ '$and' => [ [ 'testDriveStarted' => [ '$gte' => 1612099800, ], ], [ 'testDriveStarted' => [ '$lte' => 1614518940, ], ], ], ], ], ], [ 'timedOut' => true, ], ], ]

        \Yii::debug('$dataProviderQuery: ' . VarDumper::export(ArrayHelper::toArray($dataProviderQuery)));
        $whereQuery = ArrayHelper::getValue(ArrayHelper::toArray($dataProviderQuery), 'where', []);
        if (empty($whereQuery)) {
            // There's somehow no RBAC nor Search query to apply, so no $match
            return null;
        }

        $where = \Yii::$app->mongodb->getQueryBuilder()->buildCondition($whereQuery);

        // as per yii2-mongodb/src/QueryBuilder.php  "if you have other columns (than _id), containing [[\MongoDB\BSON\ObjectID]], you should take care of possible typecast on your own.
        // So we need to convert from ['oid' =>  '5f3aa813c5ff1f12435c6462'] into new ObjectID( '5f3aa813c5ff1f12435c6462' )
        $match = ['$match' => $this->ensureMongoIds($where)];
//        \Yii::debug('$match with EnsuredIds: ' . VarDumper::export($match));
        return $match;
    }

    public function ensureMongoIds($queryArray)
    {
        if (empty($queryArray)) {
            return $queryArray;
        }

        // -- If it's a single array entry e.g [ 'oid' => '5f356e4fc5ff1f292434652b' ]
        if (!empty(ArrayHelper::getValue($queryArray, 'oid')) && count(array_keys($queryArray)) === 1) {
            return \Yii::$app->t::ensureId($queryArray['oid']);
        }

        foreach ($queryArray as $key => $value) {
            if (is_array($value)) {
                // Recursively call it
                $queryArray[$key] = $this->ensureMongoIds($value);
            }
        }
        return $queryArray;
    }


    /**
     * @param null $selector
     * @return array|mixed
     *
     * Adds a default 'colours' gradient which is each entry for the $this->colours array
     * If the selector is null then all colour gradients are returned.
     */
    public function getColourGradients($selector = null)
    {

        if (null === $selector || 'colours' === $selector) {
            // Add the 'colours' gradient which is all of the $this->colours
            if (!isset($this->colourGradients['colours'])) {
                $colours = array_values($this->colours);
                if ('colours' === $selector) {
                    return $colours;
                }
                return ArrayHelper::merge($this->colourGradients, $colours);
            }
        }
        // It's not the default 'colours' gradient of all of them so return whatever has been selected
        return $this->colourGradients[$selector];
    }

    /**
     * @param $colourIndex int
     * @param null|int $coloursTotal
     * @return string|JsExpression
     *
     * Allows you to use an integer colour index
     * If you are selecting less coloursTotal than there are in the $this->colours then is uses those directly
     * Otherwise it returns a Javascript expression which attempts to use d3 interpolateRGB
     */
    public function selectColourJs($colourIndex, $coloursTotal = null)
    {
        if (empty($this->coloursCount)) {
            $this->coloursCount = count($this->colours);
        }

        if (empty($coloursTotal)) {
            $coloursTotal = $this->coloursCount - 1;
        }

        if ($coloursTotal < $this->coloursCount) {
            // Use an existing colour directly
            return array_values($this->colours)[$colourIndex]; // Return the colour at that index e.g rgb(238, 168, 149)
        } else {
            return new JsExpression("getColour($colourIndex, $coloursTotal)"); // Use the JS getColour method which likely calls _reportManager.getColour which then calls the d3 interpolateRGB d3.piecewise(d3.interpolateRgb, on the colours gradient (or whichever gradient was supplied)
        }
    }

    /**
     * Get Default Report Items Layout
     * @param $reportItems array
     * @param null|Model $model
     * @return array
     *
     * If you don't have a layout provided, create one
     *
     * Example response:
     *
     * [
     *  ['report-item-name', 'other-report-item-name', '3rd-report-item-name'], // Row 1
     *  ['4th-report-item', 'some-panel', 'maybe-a-table'], // Row 2
     *  ['ohh-and-this', ], // Row 3
     * ]
     */
    public function getDefaultReportItemsLayout($reportItems, $model = null)
    {
        if (empty($reportItems)) {
            return [];
        }

        $reportItemsLayout = []; // What we return
        $reportItemsAsKeysArray = array_keys($reportItems); // e.g [0 => 'report-item-name']
        $reportItemsLayoutRow = [];
        foreach (range(0, count($reportItems) - 1) as $reportItemIndex) {

            $reportItemName = $reportItemsAsKeysArray[$reportItemIndex];
            $reportItemsLayoutRow[] = $reportItemName;
            if (count($reportItemsLayoutRow) >= 3) {
                $reportItemsLayout[] = $reportItemsLayoutRow;
                $reportItemsLayoutRow = []; // Reset to an empty array
            }
        }

        if (!empty($reportItemsLayoutRow)) {
            // If you haven't completed the row
            $reportItemsLayout[] = $reportItemsLayoutRow;
        }
//        \Yii::debug("getDefaultReportItemsLayout() is returning: " . VarDumper::export($reportItemsLayout));

        return $reportItemsLayout;
    }

    /**
     * @param $reportItemsLayout
     * @param $reportItems
     * @param $model Model
     * @return array
     */
    public function convertReportItemsLayoutToWidgetMode($reportItemsLayout, $reportItems, $model)
    {
        // Need the widgetClass, reportItemName and any custom configuration
        $reportItemWidgetDetails = [];
        foreach ($reportItemsLayout as $rowIndex => $rowItem) {
            $rowEntries = [];
            $colTotal = 0; // Should be a max of 12
            $colSizeEntriesNotSet = 0;
            foreach ($rowItem as $entryIndex => $entryItem) {

                // Sometimes the $reportItemsLayout is
                /*
                 * [['test-drives-by-user', 'test-drives-by-mode']]
                 *
                 * Or it can include widget overrides
                 * ['test-drives-by-user' => ['class' => 'some-overrides here'], 'test-drives-by-mode' => 'more overrides']
                 */
                if (is_string($entryIndex)) {
                    $reportItemName = $entryIndex;
                    $reportItemConfig = $entryItem;
                } else {
                    $reportItemName = $entryItem;
                    $reportItemConfig = [];
                }


                // Find the $reportItem
                $reportItem = ArrayHelper::getValue($reportItems, $reportItemName, null);
                if (empty($reportItem)) {
                    \Yii::error("Can't find a ReportItem with the name $reportItemName for model " . get_class($model)); // \Yii::$app->t::getModelClassName());
                    throw new BaseException("Can't find a Report Item with the name $reportItemName for model " . get_class($model) . '::reportItemsLayout()');
                }

                if (empty($reportItem['widgetClass'])) {
                    \Yii::error("The ReportItem $reportItemName " . get_class($model) . " doesn't have the widget class specified"); // \Yii::$app->t::getModelClassName());
                    throw new BaseException("Invalid config for the report item $reportItemName on model " . get_class($model) . '::reportItemsLayout()');
                }


                $colSize = $this->getColSize(ArrayHelper::getValue($reportItemConfig, 'options.class', ''));
                if ($colSize > 0) {
                    $colTotal += $colSize;
                    // e.g $colMatches = [
                    //    0 => 'col-md-5',
                    //    1 => '5',
                    // ]

                    if ($colTotal > 12) {
                        throw new BaseException("The col-md-X classes for row $entryIndex should not total more than 12, check config for $reportItemName and others in the row. Model: " . get_class($model) . "::reportItemsLayout()");
                    }
                } else {
                    $colSizeEntriesNotSet++;
                }

                $reportItemConfig = ArrayHelper::merge($reportItemConfig, [
                    'widgetClass' => $reportItem['widgetClass'],
                    'widgetClassTwig' => str_replace('\\', '.', $reportItem['widgetClass']),
                    'widgetConfig' => ArrayHelper::getValue($reportItem, 'widgetConfig'),
                    'reportItemName' => $reportItemName,
                ]);

                $rowEntries[] = $reportItemConfig;
            }


            // -- Check if we need to set the size on some of these
            if ($colSizeEntriesNotSet > 0) {

                // -- Make sure there's space
                if ($colTotal === 12 || $colTotal + $colSizeEntriesNotSet >= 12) {
                    throw new BaseException("The col-md-X classes for row $entryIndex totals $colTotal but there's {$colSizeEntriesNotSet} entries still needing to fit in. Please make space for them or move them to another row,  Model: " . get_class($model) . '::reportItemsLayout()');
                }

                // -- Workout what column size
                $colSizeRemaining = 12 - $colTotal;
                $colSizePerEntry = floor($colSizeRemaining / $colSizeEntriesNotSet); // e.g 12 / 3 = col-md-4, where as 7 items or more in a row will be floored down to col-md-1 (as 12 / 7 = 1.71)
//                \Yii::debug("Adding col-md-{$colSizePerEntry} to the $colSizeEntriesNotSet entries without colSizes in row $entryIndex without an entry out of " . count($rowEntries) . " entries in the row as there's $colSizeRemaining column size remaining. Model: " . get_class($model) . '::reportItemsLayout()');

                // -- Add the column size, if not set
                foreach ($rowEntries as $entryIndex => $entryItem) {
                    $colSize = $this->getColSize(ArrayHelper::getValue($entryItem, 'options.class', ''));
                    if ($colSize < 1) {
                        // If not set, add the $colSizePerEntry
                        $rowEntries[$entryIndex] = ArrayHelper::merge(
                            $entryItem,
                            ['options' => ['class' => trim(ArrayHelper::getValue($entryItem, 'options.class', '') . " col-md-{$colSizePerEntry}")]]
                        );
                    }
                }
            }
            $reportItemWidgetDetails[] = $rowEntries;
        }
        return $reportItemWidgetDetails;
    }

    /**
     * @param $cssClass
     * @return false|int
     *
     * Get the col-md-X column size, if defined
     */
    public function getColSize($cssClass)
    {
        if (1 === preg_match('/col-md-([0-9]+)/', $cssClass, $colMatches)) {
            return $colMatches[1];

            // e.g $colMatches = [
            //    0 => 'col-md-5',
            //    1 => '5',
            // ]
        }
        return false;
    }
}
