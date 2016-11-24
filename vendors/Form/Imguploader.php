<?php

// $('#img-upload-container').imgUploader([], {width:200,height:134,maxLimit:2});

/**
 * Description of Image uploader
 *
 * @author riekiquan
 * @property integer $width thumb width
 * @property integer $height thumb height
 * @property integer $maxLimit
 * @property string $uploadScript
 * @property string $uploadPath
 * @property string $staticPath
 * @property string $imgUrlPrefix
 */
class Imguploader extends FormElementCommon
{
  private
    $width = 120,
    $height = 90,
    $maxLimit = 5,
    $uploadScript = NULL,
    $uploadPath = NULL,
    $staticPath = NULL,
    $imgUrlPrefix = NULL;

  public function __set($name, $value)
  {
    if (in_array($name, array('width', 'height', 'maxLimit', 'uploadScript', 'uploadPath', 'staticPath', 'imgUrlPrefix')
    )) {
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
    $html .= "<div id=\"{$id}\" class=\"{$this->getClass()}\" name=\"{$this->name}\"";
    $html .= " data-form-role=\"element\"";

    $rules = $this->getRules();
    if ($rules) {
      $rules = htmlentities(json_encode($rules));
      $html .= " data-rules=\"$rules\"";
    }

    $html .= " data-helper=\"" . htmlentities($this->helper) . "\"";

    $html .= '></div>';

    $html .= "<p class=\"help-block\">{$this->helper}</p>";
    $value = $this->default ? json_encode(explode(',', $this->default)) : '[]';

    $script = <<<EOF
{$this->getInitCallback()}
W.FormElementGetDataFns = $.extend(W.FormElementGetDataFns, {"{$id}": function(){
  return $('#{$id}').imgUploader('getData');
}});
$('#$id').imgUploader($value, {
  width:{$this->width},
  height:{$this->height},
  maxLimit:'{$this->maxLimit}',
  uploadScript:'{$this->uploadScript}',
  uploadPath: '{$this->uploadPath}',
  loadingImg: '{$this->staticPath}/plugins/img-uploader/loading_24x24.gif',
  imgUrlPrefix: '{$this->imgUrlPrefix}'
});
EOF;

    return array($html, $script);
  }

}
