<?php
/**
 * Description of Text
 *
 * @author riekiquan
 *
 * @property string $placeholder
 * @property string $beforeAddon
 * @property string $afterAddon
 * @property string $type
 */
class Text extends FormElementCommon{

  private $placeholder,
    $beforeAddon,
    $afterAddon,
    $type;

  public function __set($name, $value)
  {
    if (in_array($name, array('placeholder', 'beforeAddon', 'afterAddon', 'type'))) {
      $this->$name = $value;
    } else {
      parent::__set($name, $value);
    }
  }

  private function getType()
  {
    $type = strtolower($this->type);
    return in_array($type, array('text', 'hidden', 'password', 'email', 'phone', 'date', 'number')) ? $type : 'text';
  }

  /**
   * Render element
   *
   * @return array(html, script)
   */
  public function render()
  {
    $id = $this->getId();
    $type = $this->getType();

    if ($type !== 'hidden') {
      $html = "<label>{$this->getLabel()}</label>";
      if ($this->beforeAddon || $this->afterAddon) {
        $html .= "<div class=\"input-group\">";
      }
      if ($this->beforeAddon) {
        $html .= "<span class=\"input-group-addon\">{$this->beforeAddon}</span>";
      }
      $html .= "<input type=\"{$type}\" id=\"{$id}\" class=\"form-control {$this->getClass()}\" name=\"{$this->name}\"";
      $html .= " data-form-role=\"element\"";

      if (TRUE === $this->isDisabled) {
        $html .= " disabled";
      }

      $rules = $this->getRules();
      if ($rules) {
        $rules = htmlentities(json_encode($rules));
        $html .= " data-rules=\"$rules\"";
      }
      if ($this->placeholder) {
        $html .= " placeholder=\"{$this->placeholder}\"";
      }

      $html .= " data-helper=\"" . htmlentities($this->helper) . "\"";
      if ($this->default) {
        $html .= " value=\"{$this->default}\"";
      }


      $html .= '>';

      if ($this->afterAddon) {
        $html .= "<span class=\"input-group-addon\">{$this->afterAddon}</span>";
      }
      if ($this->beforeAddon || $this->afterAddon) {
        $html .= "</div>";
      }

      $html .= "<p class=\"help-block\">{$this->helper}</p>";

      $script = <<<EOF
{$this->getInitCallback()}
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $.trim($('#{$id}').val());
}});
EOF;
    } else {
      $html = "<input type=\"{$type}\" name=\"{$this->name}\" value=\"{$this->default}\">";
      $script = '';
    }

    return array($html, $script);
  }

}
