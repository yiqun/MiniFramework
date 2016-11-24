<?php

/**
 * Description of Select
 *
 * @author riekiquan
 * @property array $options
 * @property boolean $multiple
 */
class Select extends FormElementCommon {

  /**
   * Option
   * @var array(array('value'=>'', 'text'=>'', ...))
   */
  private $options = array(),
    $multiple = FALSE;

  public function __set($name, $value)
  {
    if (in_array($name, array('options', 'multiple'))) {
      $this->$name = $value;
    } else {
      parent::__set($name, $value);
    }
  }

  public function addOption($value, $text, $isDisabled = FALSE)
  {
    $this->options[] = array('value' => $value, 'text' => $text, 'disabled' => $isDisabled);
  }

  /**
   * Render element
   *
   * @return array(html, script)
   */
  public function render()
  {
    $id = $this->getId();

    $html = "<label>{$this->getLabel()}</label>";
    $html .= "<select id=\"{$id}\"";

    $html .= " name=\"{$this->name}\"";

    $html .= " data-form-role=\"element\"";

    if (TRUE === $this->isDisabled) {
      $html .= " disabled";
    }

    if (TRUE === $this->multiple) {
      $html .= " multiple=\"multiple\"";
    }

    $rules = $this->getRules();
    if ($rules) {
      $rules = htmlentities(json_encode($rules));
      $html .= " data-rules=\"$rules\"";
    }

    $html .= " data-helper=\"" . htmlentities($this->helper) . "\"";

    $html .= " class=\"form-control {$this->getClass()}\">";

    foreach ($this->getOptions() as $option) {
      $html .= "<option value=\"{$option['value']}\"";
      if ($this->default && $this->default === $option['value']) {
        $html .= " selected";
      }
      if (!empty($option['disabled']) && TRUE === $option['disabled']) {
        $html .= " disabled";
      }
      $html .= ">{$option['text']}</option>";
    }

    $html .= "</select>";

    $html .= "<p class=\"help-block\">{$this->helper}</p>";

    $script = <<<EOF
$("#{$id}").select2();
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $.trim($("#{$id}").val());
}});
EOF;

    return array($html, $script);
  }

  private function getOptions()
  {
    if (empty($this->options)) {
      $this->options[] = array(
        'value' => '',
        'text' => '无选项'
      );
    }

    return $this->options;
  }

}
