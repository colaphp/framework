<?php

namespace Cola\Queue\Redis\Process;

use Cola\Container\Container;
use Cola\Queue\Redis\Client;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Class Consumer
 * @package Cola\Queue\Redis\Process
 */
class Consumer
{
    /**
     * @var string
     */
    protected $_consumerDir = '';

    /**
     * StompConsumer constructor.
     * @param string $consumer_dir
     */
    public function __construct($consumer_dir = '')
    {
        $this->_consumerDir = $consumer_dir;
    }

    /**
     * onWorkerStart.
     */
    public function onWorkerStart()
    {
        $dir_iterator = new RecursiveDirectoryIterator($this->_consumerDir);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            if (is_dir($file)) {
                continue;
            }
            $fileinfo = new SplFileInfo($file);
            $ext = $fileinfo->getExtension();
            if ($ext === 'php') {
                $class = str_replace('/', "\\", substr(substr($file, strlen(base_path())), 0, -4));
                if (is_a($class, 'Cola\Queue\Redis\Consumer', true)) {
                    $consumer = Container::get($class);
                    $connection_name = $consumer->connection ?? 'default';
                    $queue = $consumer->queue;
                    $connection = Client::connection($connection_name);
                    $connection->subscribe($queue, [$consumer, 'consume']);
                }
            }
        }
    }
}
