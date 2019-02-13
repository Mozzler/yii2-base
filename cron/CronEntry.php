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
}