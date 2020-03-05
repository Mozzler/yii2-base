<?php

namespace mozzler\base\fields;

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

            $timezone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
            $epoch = (new \DateTime($value, $timezone))->format("U");

            return intval($epoch);
        }

        // We want an integer
        return intval($value);
    }

}
