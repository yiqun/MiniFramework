<?php

/**
 * Created by IntelliJ IDEA.
 * User: riekiquan
 * Date: 2016-11-4
 * Time: 下午2:23
 */

/**
 * Class MiniFrameworkException
 */
class MiniFrameworkException extends Exception
{
    /**
     * @var string $logFile
     */
    private $logFile;

    /**
     * @var string $appPath
     */
    private $appPath;

    /**
     * @var int $maxFiles
     */
    private $maxFiles = 5;

    /**
     * @var int $maxFileSize unit k, 10M default
     */
    private $maxFileSize = 10240;

    public function MiniFrameworkException($message)
    {
        global $config;

        $traces = debug_backtrace();
        $trace = array_pop($traces);
        $this->appPath = dirname($trace['file']);
        $this->logFile =  $this->appPath. '/logs/error.log';

        @header('HTTP/1.1 500');
        $message = $this->processLog($message);
        if (isset($config['debug']) && $config['debug'] === true) {
            echo $message;
        }
        //die();
    }

    /**
     * Saves log messages in files.
     * @param array $logs list of log messages
     */
    private function processLog($log)
    {
        $text = array(date('[Y-m-d H:i:s]') . ' ' . $log . PHP_EOL . 'Stack trace:');
        $traces = debug_backtrace();
        array_splice($traces, 1, 1);
        foreach ($traces as $index => $trace) {
            $text[] = '#' . ($index + 1) . ' ' . (isset($trace['file']) && isset($trace['line']) ? substr($trace['file'],
                        strlen($this->appPath)) . ' (' .
                    $trace['line'] . '): ' : '')
                . (isset($trace['class']) ? $trace['class'] . '->' : '') . $trace['function'] . '(' .
                $this->multiImplode($trace['args'], ',') . ')';
        }
        if (isset($argv)) {
            unset($argv[0]);
        }
        $text[] = 'REQUEST_URI=' . (php_sapi_name() === 'cli' && isset($argv) ? '/index.php?'
                . http_build_query($argv) : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''));
        if (isset($_SERVER['HTTP_REFERER'])) {
            $text[] = 'HTTP_REFERER=' . $_SERVER['HTTP_REFERER'];
        }
        $text[] = PHP_EOL . PHP_EOL;
        $text = implode(PHP_EOL, $text);

        $fp = @fopen($this->logFile, 'a');
        @flock($fp, LOCK_EX);
        if (@filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }

        return $text;
    }

    /**
     * Rotates log files.
     */
    private function rotateFiles()
    {
        for ($i = $this->maxFiles; $i > 0; --$i) {
            $rotateFile = $this->logFile . '.' . $i;
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxFiles) {
                    @unlink($rotateFile);
                } else {
                    @rename($rotateFile, $this->logFile . '.' . ($i + 1));
                }
            }
        }
        if (is_file($this->logFile)) {
            @rename($this->logFile, $this->logFile . '.1');
        }
    }

    /**
     * Format array output
     * @param array $array
     * @return string
     */
    private function multiImplode($array)
    {
        $ret = array();

        foreach ($array as $item) {
            if (is_array($item)) {
                $ret[] = 'Array';
            } elseif (is_object($item)) {
                $ref = new ReflectionObject($item);
                $ret[] = '(Object)' . $ref->name;
            } else {
                $strlen = mb_strlen($item);
                $ret[] = mb_substr($item, 0, 25) . ($strlen > 25 ? '...' : '');
            }
        }

        return implode(', ', $ret);
    }
}