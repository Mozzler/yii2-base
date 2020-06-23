<?php

namespace mozzler\base\fields;

use yii\helpers\VarDumper;

class DateTime extends Base
{

    public $type = 'DateTime';

    /**
     * Parse a given time value, being timezone aware, into a unix
     * epoch integer
     */
    public function setValue($value)
    {
        if (!$value) {
            return null;
        }

        // -- Handle a DateTime
        if ($value instanceof \DateTime) {
            // This assumes you've got the correct timezone
            $epoch = $value->format("U");
            return intval($epoch);
        }

        // -- Handle a string value
        if (is_string($value)) {
            // if $value is a string containing an integer, return the integer
            if (strval(intval($value)) == $value) {
                return intval($value);
            }
            // -- If it's a date range then it'll be something like '2020-06-16 - 2020-07-07'
            $dateRangePoint = strpos($value, ' - ');
            if ($dateRangePoint !== false) {
                $value = substr($value, 0, $dateRangePoint); // Taking just the first entry
            }

            $timezone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
            $epoch = (new \DateTime($value, $timezone))->format("U");

            return intval($epoch);
        }

        // We want an integer
        return intval($value);
    }

    /**
     * Helper method that generates a query filter based
     *
     *
     * Example:
     *
     * [
     * 'attribute' => 'testDriveStarted',
     *
     * 'params' => [
     *  'TestDrive' => [
     *  'carType' => '',
     *  'carId' => '',
     *  'clientId' => '',
     *  'loanType' => '',
     *  'carRego' => '',
     *  'testDriveStarted' => '2020-06-23 - 2020-07-08',
     *  'testDriveEnded' => '',
     *  'testDriveElapsedTime' => '',
     *  'viewedTermsAndConditions' => '0',
     *  'createdAt' => '',
     *  'updatedAt' => '',
     *  'createdUserId' => '',
     *  'updatedUserId' => '',
     * ],
     * ],
     * 'model' => [
     *  'id' => '',
     *  '_id' => null,
     *  'name' => null,
     *  'createdAt' => '',
     *  'createdUserId' => '',
     *  'updatedAt' => '',
     *  'updatedUserId' => '',
     *  'carType' => '',
     *  'carId' => '',
     *  'clientId' => '',
     *  'loanType' => '',
     *  'carRego' => '',
     *  'testDriveStarted' => '2020-06-23 - 2020-07-08',
     *  'testDriveEnded' => '',
     *  'testDriveElapsedTime' => '',
     *  'viewedTermsAndConditions' => '0',
     * ],
     *
     * ]
     */
    public function generateFilter($model, $attribute, $params)
    {
        \Yii::debug("The generateFilter is: " . VarDumper::export(['model' => $model->toArray(), 'attribute' => $attribute, 'params' => $params]));

        //  If it's a Date Range we want to search between the two ranges
        if (strpos($model->$attribute, ' - ') !== false) {
            $dateRangeRegex = '/([\d\-]+) \- ([\d\-]+)/';
            preg_match_all($dateRangeRegex, $model->$attribute, $matches, PREG_SET_ORDER, 0);
            \Yii::debug("matches - " . VarDumper::export($matches));
            /* E.g $matches = [
                [
                    '2020-06-16 - 2020-07-07',
                    '2020-06-16',
                    '2020-07-07',
                ],
            ] */
            if (!empty($matches) && count($matches[0]) === 3) {
                // -- Parsed the Date Range
                $timezone = new \DateTimeZone(\Yii::$app->formatter->timeZone);

                $startTime = (new \DateTime($matches[0][1], $timezone))->format("U");
                $endTime = (new \DateTime($matches[0][2], $timezone))->format("U");
                return [$attribute => ['gte' => $startTime, 'lte' => $endTime]];
            }

//            $value = substr($model->$attribute, 0, $dateRangePoint); // Taking just the first entry
        }

        switch ($this->filterType) {
            case '=':
                return [$attribute => $model->$attribute];
                break;
            case 'LIKE':
                return [$attribute => ['like' => $model->$attribute]];
                break;
        }
    }

}
