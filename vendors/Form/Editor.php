<?php

/**
 * Description of Editor
 *
 * @author riekiquan
 *
 * @property string $placeholder
 * @property integer $width
 * @property integer $height
 * @property string $stylesheets
 * @property string $editor_path
 * @property string $upload_url
 */
class Editor extends FormElementCommon
{

  private $placeholder,
    $width,
    $height,
    $stylesheets = '[]';

  public function __set($name, $value)
  {
    if (in_array($name, array('placeholder', 'width', 'height', 'stylesheets', 'editor_path', 'upload_url'))) {
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

    $html = "<label>{$this->getLabel()}</label>";
    $html .= "<textarea id=\"{$id}\" class=\"form-control {$this->getClass()}\" name=\"{$this->name}\"";
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

    $html .= " style='";
    if ($this->width) {
      $html .= 'width:' . $this->width . 'px';
    }
    if ($this->height) {
      $html .= ($this->width ? ';' : '') . 'height:' . $this->height . 'px';
    }
    $html .= "'";

    $html .= '>';
    $html .= $this->default;

    $html .= '</textarea>';

    $html .= "<p class=\"help-block\">{$this->helper}</p>";

    $script = <<<EOF
{$this->getInitCallback()}
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $.trim($('#{$id}').val());
}});
$('#$id').xheditor({
  editorRoot: '{$this->editor_path}',
  upImgUrl:"{$this->upload_url}",
  upImgExt:"jpg,jpeg,png",
  skin:'nostyle',
  tools:'Cut,Pastetext,|,Blocktag,Fontface,FontSize,Bold,Italic,Underline,Strikethrough,FontColor,BackColor,Removeformat,|,Align,List,Outdent,Indent,|,Link,Unlink,Anchor,Img,Hr,Emot,Table,|,Source,Preview,Fullscreen',
});
/*$('#$id').wysihtml5({
    "stylesheets":  $this->stylesheets,
    "font-styles":  true, //Font styling, e.g. h1, h2, etc. Default true
    "color":        true, //Button to change color of font
    "emphasis":     true, //Italics, bold, etc
    "textAlign":    true, //Text align (left, right, center, justify)
    "lists":        true, //(Un)ordered lists, e.g. Bullets, Numbers
    "blockquote":   true, //Button to insert quote
    "link":         true, //Button to insert a link
    "table":        true, //Button to insert a table
    "image":        true, //Button to insert an image
    "video":        true, //Button to insert video
    "html":         false //Button which allows you to edit the generated HTML
});*/
EOF;

    return array($html, $script);
  }

}
