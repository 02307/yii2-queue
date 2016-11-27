<?php

namespace zhuravljov\yii\queue\redis;

use yii\base\BootstrapInterface;
use yii\di\Instance;
use yii\redis\Connection;
use zhuravljov\yii\queue\Driver as BaseDriver;

/**
 * Redis Driver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
{
    /**
     * @var Connection|array|string
     */
    public $redis = 'redis';
    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Connection::class);
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->queue->id] = [
                'class' => Command::class,
                'queue' => $this->queue,
            ];
        }
    }

    /**
     * @return string
     */
    protected function getKey()
    {
        return $this->prefix . $this->queue->id;
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $message = serialize($job);
        $this->redis->executeCommand('RPUSH', [$this->getKey(), $message]);
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function work($handler)
    {
        $count = 0;
        while (($message = $this->redis->executeCommand('LPOP', [$this->getKey()])) !== null) {
            $count++;
            $job = unserialize($message);
            call_user_func($handler, $job);
        }
        return $count;
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        $this->redis->executeCommand('DEL', [$this->getKey()]);
    }
}