<?php

/**
 * Description of Textarea
 *
 * @author riekiquan
 *
 * @property string $placeholder
 * @property integer $rows
 * @property boolean $isEditor
 * @property string $emotionPath
 */
class Textarea extends FormElementCommon {

  private $placeholder,
    $rows,
    $isEditor,
    $editorLang,
$emotionPath;

  public function __set($name, $value)
  {
    if (in_array($name, array('placeholder', 'rows', 'isEditor', 'emotionPath'))) {
      $this->$name = $value;
    } elseif ($name === 'editorLang') {
      if (in_array($value, array('zh-CN'))) {
        $this->editorLang = $value;
      } else {
        throw new Exception('Available langs [zn-CN]');
      }
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
    $editorScript = '';

    $html = "<label>{$this->getLabel()}</label>";

    if (TRUE !== $this->isEditor) {
      $html .= "<textarea id=\"{$id}\" class=\"form-control {$this->getClass()}\"";
      $html .= " data-form-role=\"element\"";
      $html .= " name=\"{$this->name}\"";
      $html .= " rows=\"{$this->rows}\"";
      if (TRUE === $this->isDisabled) {
        $html .= " disabled";
      }
      if ($this->placeholder) {
        $html .= " placeholder=\"{$this->placeholder}\"";
      }
    } else {
      $html .= "<div id=\"{$id}\" class=\"{$this->getClass()}\" data-form-role=\"element\" name=\"{$this->name}\"";
      if (TRUE === $this->isDisabled) {
        $html .= " class=\"form-control\" disabled";
      }
    }



    $rules = $this->getRules();
    if ($rules) {
      $rules = htmlentities(json_encode($rules));
      $html .= " data-rules=\"$rules\"";
    }

    $html .= " data-helper=\"" . htmlentities($this->helper) . "\"";

    $html .= '>';

    if ($this->default) {
      $html .= $this->default;
    }

    if (TRUE !== $this->isEditor) {
      $html .= "</textarea>";
    } else {
      $html .= "</div>";
    }

    $html .= "<p class=\"help-block\">{$this->helper}</p>";

    if (TRUE === $this->isEditor) {
      $height = ($this->rows ? $this->rows : 7) * 14 * 2;
      $editorScript = <<<EOF
        $('#{$id}').summernote({height:{$height},placeholder:"请输入文字",lang:"zh-CN",emotionPath:"{$this->emotionPath}"});
EOF;
      if (TRUE === $this->isDisabled) {
        $editorScript .= "$('#{$id}').destroy();";
      }
      $script = <<<EOF
$editorScript
{$this->getInitCallback()}
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $.trim($('#{$id}').code());
}});
EOF;
    } else {
      $script = <<<EOF
$editorScript
{$this->getInitCallback()}
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $.trim($('#{$id}').val());
}});
EOF;
    }

    return array($html, $script);
  }

}
