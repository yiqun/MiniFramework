<?php

/**
 * Description of File
 *
 * @author riekiquan
 * @property string $autoUpload
 * @property string $autoUploadCallback
 * @property boolean $multiple
 * @property string $accept
 */
class File extends FormElementCommon
{

    private $autoUpload = NULL,
        $autoUploadCallback = NULL,
        $accept = NULL,
        $multiple = FALSE;

    public function __set($name, $value)
    {
        if (in_array($name, array('autoUpload', 'autoUploadCallback', 'accept', 'multiple'))) {
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

        $html = "<input type=\"hidden\"";
        $html .= " name=\"{$this->name}\"";
        $html .= " data-form-role=\"element\"";
        $html .= " id=\"{$id}\"";
        $html .= ">";

        $html .= "<label>{$this->getLabel()}</label>";
        $html .= "<input type=\"file\"";
        $html .= " id=\"{$id}-file\"";
        //$html .= " data-helper=\"" . htmlentities($this->helper) . "\"";
        $html .= " class=\"{$this->getClass()}\"";

        if (TRUE === $this->isDisabled) {
            $html .= " disabled";
        }

        if (NULL !== $this->accept) {
            $html .= " accept=\"{$this->accept}\"";
        }

        $rules = $this->getRules();
        if ($rules) {
            $rules = htmlentities(json_encode($rules));
            $html .= " data-rules=\"$rules\"";
        }

        if (TRUE === $this->multiple) {
            $html .= " multiple=\"multiple\"";
        }

        $html .= '>';

        $html .= "<p class=\"help-block\">{$this->helper}</p>";

        $autoUploadScript = '';
        if (NULL !== $this->autoUpload) {
            $autoUploadScript = <<<EOF
$('#{$id}-file').bind('change',function(){
    var fd = new FormData(), f = $(this)[0].files[0];
    fd.append('file', f.name);
    fd.append('type', f.type);
    fd.append('data', f.slice(0, f.size));
    $.ajax({
        type: 'POST',
        url: '{$this->autoUpload}',
        data: fd,
        processData: false,
        contentType: false
    }).done(function(res) {
        if (res.status) {
            $('#{$id}').val(res.content);
        } else {
            $('#{$id}').val('');
        }
        {$this->autoUploadCallback}(res);
    });
});
EOF;
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
