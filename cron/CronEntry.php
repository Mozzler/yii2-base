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
        $date = new \DateTime(null === $utcUnixTimestamp ? "@" . time() : "@" . $utcUnixTimestamp, $dateTimeZone);
        $thisMinuteTimestamp = round(floor($date->getTimestamp() / 60) * 60);

        // Test interval matches for all intervals
        var
        $match = "OK";
        if (!$this->intervalMatch($this->minutes, $thisMinuteTimestamp, "i")) {
            $match = "minute";
        }
        if (!$this->intervalMatch($this->hours, $thisMinuteTimestamp, "h")) {
            $match = "hour";
        }
        if (!$this->intervalMatch($this->day_month, $thisMinuteTimestamp, "d")) {
            $match = "day_month";
        }
        if (!$this->intervalMatch($this->month, $thisMinuteTimestamp, "m")) {
            $match = "month";
        }
        if (!$this->intervalMatch($this->day_week, $thisMinuteTimestamp, "w")) {
            $match = "day_week";
        }
        if ("OK" === $match) {
            return true;
        }
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
        return in_array($currentInterval, $intervalValues);
    }
}