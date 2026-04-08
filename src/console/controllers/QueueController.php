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
 *   php craft queue-bouncer/queue/run feed-me-import
 *   php craft queue-bouncer/queue/run blitz-refresh
 */
class QueueController extends Controller
{
    public $defaultAction = 'run';

    /**
     * Checks the Craft queue for any matching in-progress or pending jobs.
     * If found, skips silently. If not found, invokes the configured callback.
     *
     * Skip logic is evaluated in this order:
     *   1. `skipIf`     — a callable returning true to skip (full custom logic)
     *   2. `skipClasses` — array of job class names; skips if any are in the queue
     *   3. `jobClasses`  — deprecated alias for `skipClasses`
     */
    public function actionRun(string $key): int
    {
        $config = Craft::$app->getConfig()->getConfigFromFile('queuebouncer');

        if (!isset($config[$key])) {
            $this->stderr("Queue Bouncer: no config found for key \"$key\".\n");
            return ExitCode::CONFIG;
        }

        $entry = $config[$key];

        if (isset($entry['skipIf']) && is_callable($entry['skipIf'])) {
            // Custom skip logic — the programmer owns the check entirely.
            if (($entry['skipIf'])()) {
                $this->stdout("Queue Bouncer: skipping \"$key\" — skipIf returned true.\n");
                return ExitCode::OK;
            }
        } else {
            // Convenience shorthand: skip if any of the listed job classes are in the queue.
            // `jobClasses` is a deprecated alias for `skipClasses`.
            $skipClasses = $entry['skipClasses'] ?? $entry['jobClasses'] ?? [];

            if (!empty($skipClasses) && QueueBouncer::getInstance()->queue->isJobInQueue($skipClasses)) {
                $this->stdout("Queue Bouncer: skipping \"$key\" — job already in queue.\n");
                return ExitCode::OK;
            }
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
