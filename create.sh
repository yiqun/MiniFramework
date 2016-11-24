#!/usr/bin/env php

<?php
$frameworkPath = __DIR__.'/';
$path = empty($argv[1])? '.': $argv[1];
$path .= '/';
exec("mkdir -p {$path}assets {$path}configs {$path}controllers {$path}docs {$path}logs {$path}templates/layouts {$path}templates/index {$path}vendors {$path}tests");

// Index file
$indexFileContent = <<<EOF
<?php
/**
 * @link http://www.x-omni.com/
 * @copyright Copyright (c) 2016 OMNI
 * @license http://www.x-omni.com/license/
 */

require '{$frameworkPath}MiniFramework.php';
EOF;

file_put_contents($path.'index.php', $indexFileContent);

// Main config
$mainConfigContent = <<<EOF
<?php
/**
 * @link http://www.x-omni.com/
 * @copyright Copyright (c) 2016 OMNI
 * @license http://www.x-omni.com/license/
 */

return array(
    'appName' => 'My Application',
    /**
     * [
     * - host
     * - port
     * - name
     * - user
     * - pass
     * ]
     */
    //'db' => require __DIR__.'/db.php',
    'layout' => 'main',
    'debug' => true
);
EOF;

file_put_contents($path.'configs/main.php', $mainConfigContent);

// Main layout
$mainLayoutContent = <<<EOF
<?php
\$pageMenuList = array(
    array('name' => 'My Application', 'controller' => 'index', 'action' => 'index'),
);
?>
<!doctype html>
<html lang="zh-CN" xml:lang="zh-CN">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \$this->config['appName'] ?></title>
    <link rel="stylesheet" href="<?= \$this->getBaseUrl() ?>/assets/todc-bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= \$this->getBaseUrl() ?>/assets/todc-bootstrap/css/todc-bootstrap.min.css">
    <link rel="stylesheet" href="<?= \$this->getBaseUrl() ?>/assets/ionicons-2.0.1/css/ionicons.min.css">
    <script src="<?= \$this->getBaseUrl() ?>/assets/js/jquery-1.12.4.min.js"></script>
    <script src="<?= \$this->getBaseUrl() ?>/assets/todc-bootstrap/js/bootstrap.min.js"></script>
    <script src="<?= \$this->getBaseUrl() ?>/assets/layer.js"></script>
</head>
<body>
<div class="container">
    <nav class="navbar navbar-toolbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                        data-target=".bs-example-toolbar-collapse-1" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a href="#" class="navbar-brand dropdown-toggle"
                   data-toggle="dropdown"><?= \$this->config['appName'] ?> <span
                        class="caret"></span></a>
                <ul class="dropdown-menu">
                    <?php foreach (\$pageMenuList as \$ml) { ?>
                        <li<?= \$this->isCurrentRequest(\$ml['controller'], \$ml['action']) ? ' class="active"' : '' ?>><a
                                href="<?= \$this->getBaseUrl() ?>/?_c_=<?= \$ml['controller'] ?>&_a_=<?= \$ml['action'] ?>"><?= \$ml['name'] ?></a>
                        </li>
                    <?php } ?>
                </ul>
            </div>

            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <?php foreach (\$pageMenuList as \$ml) { ?>
                        <li<?= \$this->isCurrentRequest(\$ml['controller'], \$ml['action']) ? ' class="active"' : '' ?>><a
                                href="<?= \$this->getBaseUrl() ?>/?_c_=<?= \$ml['controller'] ?>&_a_=<?= \$ml['action'] ?>"><?= \$ml['name'] ?></a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>
</div>
<?= \$content ?>
</body>
</html>
EOF;

file_put_contents($path.'templates/layouts/main.php', $mainLayoutContent);
file_put_contents($path.'templates/index/index.php','<div class="container"><div class="well"><?=$text?></div></div>');

// Index controller
$indexController = <<<EOF
<?php
/**
 * @link http://www.x-omni.com/
 * @copyright Copyright (c) 2016 OMNI
 * @license http://www.x-omni.com/license/
 */

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    public function actionIndex()
    {
        \$this->render('index', array('text' => 'Welcome to My Application!'));
    }
}
EOF;

file_put_contents($path.'controllers/IndexController.php', $indexController);

// Copy assets
exec("cp -r assets {$path}/");

echo 'create application successfully.'.PHP_EOL;