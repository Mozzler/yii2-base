# Updates

Updated the components/TwigFactory.php file for the newer way that the updated Twig does things

This might mean it's not backwards compatible.

If you want to use an older version of Yii2 and Twig then use the tag '2025-08-29th-before-twig-updates'

## Installation

Add the following to your `config/params.php`:

```
    'mozzler.base' => [
        'email' => [
            'from' => ['noreply@acme.com' => 'No reply'],
            //'replyTo' => ['support@acme.com' => 'Support'],
        ]
    ]
```

Copy the `views/layouts/emails` directory across to `views/layouts` in your application.



Script / Cron Principles
------------------------

- Always write a script
- Link a cron job to run a script
- If we need the ability run via command, create a command that runs the script

Notes on using a Widget
========================

You Need a .twig file for a Widget, should have a .php file and can have a .css and .js file.

If you don't have a .twig file you are likely to get a weird PHP Compile error
like: `Cannot declare class mozzler\base\widgets\model\common\ToggleFieldVisibility, because the name is already in use`

The minimum for the twig would be

    <{{ widget.tag }} id="{{ widget.id }}" {{ html.renderTagAttributes(widget.options) }}></{{ widget.tag }}>

You can put custom PHP into the code method e.g

    public function code($templatify = false) { 
        $config = $this->config();
        /** @var Model $model */
        $model = $config['model'];
        
        $this->outputJsData([
            // .. Info here
        ]);
    }

Have a look at the

The JS file contents will be included only once. Use the widget name and .ready or an alternative to include it in a
different spot.

JS types:

    'ready' => WebView::POS_READY,
    'begin' => WebView::POS_BEGIN,
    'end' => WebView::POS_END,
    'head' => WebView::POS_HEAD,
    'load' => WebView::POS_LOAD

Example showing how to use widgets in JS

    $('.widget-option-class').each(function() { // Put in the CSS class as defined in the defaultConfig method's ['options']['class'] 
        const id = $(this).attr('id');
        const widgetData = m.widgets[id];
        console.log(widgetData);
        
        // Custom JS Widget logic here... Run for each instance of the widget on the page, but can have custom per widget data
    });

Reports
=================

Reports are shown on the Index page of the model and the report button only appears when the reportItems() method has been specified on the model.


To use the reports (e.g Charts, Panel, Table) you need a reportItems method on the model.

You can also set the reportItemsLayout() if you don't it'll default to all the entries, 3 per row

