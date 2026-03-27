<?php

namespace honcho\craftqueuebouncer\console\controllers;

use Craft;
use craft\console\Controller;
use honcho\craftqueuebouncer\QueueBouncer;
use yii\console\ExitCode;

/**
 * Queue controller
 *
 * Checks whether a queue job is already running before invoking a callback.
 *
 * Usage:
 *   php craft _queue-bouncer/queue/run blitz-refresh-expired
 *   php craft _queue-bouncer/queue/run lantern-flush
 */
class QueueController extends Controller
{
    public $defaultAction = 'run';

    /**
     * Checks the Craft queue for any matching in-progress or pending jobs.
     * If found, skips silently. If not found, invokes the configured callback.
     */
    public function actionRun(string $key): int
    {
        $config = Craft::$app->getConfig()->getConfigFromFile('queuebouncer');

        if (!isset($config[$key])) {
            $this->stderr("Queue Bouncer: no config found for key \"$key\".\n");
            return ExitCode::CONFIG;
        }

        $entry = $config[$key];
        $jobClasses = $entry['jobClasses'] ?? [];

        if (!empty($jobClasses) && QueueBouncer::getInstance()->queue->isJobInQueue($jobClasses)) {
            $this->stdout("Queue Bouncer: skipping \"$key\" — job already in queue.\n");
            return ExitCode::OK;
        }

        if (!isset($entry['callback']) || !is_callable($entry['callback'])) {
            // No callback configured — caller can chain with && in cron.
            return ExitCode::OK;
        }

        ($entry['callback'])();
        $this->stdout("Queue Bouncer: ran callback for \"$key\".\n");
        return ExitCode::OK;
    }
}
