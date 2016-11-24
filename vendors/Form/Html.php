<?php

/**
 * Description of Html
 *
 * @author riekiquan
 * @property string $html
 */
class Html extends FormElementCommon {
  private $html;

  public function __set($name, $value)
  {
    if (in_array($name, array('html'))) {
      $this->$name = $value;
    } else {
      parent::__set($name, $value);
    }
  }

  public function render()
  {
    $html = '';
    $label = $this->getLabel();
    if ($label) {
      $html = "<label>{$this->getLabel()}</label>";
    }
    return array($html.$this->html, NULL);
  }

}