Example /models/Deal.php :

    use mozzler\base\models\Model as BaseModel;
    class Deal extends BaseModel
    {
    
    public static $collectionName = 'app.deals';
    // modelConfig() modelFields() rbac() scenarios() behaviors() and the like to go here...

    /**
     * @returns array
     * Specify how the reports are output. If this method isn't added then they'll automatically be output 3 items per row
     */
    public function reportItemsLayout()
    {
        return [
          ['panel-total-deals','panel-deals-total-amount', 'deals-leaderboard', 'deals-by-mode' ] // They will automatically be set to the correct col-md spacing to have 4 across
          ['deals-by-mode' => ['options' => ['class' => 'col-md-8']]] // Row 2 with a single chart that's manually specified 8 out of 12 columns wide
          ['deals-by-user'] // All to itself
        ];
    }
    

    /**    
     * The main method for defining the actual report items on a model
     * @returns array
     */
    public function reportItems() {

        return [

            // -- Basic Panel example
            'panel-total-deals' => [
                'title' => 'Total Deals',
                'widgetClass' => 'mozzler\base\widgets\modelReport\items\Panel',
                'data' => [
                    // Basic query... Returns the count of documents 
                    'query' => ['_id' => ['$exists' => true]],
                ]
            ],
            // -- Aggregation example
            'panel-deals-total-amount' => [
                'title' => 'Deals Total Amount ',
                'widgetClass' => 'mozzler\base\widgets\modelReport\items\Panel',
                'widgetConfig' => [
                    'pre' => '$' // Indiciating it's a dollar amount
                ],
                'data' => [
                    // The filter will be added as a $match aggregation pipeline along with the RBAC and Search filter rules
                    'filter' => ['auctionDate' => ['$exists' => true, '$ne' => '']],
                    'aggregationPipeline' => [
                        ['$group' => [
                            '_id' => 'total',
                            'total' => ['$sum' => '$amount']]
                        ],
                    ],
                    // If you are outputting a panel it should be a single value, as this is an aggregation pipeline result we use the formatter method to get just what we want 
                    'formatter' => function ($data) {
                        $total = ArrayHelper::getValue($data, '0.total', 0);
                        if ($total > 1000) {
                            return number_format($total / 1000, 2) . 'K';
                        }
                        return number_format($total, 2);
                    }
                ]
            ],

            // -- Table Example these are more complex as you have the column function
            'deals-leaderboard' => [
                'widgetClass' => 'mozzler\base\widgets\modelReport\items\Table',
                'title' => 'Deals Leaderboard',
                'widgetConfig' => [
                    'tableClasses' => 'table table-striped',
                    'caption' => '',
                ],
                'data' => [
                    'filter' => [
                        'status' => self::STATUS_DEAL_COMPLETED,
                    ],
                    'aggregationPipeline' => [
                        [
                            '$group' => [
                                '_id' => '$clientId',
                                'dealsCount' => [
                                    '$sum' => 1,
                                ],
                                'amountTotal' => [
                                    '$sum' => '$amount',
                                ],
                                'discountTotal' => [
                                    '$sum' => '$discountAmount',
                                ],
                            ],
                        ],
                        [
                            '$sort' => [
                                'amountTotal' => -1,
                            ],
                        ],
                        [
                            '$limit' => 20,
                        ],
                        [
                            '$addFields' => [
                                'amountAvg' => [
                                    '$divide' => [
                                        '$amountTotal',
                                        '$dealsCount',
                                    ],
                                ],
                                'discountAverage' => [
                                    '$divide' => [
                                        '$discountTotal',
                                        '$dealsCount',
                                    ],
                                ],
                            ],
                        ],
                        [
                            '$lookup' => [
                                'from' => Client::$collectionName,
                                'localField' => '_id',
                                'foreignField' => '_id',
                                'as' => 'clientName',
                            ],
                        ],
                        [
                        // Convert the $lookup array into the single field we are actually looking for
                            '$addFields' => [
                                'clientName' => [
                                    '$arrayElemAt' => [
                                        '$clientName.name',
                                        0,
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // The table specific columns section 
                    'columns' => [
                        'index' => [
                            'header' => '',
                            'columnClass' => 'model-item-table-index',
                            'value' => function ($aggregationRow, $aggregationRowIndex) {
                                return $aggregationRowIndex + 1;
                            },
                        ],
                        'sales-person-assigned-to' => [
                            'header' => 'Person Assigned To',
                            'value' => 'clientName',
                        ],
                        'deals' => [
                            'header' => 'Deals',
                            'value' => 'dealsCount',
                        ],
                        'total' => [
                            'header' => 'Total <span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
                            'value' => function ($aggregationRow, $aggregationRowIndex) {
                                return '$' . number_format($aggregationRow['amountTotal']);
                            },
                        ],
                        'amount-average' => [
                            'header' => 'Amount Average',
                            'value' => function ($aggregationRow, $aggregationRowIndex) {
                                return '$' . number_format($aggregationRow['amountAvg']);
                            },
                        ],
                        'discount-average' => [
                            'header' => 'Discount Average',
                            'value' => function ($aggregationRow, $aggregationRowIndex) {
                                return '$' . number_format($aggregationRow['discountAverage']);
                            },
                        ]
                    ]
                ],
            ],



            'deals-by-mode' => [
                'widgetClass' => 'mozzler\base\widgets\modelReport\items\Chart',
                'widgetConfig' => [
                    // All the config sent to the Charts.js widget for configuring it
                    // Check https://www.chartjs.org/ for more information (the samples are especially useful)
                    'type' => 'doughnut',
                    'data' => null, // To be loaded up by the API endpoint as needed
                    'options' => [
                        'responsive' => true,
                        'legend' => [
                            'position' => 'top',
                        ],
                        'title' => [
                            // Display inside the chart
                            'display' => true,
                            'text' => 'Deals By Mode'
                        ],
                        'animation' => [
                            'animateScale' => true,
                            'animateRotate' => true
                        ]
                    ]
                ],
                'data' => [
                    // -- Key parameters for the mongo query filtering (not used)
                    // 'filter' => [],
                    // 'query' => [],
                    // 'aggregationPipeline' => [],
                    
                    // generate data via a function
                    // null by default, but if specified call the function to determine the data to return
                        'generateDataFunction' => function ($model, $modelReport, $dataProvider) {

                        // Example using the query from the data provider and set for using the count, note that we have to do some work ourselves in order to keep the RBAC and Search filtering working
                        $dataProviderQuerySilent = clone $dataProvider->query;
                        $dataProviderQuerySilent->limit(-1)->offset(-1)->orderBy([]);
                        $dataProviderQuerySilent->andFilterWhere(['mode' => self::MODE_SILENT]);

                        $dataProviderQueryOnline = clone $dataProvider->query;
                        $dataProviderQueryOnline->limit(-1)->offset(-1)->orderBy([]);
                        $dataProviderQueryOnline->andFilterWhere(['mode' => self::MODE_ONLINE]);

                        return [
                            'datasets' => [[
                                'data' => [
                                    (int)$dataProviderQuerySilent->count('*', $dataProvider->db),
                                    (int)$dataProviderQueryOnline->count('*', $dataProvider->db)],
                                'backgroundColor' => [
                                    // selectColourJs takes the index colour and the number of colours, if it's within the colours defined it uses those colours, otherwise it outputs a JS expression which uses a JS based d3 interplote function
                                    \Yii::$app->reportsManager->selectColourJs(1, \Yii::$app->reportsManager->coloursCount - 1), // The first colour
                                    \Yii::$app->reportsManager->selectColourJs(\Yii::$app->reportsManager->selectColourJs(\Yii::$app->reportsManager->coloursCount - 1), \Yii::$app->reportsManager->coloursCount - 1) // The last colour in the colours range
                                ]
                            ]],
                            'labels' => [
                                'Silent',
                                'Online',
                            ],
                        ];
                    }
                ]
            ],

          'deals-by-user' => [
                'title' => 'Deals By User', // A general title outside of the chart
                'widgetClass' => 'mozzler\base\widgets\modelReport\items\Chart',
                'widgetConfig' => [
                    // All the config sent to the chart.js widget
                    'type' => 'horizontalBar',
                    'data' => null, // To be loaded up by the API endpoint as needed
                    'options' => [
                        'scales' => [
                            'xAxes' => [
                                // Make it stacked ( as per https://www.chartjs.org/docs/latest/charts/bar.html )
                                [
                                    'stacked' => true,
                                ],
                            ],
                            'yAxes' => [
                                [
                                    'stacked' => true,
                                ],
                            ],
                        ],
                    ]
                ],
                'data' => [
                    // Example of a custom aggregation pipeline
                    'generateDataFunction' => function ($model, $modelReport, $dataProvider) {
                        /** @var $model Deals */
                        /** @var $modelReport ModelReport */
                        $maxUsers = 20;

                        // Based on https://www.chartjs.org/samples/latest/charts/bar/horizontal.html
                        // Select the top 20 users with the most silent or online deals and get a count of them as a custom aggregation pipeline
                        $dealCollection = \Yii::$app->mongodb->getCollection(Deals::collectionName());
                        $dealAggregation = [
                            [
                                '$group' => [
                                    '_id' => '$userId',
                                    self::MODE_SILENT => [
                                        '$sum' => ['$cond' => [[
                                            '$eq' => ['$mode', self::MODE_SILENT]
                                        ], 1, 0]]
                                    ],
                                    self::MODE_ONLINE => [
                                        '$sum' => ['$cond' => [[
                                            '$eq' => ['$loanType', self::MODE_ONLINE]
                                        ], 1, 0]]
                                    ],
                                ]
                            ], [
                                '$addFields' => [
                                    'total' => [
                                        '$sum' => ['$silent', '$online']
                                    ]
                                ]
                            ], [
                                '$sort' => [
                                    'total' =>
                                        -1
                                ],
                            ],
                            [
                                '$limit' => $maxUsers
                            ]];

                        // Add in the $match pipeline for the RBAC and search results, this is the important part provided by the reports manager
                        $dealAggregation = \Yii::$app->reportsManager->addAggregationMatchPipelineFromDataProvider($model, $dealAggregation, $dataProvider, 
                            [
                                // An initial $match query added to the Aggregation, along with any RBAC and search filtering, put it here so there's only a single $match pipeline stage
                                '$amount' =>  ['$gt' => 0]                            
                            ]
                            );
                        // -- Run the Aggregation
                        $dealAggregationResults = $dealCollection->aggregate($dealAggregation);
    
                        // e.g $dealAggregationResults = [ [
                        //  '_id' => ObjectId('5de5c849c5ff1f54717b4ad2'),
                        // 'silent' => 5,
                        // 'online' => 5,
                        // 'total' =>10
                        // ], ...];
    
                        // -----------------------------------------------------
                        //   Set the Labels and Data
                        // -----------------------------------------------------
                        $labels = [];
                        $datasetSilentData = [];
                        $datasetOnlineData = [];

                        // This isn't the best example, you'd be better using a MongoDB $lookup aggregation pipeline instead to get the $user's name, but should give you an example of some potential data manipulation
                        foreach ($dealAggregationResults as $aggregationIndex => $aggregationResult) {
                            // -- Lookup the user (for their name)
                            $user = \Yii::$app->t->cachedGetModel(User::class, $aggregationResult['_id']);
                            // -- Ensure it's a valid user (not null)
                            if (empty($user)) {
                                \Yii::warning("Invalid user for _id: " . VarDumper::export($aggregationResult['_id']));
                                $dealAggregationResults[$aggregationIndex] = []; // Effectively unset it
                                continue;
                            }
    
                            // -- Save the data into the form as needed
                            $labels[] = $user->name;
                            $datasetSilentData[] = $aggregationResult[self::MODE_SILENT];
                            $datasetOnlineData[] = $aggregationResult[self::MODE_ONLINE];
                        }
    
                        return [
                            'labels' => $labels,
                            'datasets' => [[
                                'label' => 'Silent',
                                'backgroundColor' => \Yii::$app->reportsManager->colours['maximum-yellow-red'],
                                'borderColor' => \Yii::$app->reportsManager->colours['deal-orange'],
                                'borderWidth' => 2,
                                'data' => $datasetSilentData
                            ], [
                                'label' => 'Online',
                                'backgroundColor' => \Yii::$app->reportsManager->colours['alice-blue'],
                                'borderColor' => \Yii::$app->reportsManager->colours['light-slate-gray'],
                                'borderWidth' => 1,
                                'data' => $datasetOnlineData
                            ]]
                        ];
                    }
                ]
            ]
        ],
        ];
     }


    }















