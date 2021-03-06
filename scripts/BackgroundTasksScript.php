<?php

namespace mozzler\base\scripts;

/**
 * Script that locates all pending background tasks and executes them
 */
class BackgroundTasksScript extends ScriptBase
{

    // limit how many background tasks will be processed at one time
    public $limit = 10;

    public function run($task)
    {
        $task->addLog("Would be running the background tasks right now....");
        // find all background tasks that are pending
        // update each task to be "in progress" (if this fails, discard the task as it is already being processed by another worker)
        // execute the task
        // update the task as completed or errored with any logs as required
    }

}