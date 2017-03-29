<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\sync;

use Yii;
use tests\QueueTestCase;
use zhuravljov\yii\queue\sync\Queue;

/**
 * Sync Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends QueueTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->syncQueue;
    }

    public function testRun()
    {
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->getQueue()->run();
        $this->assertJobDone($job);
    }
}