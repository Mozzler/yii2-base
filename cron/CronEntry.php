<?php

namespace mozzler\base\cron;

use \yii\base\Component;

class CronEntry extends Component
{

    public $scriptClass;

    public $config = [];

    public $minutes = "*";

    public $hours = "*";

    public $dayMonth = "*";

    public $months = "*";

    public $dayWeek = "*";

    public $timezone = "Australia/Adelaide";

    public $active = false;

    public $timeoutSeconds = 120; // In seconds


    /**
     * @param integer $utcUnixTimestamp Unixtimestamp (in seconds)
     */
    public function shouldRunCronAtTime($utcUnixTimestamp = null)
    {
        if (false === $this->active) {
            return false;
        }

        // -- Deal with the timezone issues
        $dateTimeZone = new \DateTimeZone($this->timezone);
        $date = new \DateTime(null === $utcUnixTimestamp ? "@" . time() : "@" . $utcUnixTimestamp); // Timestamp is always parsed as UTC
        $date->setTimezone($dateTimeZone); // Convert to specified timezone
        $thisMinuteTimestamp = intval(floor(($date->getTimestamp() - $date->getOffset()) / 60) * 60);
        $dateAtCheckedTimestamp = new \DateTime('@' . $thisMinuteTimestamp);
        \Codeception\Util\Debug::debug("\n\n" . var_export(['date' => $date, 'offset' => $date->getOffset(), 'utcUnixTimestamp' => $utcUnixTimestamp, 'timestamp' => $date->getTimestamp(), 'thisMinuteTimestamp' => $thisMinuteTimestamp, 'dateAtCheckedTimestamp' => $dateAtCheckedTimestamp, 'Full dateAtCheckedTimestamp' => $dateAtCheckedTimestamp->format('r')], true) . "\n\n");


        // Test interval matches for all intervals
        $match = "OK";
        if (!$this->intervalMatch($this->minutes, $thisMinuteTimestamp, "i")) {
            $match = "minute";
        } else if (!$this->intervalMatch($this->hours, $thisMinuteTimestamp, "h")) {
            $match = "hour";
        } else if (!$this->intervalMatch($this->dayMonth, $thisMinuteTimestamp, "d")) {
            $match = "dayMonth";
        } else if (!$this->intervalMatch($this->months, $thisMinuteTimestamp, "m")) {
            $match = "months";
        } else if (!$this->intervalMatch($this->dayWeek, $thisMinuteTimestamp, "w")) {
            $match = "dayWeek";
        }

        if ("OK" === $match) {
            return true;
        }
        \Codeception\Util\Debug::debug("The shouldRunCronAtTime() is false because the {$match} is incorrect. Using " . var_export([
                'utcUnixTimestamp' => $utcUnixTimestamp,
                'thisMinuteTimestamp' => $thisMinuteTimestamp,
                '$date' => $date,
                '$dateTimeZone' => $dateTimeZone,
                'CronEntry' => $this,
            ], true));
        \Yii::debug("The shouldRunCronAtTime() is false because the {$match} is incorrect");
        return false;
    }

    public function intervalMatch($intervalValue, $timestamp, $intervalFormat)
    {

        if ($intervalValue === "*") {
            return true;
        }
        $intervalValues = explode(',', $intervalValue);
        foreach ($intervalValues as $intervalIndex => $interval) {
            $intervalValues[$intervalIndex] = intval($interval); // Ensure it's a whole number
        }
        $currentInterval = intval(date($intervalFormat, $timestamp));
        \Codeception\Util\Debug::debug("intervalMatch() currentInterval: $currentInterval = " . json_encode(in_array($currentInterval, $intervalValues)) . ", based on intervalValues: " . json_encode($intervalValues) . ", using the format: " . $intervalFormat);
        return in_array($currentInterval, $intervalValues);
    }
}