<?php

namespace mozzler\base\cron;

class BackgroundTasks extends CronEntry
{

    public $scriptClass = "mozzler\base\scripts\BackgroundTasks";

    public $config = [
        "limit" => 20
    ];

}