<?php
/**
 * @link http://www.x-omni.com/
 * @copyright Copyright (c) 2016 OMNI
 * @license http://www.x-omni.com/license/
 */

/**
 * Class Controller
 */
class Controller
{
    public $actionName, $appPath;
    protected $config;
    protected $controllerName;
    protected $layout;
    protected $title;
    private $dbTransactionBegan;

    /**
     * Controller constructor.
     * @param $controllerName
     * @param $config
     */
    public function __construct($controllerName, $config)
    {
        $this->controllerName = $controllerName;
        $this->config = $config;
        if (array_key_exists('layout', $this->config)) {
            $this->layout = $this->config['layout'];
            unset($this->config['layout']);
        }
    }

    /**
     * Run before action
     */
    public function beforeAction()
    {

    }

    /**
     * Get url param
     * @param string $paramName
     * @return mixed
     */
    protected function getParam($paramName)
    {
        if (php_sapi_name() === "cli") {
            $value = getopt($paramName.':');
        } else {
            $value = isset($_GET[$paramName]) ? $_GET[$paramName] : null;
        }

        return $value;
    }

    /**
     * Get post variable
     * @param $string $postName default null
     * @return mixed
     */
    protected function getPost($postName = null)
    {
        static $POST = NULL;
        if ($POST === NULL) {
            if ($_POST) {
                $POST = $_POST;
            } else {
                $POST = file_get_contents('php://input');
                if ($POST) {
                    // If is json format
                    if (is_string($POST) && 0 === strpos($POST, '{') && '}' === substr($POST, -1)) {
                        $POST = json_decode($POST, TRUE);
                    }
                } else {
                    $POST = array();
                }
            }
        }
        return is_string($POST) ? $POST :
            (is_array($POST) && array_key_exists($postName, $POST) ? $POST[$postName] : null);
    }

