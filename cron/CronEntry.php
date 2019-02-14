<?php

namespace mozzler\base\cron;

use \yii\base\Component;

class CronEntry extends Component
{

    public $scriptClass;

    public $config = [];

    // These only accept '*' or a comma separated set of numbers e.g '0,1,3,5,10,15,20,50'
    // No support for ranges like '1-5' or '*/2' But update the intervalMatch function if you want them to work
    public $minutes = "*"; // Accepts 0-60

    public $hours = "*"; // Accepts 0-24

    public $dayMonth = "*"; // Accepts 1-31

    public $months = "*"; // Accepts 1-12

    public $dayWeek = "*"; // Accepts 0-6 ( 0 = Sunday, 6 = Saturday)

    public $timezone = "Australia/Adelaide";

    public $active = false; // Won't run unless true, but you can easily set it to true in the config/console.php file

    public $timeoutSeconds = 120; // In seconds


    /**
     * @param integer $utcUnixTimestamp Unixtimestamp (in seconds)
     * @return bool
     * @throws \Exception
     *
     * You can easily just call $cronEntry->shouldRunCronAtTime(); and it'll return true/false if it should run right now
     * This will check based on the current minute (rounding down)
     */
    public function shouldRunCronAtTime($utcUnixTimestamp = null)
    {
        if (false === $this->active) {
            return false;
        }
        if (null === $utcUnixTimestamp) {
            $utcUnixTimestamp = time();
        }

        // Round to the start of the current minute
        $nearestMinuteTimestamp = round(floor($utcUnixTimestamp / 60) * 60);

        // -- Deal with the timezone issues
        $dateTimeZone = new \DateTimeZone($this->timezone);
        $date = new \DateTime(null === $nearestMinuteTimestamp ? "@" . time() : "@" . $nearestMinuteTimestamp); // Timestamp is always parsed as UTC
        $date->setTimezone($dateTimeZone); // Convert to specified timezone

        // -- Test interval matches for all intervals
        $match = "OK";
        if (!$this->intervalMatch($this->minutes, $date, "i")) {
            $match = "minute";
        } else if (!$this->intervalMatch($this->hours, $date, "G")) {
            $match = "hour";
        } else if (!$this->intervalMatch($this->dayMonth, $date, "j")) {
            $match = "dayMonth";
        } else if (!$this->intervalMatch($this->months, $date, "m")) {
            $match = "months";
        } else if (!$this->intervalMatch($this->dayWeek, $date, "w")) {
            $match = "dayWeek";
        }

        if ("OK" === $match) {
            return true;
        }
        \Yii::debug("The shouldRunCronAtTime() is false because the {$match} is incorrect");
        return false;
    }

    /**
     * @param $intervalValue string
     * @param $date \DateTime
     * @param $intervalFormat string
     * @return bool
     */
    public function intervalMatch($intervalValue, $date, $intervalFormat)
    {

        if ($intervalValue === "*") {
            return true;
        }
        $intervalValues = explode(',', $intervalValue);
        foreach ($intervalValues as $intervalIndex => $interval) {
            $intervalValues[$intervalIndex] = intval($interval); // Ensure it's a whole number
        }
        $currentInterval = intval($date->format($intervalFormat));
        return in_array($currentInterval, $intervalValues);
    }
}