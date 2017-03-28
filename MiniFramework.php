<?php

/**
 * @link http://www.x-omni.com/
 * @copyright Copyright (c) 2016 OMNI
 * @license http://www.x-omni.com/license/
 */

error_reporting(E_ALL);
require __DIR__ . '/MiniFrameworkException.php';

/**
 * Class MiniFramework
 */
class MiniFramework
{

    /**
     * @var array $config
     */
    private $config;

    /**
     * @var string $appPath
     */
    private $appPath;

    /**
     * @var string $controllerKey
     */
    private $controllerKey = '_c_';

    /**
     * @var string $actionKey
     */
    private $actionKey = '_a_';

    /**
     * @var string $controller file in controllers
     */
    private $defaultController = 'index';

    /**
     * @var Controller $Controller
     */
    private $Controller;

    /**
     * @var string $action in controller class
     */
    private $defaultAction = 'index';

    /**
     * App constructor.
     * @param $config
     */
    public function __construct($config, $appPath = null)
    {
        $this->checkShortOpenTagSupport();

        if ($config && is_array($config)) {
            if (isset($config['defaultController'])) {
                $this->controller = $config['defaultController'];
                unset($config['defaultController']);
            }
            if (isset($config['defaultAction'])) {
                $this->action = $config['defaultAction'];
                unset($config['defaultAction']);
            }
            if (isset($config['controllerKey'])) {
                $this->controllerKey = $config['controllerKey'];
                unset($config['controllerKey']);
            }
            if (isset($config['actionKey'])) {
                $this->actionKey = $config['actionKey'];
                unset($config['actionKey']);
            }

            $this->config = $config;
        }

        $this->appPath = $this->getAppPath($appPath);

        spl_autoload_register(function ($class) {
            if ($class === 'Controller') {
                require __DIR__ . '/Controller.php';
            } elseif ('Controller' === substr($class, -10)) {
                $fileName = $this->appPath . '/controllers/' . $class . '.php';
                if (file_exists($fileName)) {
                    include $fileName;
                } else {
                    throw new MiniFrameworkException("无效的控制器[{$class}]");
                }
            }
        });

        set_error_handler(function ($errNo, $errStr) {
            @ob_end_clean();
            throw new MiniFrameworkException($errStr);
        });
    }

    /**
     * Run method
     */
    public function run()
    {
        $Controller = $this->getController();
        $action = $this->getActionName();
        $Controller->actionName = substr($action, 6);
        $Controller->beforeAction();
        $Controller->appPath = $this->appPath;
        $Controller->$action();
    }

    /**
     * Get controller
     * @return Controller
     */
    private function getController()
    {
        if (!$this->Controller) {
            if (php_sapi_name() === "cli") {
                $param = getopt($this->controllerKey.':');
                if ($param) {
                    $this->defaultController = $param;
                }
            } elseif (isset($_GET[$this->controllerKey]) && ($_GET[$this->controllerKey] = trim($_GET[$this->controllerKey]))) {
                $this->defaultController = $_GET[$this->controllerKey];
            }

            $ControllerName = ucfirst(strtolower($this->defaultController)) . 'Controller';
            $this->Controller = new $ControllerName($this->defaultController, $this->config);
        }

        return $this->Controller;
    }

    /**
     * Get action name
     * @return string
     * @throws MiniFrameworkException
     */
    private function getActionName()
    {
        if (php_sapi_name() === "cli") {
            $param = getopt($this->actionKey.':');
            if ($param) {
                $this->defaultAction = $param;
            }
        } elseif (isset($_GET[$this->actionKey]) && ($_GET[$this->actionKey] = trim($_GET[$this->actionKey]))) {
            $this->defaultAction = $_GET[$this->actionKey];
        }

        $this->defaultAction = 'action' . ucfirst(strtolower($this->defaultAction));

        if (!method_exists($this->Controller, $this->defaultAction)) {
            throw new MiniFrameworkException("无效的Action名[{$this->defaultAction}]");
        }

        $reflection = new ReflectionMethod($this->Controller, $this->defaultAction);
        if (!$reflection->isPublic()) {
            throw new MiniFrameworkException("Action[{$this->defaultAction}]不是public方法");
        }

        return $this->defaultAction;
    }

    private function checkShortOpenTagSupport()
    {
        ob_start();
        $a = '1';
        ?>
        <?= $a ?>
        <?php
        $content = trim(ob_get_contents());
        @ob_end_clean();
        if ($content !== '1') {
            throw new MiniFrameworkException('Please set "short_open_tag = On" in php.ini first.');
        }
    }

    private function getAppPath($appPath = null)
    {
        if ($appPath) {
            return $appPath;
        }
        $traces = debug_backtrace();
        $trace = array_pop($traces);
        $dirname = dirname($trace['file']);
        return $dirname;
    }
}

/**
 * Run scripts
 */
//$traces = debug_backtrace();
//$trace = array_pop($traces);
//unset($traces);
//$dirname = dirname($trace['file']);
//$config = require $dirname . '/configs/main.php';
//$App = new MiniFramework($dirname, $config);
//unset($config, $dirname, $trace);
//$App->run();
