<?php
/**
 * @Todo compress js
 */

/**
 * Form
 *
 * PHP version 5
 *
 * @category Form
 * @package  Extensions
 * @author   Andrew Li <tinray1024@gmail.com>
 * @license  http://yiqun.github.io Andrew Li
 * @version  $Rev: 33 $
 * @link     http://yiqun.github.io
 * @modified $Author: andrew $
 * @since    $Date: 2014-02-07 16:13:51 +0800 (Fri, 07 Feb 2014) $
 *
 * @property array $elements Form elements
 * @property array $title Form title and description
 * @property string $id Form Id
 * @property string $class Form Class name
 * @property string $action Submit url
 * @property string $method Submit method
 * @property string $encType Submit enc type
 * @property string $submitType direct/ajax
 * @property string $ajaxResponseType
 * @property integer $ajaxTimeout
 * @property string $ajaxCallback
 */
class Form
{

    private
        $title = array(
        'text' => '',
        'description' => ''
    ),
        $id = '',
        $class = '',
        $action = '',
        $method = '',
        $encType = '',
        $submitType = '',
        $ajaxResponseType = '',
        $ajaxCallback = '',
        $ajaxTimeout = 10,
        $elements = array();

    /**
     * Set params
     *
     * @param string $name
     * @param mixed $value
     * @return boolean
     * @throws Exception
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->checkParamType($this->$name, $value, $name);
            // check array
            if (!is_array($value)) {
                $this->$name = $value;
            } else {
                foreach ($value as $vk => $vv) {
                    if (!isset($this->{$name}[$vk])) {
                        throw new Exception("Invalid key {$vk} in $name");
                    }
                    $this->checkParamType($this->{$name}[$vk], $vv, "$name($vk)");
                    $this->{$name}[$vk] = $vv;
                }
            }
            return TRUE;
        }

        throw new Exception("Invalid property $name");
    }

    /**
     * Check param type
     *
     * @param type $expect
     * @param type $in
     * @param type $name
     * @throws Exception
     */
    private function checkParamType($expect, $in, $name)
    {
        $expect_type = gettype($expect);
        if ($expect_type !== gettype($in)) {
            throw new Exception("Invalid property type of $name, expect $expect_type");
        }
    }

    public function addElement($element)
    {
        if (!$element instanceof FormElementCommon && !$element instanceof FormElementGroup) {
            throw new Exception('Argument 1 expect types[FormElementCommon or FormElementGroup], ' . gettype($element) . ' given');
        }
        $this->elements[] = $element;
    }

    public function render()
    {
        $html = $script = '';
        // header
        $html .= "<form id=\"{$this->getId()}\" role=\"form\" class=\"{$this->getClass()}\" action=\"{$this->getAction()}\" method=\"{$this->getMethod()}\" enctype=\"{$this->getEncType()}\" role=\"form\" submitType=\"{$this->getSubmitType()}\">";
        // title
        if ($this->title['text']) {
            $html .= "<h3 class=\"form-title\">{$this->title['text']}</h3>";
        }
        if ($this->title['description']) {
            $html .= "<p class=\"form-description\">{$this->title['description']}</p>";
        }

        // loop elements
        foreach ($this->elements as $element) {
            list($returnHtml, $returnScript) = $element->render();
            $html .= $returnHtml;
            if ($returnScript) {
                $script .= $returnScript;
            }
        }
        // @todo 添加临时表单项
        $html .= "<input type=hidden name='formData'>";
        // footer
        $html .= "</form>";

        $submitScript = $this->getSubmitScript('ajax' === $this->getSubmitType());

        // return
        return $html . <<<EOF
<script>(function(W){
  'use strict';
  if (W.jQuery === undefined) {
    throw 'jQuery library required';
  }
  var $ = W.jQuery;
  $script
}(window));
</script>
$submitScript
EOF;
    }

    private function getId()
    {
        return $this->id ? $this->id : 'form-' . uniqid();
    }

    private function getClass()
    {
        return $this->class ? $this->class : 'form-class';
    }

    private function getAction()
    {
        return $this->action;
    }

    private function getMethod()
    {
        $methods = array('GET', 'POST', 'PUT', 'DELETE');
        $this->method = strtoupper($this->method);
        return in_array($this->method, $methods) ? $this->method : $methods[0];
    }

    private function getEncType()
    {
        $encTypes = array(
            'text/plain',
            'multipart/form-data',
            'application/x-www-form-urlencoded'
        );
        $this->encType = strtolower($this->encType);
        return in_array($this->encType, $encTypes) ? $this->encType : ($this->getMethod() === 'POST' ? $encTypes[2] : $encTypes[0]);
    }

    private function getSubmitType()
    {
        $submitTypes = array(
            'ajax', 'direct'
        );
        $this->submitType = strtolower($this->submitType);
        return in_array($this->submitType, $submitTypes) ? $this->submitType : $submitTypes[0];
    }

    private function getAjaxResponseType()
    {
        $responseTypes = array('text', 'script', 'xml', 'html', 'json', 'jsonp');
        $this->ajaxResponseType = strtolower($this->ajaxResponseType);
        return in_array($this->ajaxResponseType, $responseTypes) ? $this->ajaxResponseType : $responseTypes[0];
    }

    private function getAjaxCallback()
    {
        if ($this->ajaxCallback) {
            return $this->ajaxCallback . '(res)';
        }
    }

