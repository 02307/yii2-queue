<?php

namespace zhuravljov\yii\queue;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;

/**
 * Class Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends Component implements BootstrapInterface
{
    const EVENT_ON_PUSH = 'onPush';
    const EVENT_ON_POP = 'onPop';
    const EVENT_ON_RELEASE = 'onRelease';

    /**
     * @var Driver|array|string
     */
    public $driver = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->driver = Yii::createObject($this->driver, [$this]);
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            // Finds queue component id and adds console command
            foreach ($app->getComponents(false) as $id => $component) {
                if ($component === $this) {
                    $app->controllerMap[$id] = [
                        'class' => Command::class,
                        'queue' => $this,
                    ];
                    break;
                }
            }
        }
    }

    /**
     * @param Job $job
     */
    public function push(Job $job)
    {
        $this->driver->push($job);
        $this->trigger(self::EVENT_ON_PUSH, new Event(['job' => $job]));
    }

    /**
     * @param boolean $throw
     * @return boolean
     * @throws
     */
    public function work($throw = true)
    {
        if ($this->driver->pop($message, $job)) {
            $this->trigger(self::EVENT_ON_POP, new Event(['job' => $job]));
            try {
                /** @var Job $job */
                $job->run($this);
            } catch (\Exception $e) {
                if ($throw) {
                    throw $e;
                } else {
                    Yii::error($e, __METHOD__);
                }
            } finally {
                $this->driver->release($message);
                $this->trigger(self::EVENT_ON_RELEASE, new Event(['job' => $job]));
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Purges the queue
     */
    public function purge()
    {
        $this->driver->purge();
    }
}