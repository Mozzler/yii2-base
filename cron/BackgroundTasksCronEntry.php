<?php

namespace mozzler\base\cron;

class BackgroundTasksCronEntry extends CronEntry
{

    public $scriptClass = "mozzler\base\scripts\BackgroundTasksScript";

    public $config = [
        "limit" => 20
    ];

}