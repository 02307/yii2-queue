<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\redis;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\console\Controller as ConsoleController;
use yii\helpers\Console;

/**
 * Statistic Action
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class StatAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (!$this->controller instanceof ConsoleController) {
            throw new InvalidConfigException('The controller must be console controller.');
        }
        if (!($this->queue instanceof Queue)) {
            throw new InvalidConfigException('The queue must be redis queue.');
        }
    }

    /**
     * Returns redis-queue statistic.
     */
    public function run()
    {
        Console::output(Console::ansiFormat('Jobs', [Console::FG_GREEN]));

        Console::stdout(Console::ansiFormat('- reserved: ', [Console::FG_YELLOW]));
        Console::output($this->getReservedCount());

        if ($workersInfo = $this->getWorkersInfo()) {
            Console::output(Console::ansiFormat('Workers ', [Console::FG_GREEN]));
            foreach ($workersInfo as $name => $info) {
                Console::stdout(Console::ansiFormat("- $name: ", [Console::FG_YELLOW]));
                Console::output($info['addr']);
            }
        }
    }

    /**
     * @return integer
     */
    protected function getReservedCount()
    {
        return $this->queue->redis->executeCommand('LLEN', [$this->queue->channel . '.reserved']);
    }

    /**
     * @return array
     */
    protected function getWorkersInfo()
    {
        $workers = [];
        $data = $this->queue->redis->executeCommand('CLIENT', ['LIST']);
        foreach (explode("\n", trim($data)) as $line) {
            $client = [];
            foreach (explode(' ', trim($line)) as $pair) {
                list($key, $value) = explode('=', $pair, 2);
                $client[$key] = $value;
            }
            if (isset($client['name']) && strpos($client['name'], $this->queue->channel . '.worker') === 0) {
                $workers[$client['name']] = $client;
            }
        }

        return $workers;
    }
}