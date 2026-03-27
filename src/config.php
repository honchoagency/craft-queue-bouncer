<?php

/**
 * Queue Bouncer config template
 *
 * Copy this file to config/queuebouncer.php and customise.
 *
 * Each top-level key is an identifier passed to:
 *   php craft _queue-bouncer/queue/run <key>
 *
 * jobClasses  — one or more fully-qualified queue job class names.
 *               The bouncer denies the callback if ANY of these are pending or running.
 * callback    — PHP callable invoked when the bouncer passes (optional).
 *               Omit if you prefer to chain commands with && in cron.
 */
return [
    'example-job' => [
        'jobClasses' => [
            // \my\plugin\jobs\MyJob::class,
        ],
        'callback' => function () {
            // \MyPlugin::getInstance()->myService->doSomething();
        },
    ],
];
