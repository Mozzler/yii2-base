<?php

namespace mozzler\base\fields;

use yii\helpers\VarDumper;

class BsonDate extends Base
{

    public $type = 'BsonDate';

    /**
     * Parse a given time value, into a MongoDB\BSON\UTCDateTime which can be used by things like Timeseries data
     * @see https://www.php.net/manual/en/mongodb-bson-utcdatetime.construct.php
     *
     */
    public function setValue($value)
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            // Return as is
            return $value;
        }


        // -- Handle a DateTime
        if ($value instanceof \DateTime) {
            // This assumes you've got the correct timezone (e.g it'll likely be stored as "Thu, 20 Nov 2014 01:03:31 +0000")
            return new \MongoDB\BSON\UTCDateTime($value);
        }

        // -- Handle a string value
        if (is_string($value)) {
            // if $value is a string containing an integer, return the integer
            if (strval(intval($value)) == $value) {
                // as per https://stackoverflow.com/a/45687447 apparently multiplying by 1000 gets the correct UTC Date Time format
                return new \MongoDB\BSON\UTCDateTime(intval($value) * 1000);
            }
            // -- If it's a date range then it'll be something like '2020-06-16 - 2020-07-07'
            $dateRangePoint = strpos($value, ' - ');
            if ($dateRangePoint !== false) {
                // Using just the first entry (from)
                $value = substr($value, 0, $dateRangePoint);
            }

            $timezone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
            return new \MongoDB\BSON\UTCDateTime(new \DateTime($value, $timezone));
        }

        // We want an integer
        return new \MongoDB\BSON\UTCDateTime(intval($value) * 1000);
    }

    // get stored value -- convert db value to application value
    public function getValue($value)
    {

        if (!empty($value) && $value instanceof MongoDB\BSON\UTCDateTime) {
            // Convert to a PHP DateTime format
            return $value->toDateTime();
        }
        return $value;
    }

}
