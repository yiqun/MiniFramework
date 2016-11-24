<?php

/**
 * Description of Button
 *
 * @author riekiquan
 * @property string $type
 * @property string loadingText
 * @property string $value
 * @property string $click
 * @property string $style
 */
class Button extends FormElementCommon {

  private $type = '',
    $loadingText = '',
    $value = '',
$click = '',
$style = '';

  public function __set($name, $value)
  {
    if (in_array($name, array('type', 'loadingText', 'value', 'click', 'style'))) {
      $this->$name = $value;
    } else {
      parent::__set($name, $value);
    }
  }

  public function render()
  {
    $id = $this->getId();
    $html = "<button id=\"{$id}\" class=\"btn {$this->class}\" type=\"{$this->getType()}\"";
    if (TRUE === $this->isDisabled) {
      $html .= " disabled";
    }
    if ($this->loadingText) {
      $html .= " data-loading-text='{$this->loadingText}'";
    }
    if ($this->style) {
      $html .= " style='{$this->style}'";
    }
    $html .= '>' . $this->getValue() . '</button>';
    $initCallback = $this->getInitCallback();
    return array($html, ($this->click? "$('#$id').bind('click', function(){{$this->click}});":'').$initCallback);
  }

  private function getType()
  {
    return $this->type ? $this->type : 'button';
  }

  private function getValue()
  {
    return $this->value ? $this->value : 'Click';
  }

}