    private function getSubmitScript($ajax = FALSE)
    {
        /*$submit = '// add input hide for !(input,select,textarea) types $(this).submit();';
        if ($ajax) {
          $submit = <<<EOF
          $.ajax({
            url: '{$this->getAction()}',
            data: '',
            method: '{$this->getMethod()}',
            dataType: '{$this->getAjaxResponseType()}',
            timeout: '{$this->getAjaxTimeout()}',
            success: function(res){
              {$this->getAjaxCallback()};
            }
          });
    EOF;
        }*/
        return '<script>' . str_replace(array("'successCallback'", '"successCallback"'), $this->getAjaxCallback(), file_get_contents(__DIR__ . '/Form.min.js')) . '</script>';
    }

    private function getAjaxTimeout()
    {
        return 1000 * ($this->ajaxTimeout ? $this->ajaxTimeout : 10);
    }

}

/**
 * Element common
 *
 * PHP version 5
 *
 * @category Form
 * @package  Extensions
 * @author   Andrew Li <tinray1024@gmail.com>
 * @license  http://yiqun.github.io Andrew Li
 * @version  $Rev: 33 $
 * @link     http://yiqun.github.io
 * @modified $Author: andrew $
 * @since    $Date: 2014-02-07 16:13:51 +0800 (Fri, 07 Feb 2014) $
 *
 * @property string $id
 * @property array $class
 * @property string $name
 * @property string $helper
 * @property string $default Default value
 * @property boolean $isDisabled
 * @property string $initCallback Function name
 * @property array $label
 */
class FormElementCommon
{

    protected
        $id,
        $class,
        $name,
        $label,
        $helper,
        $default,
        $isDisabled,
        $initCallback,
        $rules;

    public function __set($name, $value)
    {
        switch ($name) {
            case 'id':
            case 'class':
            case 'name':
            case 'label':
            case 'helper':
            case 'default':
            case 'isDisabled':
            case 'initCallback':
                $this->$name = $value;
                break;
            default :
                throw new Exception('Invalid property ' . $name);
        }
    }

    public function addRule(FormElementRule $rule)
    {
        $this->rules[] = $rule;
    }

    /**
     * Render element
     *
     * @return array(html, script)
     */
    public function render()
    {

    }

    protected function getId()
    {
        if (!$this->id) {
            $this->id = 'form-element-' . uniqid();
        }
        return $this->id;
    }

    protected function getLabel()
    {
        return $this->label;
    }

    protected function getHelper()
    {
        return "<p class=\"help-block\">{$this->helper}</p>";
    }

    protected function getClass()
    {
        return "form-element" . ($this->class ? " {$this->class}" : "");
    }

    protected function getRules()
    {
        $rules = array();
        if (is_array($this->rules)) {
            foreach ($this->rules as $rule) {
                $rules[] = $rule->toArray();
            }
        }
        return $rules;
    }

    protected function getInitCallback()
    {
        if ($this->initCallback) {
            return "({$this->initCallback})($(\"#{$this->getId()}\"));";
        }
    }

}

/**
 * @property string $rule
 * @property string $optVal
 * @property string $errMsg
 */
class FormElementRule
{

    private $rule,
        $optVal,
        $errMsg;

    public function __set($name, $value)
    {
        switch ($name) {
            case 'rule':
                $rules = array('required', 'email', 'url', 'number', 'regexp', 'minNum', 'maxNum', 'minLen', 'maxLen', 'include', 'exclude', 'range', 'unrange', 'begin', 'end');
                if (!in_array($value, $rules)) {
                    throw new Exception('Invalid rule ' . $value);
                }
                $this->rule = $value;
                break;
            case 'optVal':
                $this->optVal = $value;
                break;
            case 'errMsg':
                $this->errMsg = $value;
                break;
            default :
                throw new Exception('Invalid property ' . $name);
        }
    }

    public function toArray()
    {
        return array(
            'rule' => $this->rule,
            'optVal' => $this->optVal,
            'errMsg' => $this->errMsg
        );
    }

}

/**
 * @property string $class
 */
class FormElementGroup
{

    private $elements = array(), $class;

    public function __set($name, $value)
    {
        if ($name === 'class') {
            $this->class = $value;
        } else {
            throw new Exception('Invalid property ' . $name);
        }
    }

    public function addElement(FormElementCommon $element)
    {
        $this->elements[] = $element;
    }

    public function render()
    {
        $html = "<div class=\"form-group {$this->class}\">";
        $script = "";
        foreach ($this->elements as $element) {
            list($returnHtml, $returnScript) = $element->render();
            $html .= $returnHtml;
            if ($returnScript) {
                $script .= $returnScript;
            }
        }
        $html .= "</div>";

        return array($html, $script);
    }

}

/**
 * FormAutoloader
 */
class FormAutoloader
{

    public static function Register()
    {
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }
        return spl_autoload_register(array('FormAutoloader', 'Load'), TRUE, TRUE);
    }

    public static function Load($pClassName)
    {
        if (class_exists($pClassName, FALSE)) {
            return FALSE;
        }

        $pClassFilePath = __DIR__ . "/Form/{$pClassName}.php";

        if ((file_exists($pClassFilePath) === FALSE) || (is_readable($pClassFilePath) === FALSE)) {
            return FALSE;
        }

        require($pClassFilePath);
    }

}

FormAutoloader::Register();
