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
        return isset($_GET[$paramName]) ? $_GET[$paramName] : null;
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
     * @return PDO
     */
    private function dbHandler()
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
                $db = new PDO("mysql:host={$this->config['db']['host']};"
                    . "port={$this->config['db']['port']};"
                    . "dbname={$this->config['db']['name']};"
                    . 'charset=utf8;', $this->config['db']['user'], $this->config['db']['pass']
                    , array(PDO::ATTR_PERSISTENT => true));

                if (!$db) {
                    $this->error('数据库连接失败:' . json_encode($this->config['db']));
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
     * @param $msg
     */
    protected function error($msg)
    {
        throw new MiniFrameworkException($msg);
    }

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

    protected function isCurrentRequest($controller, $action)
    {
        return 0 === strcasecmp($this->controllerName, $controller) && 0 === strcasecmp($this->actionName, $action);
    }

    /**
     * Parse xml to array, with root key
     * @param string $xml
     * @return mixed
     */
    protected function xml2array($xml)
    {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = $this->xml2array($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    protected function outputJSON($status, $content)
    {
        @header('Content-type: application/json');
        echo json_encode(array('status'=>$status, 'content'=>$content));
    }
}