![Banner](./docs/img/banner.png)

# Queue Bouncer for Craft CMS

A Craft CMS plugin that prevents duplicate jobs from piling up in the queue. If a matching job is already pending or running, Queue Bouncer skips the callback silently.

## The Problem

Cron-triggered jobs can overlap. For example if a regular FeedMe import takes longer than its cron interval, a second job gets queued before the first finishes. Over time this creates a backlog that compounds the problem.

Queue Bouncer sits in front of your cron commands and acts as a gatekeeper - only running the callback function if there are no matching jobs in the queue.

## Configuration

Copy `config.php` to `config/queuebouncer.php` and define your guarded jobs:

```php
// example configuration
return [
    'feed-me-import' => [
        'jobClasses' => [
            \craft\feedme\queue\jobs\FeedImport::class,
        ],
        'callback' => function () {
            $feed = FeedMe::getInstance()->getFeeds()->getFeedById(1);
            if ($feed) {
                Queue::push(new FeedImport(['feed' => $feed]));
            }
        },
    ],
];
```

Each top-level key is an identifier you pass to the console command. A config entry can have:

| Key | Description |
|-----|-------------|
| `jobClasses` | Array of fully-qualified queue job class names to check. The callback is skipped if **any** of these are pending or running. |
| `callback` | PHP callable invoked when the bouncer gives the green light. Omit it if you'd rather chain commands with `&&` in cron. |

## Usage

Replace your existing cron command with the Queue Bouncer equivalent:

```bash
# Before
php craft feed-me/feeds/queue 1

# After
php craft queue-bouncer/queue feed-me-import
```

Queue Bouncer will:
1. Check the Craft queue for any pending or in-progress jobs matching the configured `jobClasses`.
2. If a match is found, exit (no duplicate queued).
3. If the queue is clear, invoke the `callback`.

## License

MIT
