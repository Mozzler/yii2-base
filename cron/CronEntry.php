<?php

namespace mozzler\base\cron;

class CronEntry extends yii\base\Component
{

    public $scriptClass;

    public $config = [];

    public $minutes = "*";

    public $hour = "*";

    public $dayMonth = "*";

    public $month = "*";

    public $dayWeek = "*";

    public $timezone = "Australia/Adelaide";

    public $active = false;

    public $timeoutSeconds = 120; // In seconds
}