    /**
     * Query sql string
     * @param $sql
     * @return array
     */
    protected function dbQuery($sql)
    {
        $stmt = $this->dbHandler()->prepare($sql);
        if (!$stmt) {
            $error = $this->dbHandler()->errorInfo();
            $this->error('Error:' . $error[0] . $error[1] . '. Statement error is: ' . $error[2]);
        }
        $stmt->execute();
        $error = $stmt->errorInfo();
        if ($error[0] !== '00000') {
            $this->error('Error-code:' . $error[0] . '-' . $error[1] . '. Statement error is: ' . $error[2]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute sql string
     * @param $sql
     * @return string
     */
    protected function dbExecute($sql)
    {
        $stmt = $this->dbHandler()->prepare($sql);
        if (!$stmt) {
            $error = $this->dbHandler()->errorInfo();
            $this->error('Error:' . $error[0] . $error[1] . '. Statement error is: ' . $error[2]);
        }
        $res = $stmt->execute();
        $error = $stmt->errorInfo();
        if ($error[0] !== '00000') {
            $this->error('Error-code:' . $error[0] . '-' . $error[1] . '. Statement error is: ' . $error[2]);
        }
        if (stripos($sql, 'INSERT') === 0) {
            $lastId = $this->dbHandler()->lastInsertId();
            if ($lastId) {
                $res = $lastId;
            }
        }

        return $res;
    }

    /**
     * DB handler
     * @param boolean $persistent default false, work only first time
     * @return PDO
     */
    private function dbHandler($persistent = false)
    {
        static $db;
        if (!$db) {
            if (!is_array($this->config) || !array_key_exists('db', $this->config) || !is_array($this->config['db'])
                || !array_key_exists('host', $this->config['db']) || !$this->config['db']['host']
                || !array_key_exists('name', $this->config['db']) || !$this->config['db']['name']
                || !array_key_exists('user', $this->config['db']) || !$this->config['db']['user']
            ) {
                $this->error('无效的数据库配置');
            }

            try {
                if (!array_key_exists('type', $this->config['db'])) {
                    $this->config['db']['type'] = 'mysql';
                }
                if (!array_key_exists('port', $this->config['db'])) {
                    $this->config['db']['port'] = '3306';
                }
                $attrs = [PDO::ATTR_CASE => PDO::CASE_NATURAL];
                if ($persistent) {
                    $attrs[PDO::ATTR_PERSISTENT] = true;
                }
                $db = new PDO("{$this->config['db']['type']}:host={$this->config['db']['host']};"
                    . "port={$this->config['db']['port']};"
                    . "dbname={$this->config['db']['name']};"
                    . 'charset=utf8;', $this->config['db']['user'], $this->config['db']['pass']
                    , $attrs);

                if (!$db) {
                    $this->error('数据库连接失败:' . json_encode($this->config['db'], JSON_UNESCAPED_UNICODE));
                }

                $stmt = $db->prepare("SET names utf8");
                $stmt->execute();
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }

        return $db;
    }

    /**
     * Begin transaction
     */
    protected function dbBeginTransaction()
    {
        if (!$this->dbTransactionBegan) {
            $this->dbHandler()->beginTransaction();
            $this->dbTransactionBegan = true;
        }
    }

    /**
     * Commit database changes
     */
    protected function dbCommit()
    {
        if ($this->dbTransactionBegan) {
            $this->dbHandler()->commit();
            $this->dbTransactionBegan = false;
        }
    }

    /**
     * Rollback database
     */
    protected function dbRollback()
    {
        if ($this->dbTransactionBegan) {
            $this->dbHandler()->rollBack();
            $this->dbTransactionBegan = false;
        }
    }

    /**
     * Redis handler
     * @param boolean $persistent default false, work only first time
     * @return Redis
     * @throws MiniFrameworkException
     */
    protected function redisHandler($persistent = false)
    {
        static $redis;
        if (!$redis) {
            if (!is_array($this->config) || !array_key_exists('redis', $this->config)
                || !is_array($this->config['redis']) || !array_key_exists('host', $this->config['redis'])
                || !$this->config['redis']['host']
            ) {
                $this->error('无效的redis配置');
            }

            try {
                if (!array_key_exists('port', $this->config['redis'])) {
                    $this->config['redis']['port'] = '6379';
                }

                $redis = new Redis();
                if ($persistent) {
                    $redis->pconnect($this->config['redis']['host'], $this->config['redis']['port']);
                } else {
                    $redis->connect($this->config['redis']['host'], $this->config['redis']['port'], 3);
                }

                if (!$redis) {
                    $this->error('redis连接失败:' . json_encode($this->config['redis'], JSON_UNESCAPED_UNICODE));
                }

            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }

        return $redis;
    }

    /**
     * Include vendor file exclude .php
     * @param string $fileBaseName
     */
    protected function vendor($fileBaseName)
    {
        if (class_exists($fileBaseName, false)) {
            return;
        }
        if ('.php' !== substr($fileBaseName, -4)) {
            $fileBaseName .= '.php';
        }
        $fileName = $this->appPath . "/vendors/{$fileBaseName}";
        if (!file_exists($fileName)) {
            $fileName = __DIR__ . "/vendors/{$fileBaseName}";
        }
        require_once $fileName;
    }

    /**
     * Save debug log
     * @param $log
     */
    protected function debugLog($log)
    {
        $traces = debug_backtrace();
        $text = date('[Y-m-d H:i:s] ') . $traces[0]['file'] . '(' . $traces[0]['line'] . "): \n\t" . print_r($log, TRUE) . "\n\n";

        if ($this->config['debug'] === true) {
            $fp = @fopen($this->appPath . '/logs/debug.log', 'a');
            @flock($fp, LOCK_EX);
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    /**
     * Render page
     * @param string $templateFileBaseName
     * @param null $pageData
     * @param null $layoutFileBaseName
     */
    protected function render($templateFileBaseName, $pageData = null, $layoutFileBaseName = null)
    {
        if (false === strpos($templateFileBaseName, '/')) {
            $templateFileBaseName = strtolower($this->controllerName) . '/' . $templateFileBaseName;
        }
        $templateFileBaseName = 'templates/' . $templateFileBaseName . '.php';
        if (!file_exists($templateFileBaseName)) {
            throw new MiniFrameworkException('模板[' . $templateFileBaseName . ']不存在');
        }

        if ($pageData) {
            extract($pageData, EXTR_OVERWRITE);
        }

        ob_start();
        include $templateFileBaseName;
        $content = ob_get_contents();
        ob_end_clean();

        if (!$layoutFileBaseName && $this->layout) {
            $layoutFileBaseName = $this->layout;
        }
        if ($layoutFileBaseName) {
            $layoutFileBaseName = 'templates/layouts/' . strtolower($layoutFileBaseName) . '.php';
            if (!file_exists($layoutFileBaseName)) {
                throw new MiniFrameworkException('布局[' . $layoutFileBaseName . ']不存在');
            }
            ob_start();
            include $layoutFileBaseName;
            $content = ob_get_contents();
            ob_end_clean();
        }

        echo $content;

        die();
    }

    /**
     * Throw error
     * @param string $msg
     */
    protected function error($msg)
    {
        throw new MiniFrameworkException($msg);
    }

    /**
     * Get current app base url
     * @return null|string
     */
    protected function getBaseUrl()
    {
        static $baseUrl = null;
        if (null === $baseUrl) {
            if (isset($_SERVER['SCRIPT_FILENAME']) && isset($_SERVER['DOCUMENT_ROOT'])) {
                // remove document root
                $requestUri = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));
                // remove base name
                $baseUrl = substr($requestUri, 0, -strlen(basename($requestUri)) - 1);
            } else {
                $baseUrl = '';
            }
        }

        return $baseUrl;
    }

    /**
     * Check current controller if
     * @param string $controller
     * @param string $action
     * @return bool
     */
    protected function isCurrentRequest($controller, $action)
    {
        return 0 === strcasecmp($this->controllerName, $controller) && 0 === strcasecmp($this->actionName, $action);
    }

    /**
     * Output json string
     * @param string $status
     * @param null $content
     */
    protected function outputJSON($status, $content = null)
    {
        @header('Content-type: application/json');
        echo json_encode(array('status' => $status, 'content' => $content), JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Check variable value, output error if empty
     * @param mixed $var
     * @param string $msg
     */
    protected function checkEmptyErr($var, $msg)
    {
        if (empty($var)) {
            $this->outputJSON(0, $msg);
        }
    }

    /**
     * Escape sql string
     * @param string $string
     * @return string
     */
    protected function dbEscapeString($string)
    {
        $string = $this->dbHandler()->quote($string);
        if ('\'' === substr($string, 0, 1) && '\'' === substr($string, -1)) {
            $string = substr($string, 1, -1);
        }

        return $string;
    }

    /**
     * Get Controller object via controller name
     * @param string $name
     * @return mixed
     */
    protected function getController($name)
    {
        static $controllers = [];
        if (!array_key_exists($name, $controllers)) {
            $controller = $name . 'Controller';
            $controllers[$name] = new $controller($this->actionName, $this->config);
        }
        return $controllers[$name];
    }

    /**
     * Dump variable to page
     * @param string $input
     * @param bool $interrupt
     */
    protected static function dump($input, $interrupt = true)
    {
        header('Content-type: text/html; charset=utf-8');
        highlight_string("<?php\n ".var_export($input, true));
        if ($interrupt) {
            die();
        }
    }
}
