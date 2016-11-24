<?php

/**
 * Description of Holder
 *
 * @author riekiquan
 *
 * @property string $tag
 */
class Holder extends FormElementCommon {

  private $tag;

  public function __set($name, $value)
  {
    if (in_array($name, array('tag'))) {
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
    $tag = $this->getTag();

    $html = "<label>{$this->getLabel()}</label>";
    $html .= "<{$tag} id=\"{$id}\" class=\"{$this->getClass()}\"></{$tag}>";
    $html .= "<p class=\"help-block\">{$this->helper}</p>";

    $script = <<<EOF
{$this->getInitCallback()}
EOF;
    return array($html, $script);
  }

  private function getTag()
  {
    $tag = strtolower($this->tag);
    return $tag ? $tag : 'span';
  }

}
