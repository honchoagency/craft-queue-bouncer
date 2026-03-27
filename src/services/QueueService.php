<?php

namespace honcho\craftqueuebouncer\services;

use craft\base\Component;
use craft\db\Table;
use yii\db\Query;

/**
 * Queue Service
 *
 * Checks whether any of the given queue job classes are currently
 * pending or in-progress in the Craft queue.
 */
class QueueService extends Component
{
    /**
     * Returns true if any of the given job classes are pending or in-progress in the queue.
     *
     * @param string[] $jobClasses Fully-qualified class names to check
     */
    public function isJobInQueue(array $jobClasses): bool
    {
        foreach ($jobClasses as $class) {
            // Extract just the short class name (e.g. "RefreshCacheJob").
            // Craft stores jobs as PHP-serialized binary; the class name appears literally:
            // O:47:"putyourlightson\blitz\jobs\RefreshCacheJob":...
            $shortName = substr($class, strrpos($class, '\\') + 1);

            // Craft's queue deletes completed jobs — every row is pending or running.
            // Exclude failed jobs (fail = true), which stay in the table.
            $count = (new Query())
                ->from(Table::QUEUE)
                ->where(['fail' => false])
                ->andWhere(['like', 'job', $shortName])
                ->count();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }
}
