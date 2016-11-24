<?php

/**
 * Description of File
 *
 * @author riekiquan
 * @property boolean $autoUpload
 * @property boolean $multiple
 */
class File extends FormElementCommon {

  private $autoUpload = FALSE,
    $multiple = FALSE;

  public function __set($name, $value)
  {
    if (in_array($name, array('autoUpload', 'multiple'))) {
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

    $html = "<label>{$this->getLabel()}";
    $html .= "<input type=\"file\"";
    $html .= " data-form-role=\"element\"";
    $html .= " id=\"{$id}\"";
    $html .= " data-helper=\"" . htmlentities($this->helper) . "\"";
    $html .= " class=\"{$this->getClass()}\"";

    if (TRUE === $this->isDisabled) {
      $html .= " disabled";
    }

    $rules = $this->getRules();
    if ($rules) {
      $rules = htmlentities(json_encode($rules));
      $html .= " data-rules=\"$rules\"";
    }

    if (TRUE === $this->multiple) {
      $html .= " multiple=\"multiple\"";
    }

    $html .= '></label>';

    $html .= "<p class=\"help-block\">{$this->helper}</p>";

    $autoUploadScript = '';
    if (TRUE === $this->autoUpload) {
      $autoUploadScript = "$('#{$id}').bind('change',function(){\$(this).closest('form').submit();});";
    }

    $script = <<<EOF
{$autoUploadScript}
{$this->getInitCallback()}
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $.trim($('#{$id}').val());
}});
EOF;

    return array($html, $script);
  }

}
