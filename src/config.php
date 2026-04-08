<?php

/**
 * Queue Bouncer config template
 *
 * Copy this file to config/queuebouncer.php and customise.
 *
 * Each top-level key is an identifier passed to:
 *   php craft queue-bouncer/queue/run <key>
 *
 * skipClasses — (simple) array of fully-qualified job class names.
 *               The callback is skipped if ANY of these are pending or running.
 *               Use this when the class name alone is enough to identify the job.
 *
 * skipIf      — (advanced) a callable returning true to skip the callback.
 *               Use this when you need to match on job data, e.g. to allow
 *               multiple concurrent jobs of the same class but different feeds.
 *               When present, skipClasses is ignored.
 *
 * callback    — PHP callable invoked when the bouncer passes (optional).
 *               Omit if you prefer to chain commands with && in cron.
 */
return [
    // Simple example: skip if this job class is anywhere in the queue.
    'example-simple' => [
        'skipClasses' => [
            // \my\plugin\jobs\MyJob::class,
        ],
        'callback' => function () {
            // \MyPlugin::getInstance()->myService->doSomething();
        },
    ],

    // Advanced example: allow concurrent jobs of the same class, but only
    // one per feed. skipIf receives no arguments — query whatever you need.
    'example-advanced' => [
        'skipIf' => function () {
            // return \honcho\craftqueuebouncer\QueueBouncer::getInstance()
            //     ->queue->isJobInQueue([\craft\feedme\jobs\FeedImport::class]);

            // Or write your own query:
            // $count = (new \yii\db\Query())
            //     ->from(\craft\db\Table::QUEUE)
            //     ->where(['fail' => false])
            //     ->andWhere(['like', 'job', 'FeedImport'])
            //     ->andWhere(['like', 'job', '"id";i:17'])
            //     ->count();
            // return $count > 0;
            return false;
        },
        'callback' => function () {
            // \MyPlugin::getInstance()->myService->doSomething();
        },
    ],
];
