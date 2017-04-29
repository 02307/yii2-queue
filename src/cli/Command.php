<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\cli;

use yii\base\Exception;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Class Command
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Command extends Controller
{
    /**
     * @var Queue
     */
    public $queue;
    /**
     * @var boolean
     */
    public $verbose = false;

    public function init()
    {
        parent::init();
        $this->queue->messageHandler = function ($id, $message) {
            return $this->handleMessage($id, $message);
        };
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'verbose',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'v' => 'verbose',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($this->verbose) {
            $this->queue->attachBehavior('verbose', Verbose::class);
        }

        return parent::beforeAction($action);
    }

    /**
     * Executes a job.
     *
     * @param string|null $id of a message
     * @return int
     */
    public function actionExec($id = null)
    {
        return $this->queue->execute($id, file_get_contents('php://stdin')) ? 0 : 1;
    }

    /**
     * Handles message using child process.
     *
     * @param string|null $id of a message
     * @param string $message
     * @return bool
     * @throws
     * @see actionExec()
     */
    private function handleMessage($id, $message)
    {
        // Executes child process
        $cmd = strtr('{php} {yii} {queue}/exec --verbose={verbose} {id}', [
            '{php}' => PHP_BINARY,
            '{yii}' => $_SERVER['SCRIPT_FILENAME'],
            '{queue}' => $this->id,
            '{verbose}' => (int) $this->verbose,
            '{id}' => $id,
        ]);
        $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $cwd = $_SERVER['PWD'];
        $process = proc_open($cmd, $descriptors, $pipes, $cwd);
        if (is_resource($process)) {
            // Writes message to stdIn of process
            fwrite($pipes[0], $message);
            fclose($pipes[0]);
            // Reads stdOut
            Console::stdout(stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            // Reads stdErr
            Console::stderr(stream_get_contents($pipes[2]));
            fclose($pipes[2]);
            // Closes process
            $exitCode = proc_close($process);

            return $exitCode == 0;
        } else {
            throw new Exception("Cannot execute command: $cmd");
        }
    }
}