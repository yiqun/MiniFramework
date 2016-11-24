<?php

/**
 * Description of Hr
 *
 * @author riekiquan
 */
class Hr extends FormElementCommon {

  public function render()
  {
    $html = "<hr class=\"{$this->class}\">";
    return array($html, NULL);
  }

}
