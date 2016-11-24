<?php

/**
 * Description of Cascading selector
 *
 * @author riekiquan
 * @property string $className
 * @property integer $root
 * @property integer $depth
 * @property boolean $onlyText
 * @property string $initialText
 * @property string $getChildrenUrl
 * @property string $getParentsUrl
 * @property string $requestMethod
 * @property string $onChange
 * @property string $before
 * @property string $after
 */
class Cascadingselector extends FormElementCommon
{
  private
$className,
$root = 0,
$depth = -1,
$onlyText = FALSE,
$initialText = "请选择",
$getChildrenUrl = NULL,// "data.php?action=getChildren&parent=",
$getParentsUrl = NULL,// "data.php?action=getParents&child=",
$requestMethod = "get",
$onChange = "",
$before,
$after;//

  public function __set($name, $value)
  {
    if (in_array($name, array('className', 'root', 'depth', 'onlyText', 'initialText','getChildrenUrl',
      'getParentsUrl', 'requestMethod', 'onChange', 'before', 'after'))) {
      $this->$name = $value;
    } else {
      parent::__set($name, $value);
    }
  }

  /**
   * Render element
   *
   * @return array(html, script)
   */
  public function render()
  {
    $id = $this->getId();

    $html = "<style>#$id select{border-radius:0;margin-right:10px}</style>";
    $html .= "<label>{$this->getLabel()}</label>";
    $html .= "<div id=\"{$id}\" class=\"{$this->getClass()}\" name=\"{$this->name}\"";
    $html .= " data-form-role=\"element\"";

    $rules = $this->getRules();
    if ($rules) {
      $rules = htmlentities(json_encode($rules));
      $html .= " data-rules=\"$rules\"";
    }

    $html .= " data-helper=\"" . htmlentities($this->helper) . "\"";

    $html .= '></div>';

    $html .= "<p class=\"help-block\">{$this->helper}</p>";

    $onlyText = $this->onlyText? 'true': 'false';

    $script = <<<EOF
{$this->getInitCallback()}
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $('#$id').cascadingSelector('getValue');
}});
$('#$id').cascadingSelector({
  className: "input-sm",
  value: "{$this->default}",
  root: "{$this->root}",
  depth: "{$this->depth}",
  onlyText: $onlyText,
  initialText: "{$this->initialText}",
  getChildrenUrl: "{$this->getChildrenUrl}",
  getParentsUrl: "{$this->getParentsUrl}",
  requestMethod: "{$this->requestMethod}",
  onChange: "{$this->onChange}",
  before: "{$this->before}",
  after: "{$this->after}"
});
EOF;

    return array($html, $script);
  }

}
