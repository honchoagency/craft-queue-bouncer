<?php

namespace honchoagency\craftqueuebouncer;

use Craft;
use craft\base\Plugin;
use honchoagency\craftqueuebouncer\services\QueueService;

/**
 * Queue Bouncer plugin
 *
 * @method static QueueBouncer getInstance()
 * @property-read QueueService $queue
 */
class QueueBouncer extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSection = false;

    public static function config(): array
    {
        return [
            'components' => [
                'queue' => QueueService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'honcho\\craftqueuebouncer\\console\\controllers';
        }
    }
}
