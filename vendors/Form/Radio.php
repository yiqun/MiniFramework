<?php

/**
 * Description of Radio
 *
 * @author riekiquan
 * @property array $options
 */
class Radio extends FormElementCommon {

  /**
   * Option
   * @var array(array('value'=>'', 'text'=>'', ...))
   */
  private $options = array();

  public function __set($name, $value)
  {
    if ($name === 'options') {
      $this->options = $value;
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
    $html .= "<div id=\"{$id}\"";
    $html .= " name=\"{$this->name}\"";
    $html .= " data-form-role=\"element\"";
    $html .= " data-helper=\"" . htmlentities($this->helper) . "\"";
    $html .= " class=\"{$this->getClass()}\"";

    $rules = $this->getRules();
    if ($rules) {
      $rules = htmlentities(json_encode($rules));
      $html .= " data-rules=\"$rules\"";
    }

    $html .= ">";

    foreach ($this->getOptions() as $option) {
      $html .= "<label class=\"radio-inline\"><input type=\"radio\" name=\"{$this->name}_radio\"
      value=\"{$option['value']}\"";
      if ($this->default && $this->default === $option['value']) {
        $html .= " checked";
      }
      if (!empty($option['disabled']) && TRUE === $option['disabled']) {
        $html .= " disabled";
      }
      $html .= ">{$option['text']}</label>";
    }

    $html .= "</div>";

    $html .= "<p class=\"help-block\">{$this->helper}</p>";

    $disableScript = '';
    if (TRUE === $this->isDisabled) {
      $disableScript = "$('#{$id}').find('[type=radio]').prop('disabled', true);";
    }

    $script = <<<EOF
$disableScript
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $.trim($("#{$id}").find("input[type=radio]:checked").val());
}});
EOF;

    return array($html, $script);
  }

  private function getOptions()
  {
    return $this->options;
  }

}
