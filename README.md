![Banner](./docs/img/banner.png)

# Queue Bouncer for Craft CMS

A Craft CMS plugin that prevents duplicate jobs from piling up in the queue. If a matching job is already pending or running, Queue Bouncer skips the callback silently.

## The Problem

Cron-triggered jobs can overlap. For example if a regular FeedMe import takes longer than its cron interval, a second job gets queued before the first finishes. Over time this creates a backlog that compounds the problem.

Queue Bouncer sits in front of your cron commands and acts as a gatekeeper - only running the callback function if there are no matching jobs in the queue.

## Configuration

Copy `config.php` to `config/queuebouncer.php` and define your guarded jobs:

```php
// config/queuebouncer.php
return [
    'feed-me-import' => [
        'skipClasses' => [
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

Each top-level key is an identifier you pass to the console command. A config entry supports these keys:

| Key | Description |
|-----|-------------|
| `skipClasses` | Array of fully-qualified job class names. The callback is skipped if **any** are pending or running. Best for the simple case where the class name alone identifies the job. |
| `skipIf` | A callable returning `true` to skip the callback. Use this when you need finer control — for example to allow concurrent jobs of the same class but different feeds. **Takes precedence over `skipClasses` when present.** |
| `callback` | PHP callable invoked when the bouncer gives the green light. Omit it if you'd rather chain commands with `&&` in cron. |

### Advanced: `skipIf`

`skipIf` is useful when `skipClasses` is too broad. For example, if you run separate imports for multiple FeedMe feeds and want each one guarded independently:

```php
'feed-me-import-17' => [
    'skipIf' => function () {
        // Only block if feed #17 is specifically already queued.
        return (new \yii\db\Query())
            ->from(\craft\db\Table::QUEUE)
            ->where(['fail' => false])
            ->andWhere(['like', 'job', 'FeedImport'])
            ->andWhere(['like', 'job', '"id";i:17'])
            ->exists();
    },
    'callback' => function () {
        $feed = FeedMe::getInstance()->getFeeds()->getFeedById(17);
        if ($feed) {
            Queue::push(new FeedImport(['feed' => $feed]));
        }
    },
],

'feed-me-import-42' => [
    'skipIf' => function () {
        return (new \yii\db\Query())
            ->from(\craft\db\Table::QUEUE)
            ->where(['fail' => false])
            ->andWhere(['like', 'job', 'FeedImport'])
            ->andWhere(['like', 'job', '"id";i:42'])
            ->exists();
    },
    'callback' => function () {
        $feed = FeedMe::getInstance()->getFeeds()->getFeedById(42);
        if ($feed) {
            Queue::push(new FeedImport(['feed' => $feed]));
        }
    },
],
```

Both keys can run concurrently — `feed-me-import-17` won't block `feed-me-import-42`.

## Usage

Replace your existing cron command with the Queue Bouncer equivalent:

```bash
# Before
php craft feed-me/feeds/queue 1

# After
php craft queue-bouncer/queue feed-me-import
```

Queue Bouncer will:
1. Evaluate `skipIf` (if configured) — if it returns `true`, exit cleanly.
2. Otherwise check `skipClasses` — if any matching job is pending or running, exit cleanly.
3. If neither condition triggered, invoke the `callback`.

## License

MIT
