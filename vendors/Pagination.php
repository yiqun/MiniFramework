<?php

/**
 * Pagination
 *
 * PHP version 5
 *
 * @category Pagination
 * @package  Extensions
 * @author   Andrew Li <tinray1024@gmail.com>
 * @license  http://yiqun.github.io Andrew Li
 * @version  $Rev: 33 $
 * @link     http://yiqun.github.io
 * @modified $Author: andrew $
 * @since    $Date: 2014-02-07 16:13:51 +0800 (Fri, 07 Feb 2014) $
 *
 * @property array $params
 * @property string $baseUrl
 * @property integer $activePage default 1
 * @property integer $totalPage default 0
 * @property integer $total default 0
 * @property integer $offset default 10
 * @property boolean $ignoreTotalPageEqualZero default FALSE
 * @property integer $showNum default 7
 * @property string $prevHtml Previous button html
 * @property string $nextHtml Next button html
 * @property string $jumpTmpl Jump page html template
 * will replace %placeholder%, %maxlength%, %min%, %max%, %click%
 * @property string $containerId Container tag's id
 * @property string $containerClass Container class name
 * @property string $prevClass Previous item's class name
 * @property string $nextClass Next item's class name
 * @property string $indexClass Index item's class name
 * @property string $activeClass Active item's class name
 * @property string $ajaxCallback Javascript callback function
 * @property string $urlStyle Url style, available: default, pathinfo
 * @property string $pageKey Page key
 * @property boolean $hideFirstPage Hide first page
 */
class Pagination {

  private
  // Params as ?a=v1&b=v2...
    $params = array(),
    // Link base url
    $baseUrl = '',
    // Optional params
    $options = array(
      // Current active page index
      'activePage' => 1,
      // Total page index
      'totalPage' => 0,
      // Total records
      'total' => 0,
      // Show records every page
      'offset' => 10,
      // Wheter ignore totalPage equal 0
      'ignoreTotalPageEqualZero' => FALSE,
      // How many index items for show, odd number
      'showNum' => 7,
      // Previous link html
      'prevHtml' => 'Prev',
      // Next link html
      'nextHtml' => 'Next',
      // Whether show jump input & button
      'jumpTmpl' => 'Jump to <input type="number" placeholder="%placeholder%" maxlength="%maxlength%" min="%min%" max="%max%"><button onclick="%click%">Go</button>',
      // Container tag's id
      'containerId' => '',
      // Container tag's class name
      'containerClass' => 'pagination',
      // Previous item's class name
      'prevClass' => 'prev',
      // Next item's class name
      'nextClass' => 'next',
      // Index item's class name
      'indexClass' => 'index',
      // Active item's class name
      'activeClass' => 'active',
      // Javascript callback function
      'ajaxCallback' => '',
      // Url style, available: default, pathinfo
      'urlStyle' => 'default',
      // Page key
      'pageKey' => 'page',
      // Hide first page
      'hideFirstPage' => FALSE,
      // with default style
      'withDefaultStyle' => TRUE
  );

  /**
   * Flag for some code output
   *
   * @var boolean $first
   */
  public static $first = TRUE;

  /**
   * Set params
   *
   * @param type $name
   * @param type $value
   * @return boolean
   * @throws Exception
   */
  public function __set($name, $value)
  {
    if (property_exists($this, $name)) {
      $this->checkParamType($this->$name, $value, $name);
      $this->$name = $value;
      return TRUE;
    }

    if (isset($this->options[$name])) {
      $this->checkParamType($this->options[$name], $value, $name);
      $this->options[$name] = $value;
      return TRUE;
    }

    throw new \Exception("Invalid property $name");
  }

  /**
   * Check param type
   *
   * @param type $expect
   * @param type $in
   * @param type $name
   * @throws Exception
   */
  private function checkParamType($expect, $in, $name)
  {
    $expect_type = gettype($expect);
    if ($expect_type !== gettype($in)) {
      throw new \Exception("Invalid property type of $name, expect $expect_type");
    }
  }

  public function render()
  {
    if (!$this->options['ignoreTotalPageEqualZero'] && 0 === $this->getTotalPage()) {
      return '';
    }
      $defaultStyle = <<<EOF
<style>
.pagination{
    width:100%;
    text-align: center;
    width:100%;
    height:58px;
    padding:15px 0;
    margin-top:0;
    margin-bottom:0;
    border-radius:0;
}
.pagination a{
    color: #1485ec;
    display: inline-block;
    margin-left: 4px;
    height:26px;
    font-size:12px;
    line-height:24px;
    border:1px solid #c1d2f0;
    padding-left:10px;
    padding-right:10px;
    text-decoration:none;
}
.pagination a.active{
    background-color:#0fb7db;
    color:#fff;
    border-color: #0fb7db;
}
.pagination a:hover{
    background-color:#fe8f00;
    color:#fff;
    border-color: #fe8f00;
}
.pagination a.prev>i{
    margin-right:0;
}
</style>
EOF;

    return ($this->options['withDefaultStyle']? $defaultStyle:'').('' === $this->options['ajaxCallback'] ? $this->renderWithoutAjax() : $this->renderWithAjax());
  }

  private function renderWithAjax()
  {
    $uniqid = $this->getContainerId();
    $html = "<div id=\"{$uniqid}\" class=\"{$this->options['containerClass']}\"></div>";
    $html .= $this->getScript($uniqid);

    Pagination::$first = FALSE;

    return $html;
  }

  private function renderWithoutAjax()
  {
    $activePage = $this->getActivePage();
    $totalPage = $this->getTotalPage();
    $minPage = $this->getMinPage();
    $maxPage = $this->getMaxPage();
    $uniqid = $this->getContainerId();

    $html = "<div id=\"{$uniqid}\" class=\"{$this->options['containerClass']}\">";

    // prev
    $html .= "<a class=\"{$this->options['prevClass']}\" href=\"" .
      $this->getUrl($this->getPrevPage()) . "\">{$this->options['prevHtml']}</a>";

    if ($minPage <= 4) {
      $minPage = 1;
    } else {
      for ($i = 1; $i <= 2; $i++) {
        $html .= "<a class=\"{$this->options['indexClass']}\" href=\"{$this->getUrl($i)}\">$i</a>";
      }
      $html .= "<font>...</font>";
    }

    // indexes
    for ($i = $minPage; $i <= ($totalPage > 0 && $maxPage >= $totalPage - 3 ? $totalPage : $maxPage); $i++) {
      $html .= "<a class=\"{$this->options['indexClass']}" .
        (intval($i) === intval($activePage) ? " {$this->options['activeClass']}" : '') .
        "\" href=\"" . $this->getUrl($i) . "\">{$i}</a>";
    }

    if ($maxPage < $totalPage - 3) {
      $html .= "<font>...</font>";
      for ($i = $totalPage - 1; $i <= $totalPage; $i++) {
        $html .= "<a class=\"{$this->options['indexClass']}\" href=\"{$this->getUrl($i)}\">$i</a>";
      }
    }

    // next
    $html .= "<a class=\"{$this->options['nextClass']}\" href=\"" . $this->getUrl($this->getNextPage()) . "\">{$this->options['nextHtml']}</a>";

    if (0 !== $totalPage) {
      $html .= $this->getJumpHtml(FALSE);
    }

    $html .= "</div><script>(function(w){var ctn=$(\"#{$uniqid}\"); $(\"a.{$this->options['activeClass']}" .
      ($activePage === 1 ? ",a.{$this->options['prevClass']}" : '') .
      ($activePage === $totalPage ? ",a.{$this->options['nextClass']}" : '') .
      "\",ctn).bind(\"click\",function(e){e.preventDefault();});$(\"[type=number]\",ctn).bind(\"keydown\",function(e){if(e.which===13)$(\"button\",ctn).trigger(\"click\")});}(window));</script>";

    return $html;
  }

  /**
   * Get current active page index
   *
   * @return integer
   */
  private function getActivePage()
  {
    $totalPage = $this->getTotalPage();
    $activePage = $this->options['activePage'];
    return max(1, $totalPage > 0 ? min($totalPage, $activePage) : $activePage);
  }

  /**
   * Get total page number
   *
   * @return integer
   */
  private function getTotalPage()
  {
    return max(0, $this->options['totalPage'], $this->options['offset'] > 0 ?
        ceil($this->options['total'] / $this->options['offset']) : 0);
  }

  /**
   * Get min page index
   *
   * @return integer
   */
  private function getMinPage()
  {
    $activePage = $this->getActivePage();
    $showNum = $this->getShowNum();
    $pageOffset = floor($showNum / 2);
    $totalPage = $this->getTotalPage();
    return max(1, 0 === $totalPage ?
        $activePage - $showNum + 1 :
        (
        $totalPage - $activePage < $pageOffset ?
          $totalPage - $showNum + 1 :
          $activePage - $pageOffset
        )
    );
  }

  /**
   * Get max page index
   *
   * @return integer
   */
  private function getMaxPage()
  {
    $activePage = $this->getActivePage();
    $showNum = $this->getShowNum();
    $pageOffset = floor($showNum / 2);
    return 0 === $this->getTotalPage() ?
      $activePage :
      min(max($activePage + $pageOffset, $showNum), $this->getTotalPage());
  }

  /**
   * Get previous page index
   *
   * @return integer
   */
  private function getPrevPage()
  {
    return max(1, $this->getActivePage() - 1);
  }

  /**
   * Get next page index
   *
   * @return integer
   */
  private function getNextPage()
  {
    $totalPage = $this->getTotalPage();
    return 0 === $totalPage ? $this->getActivePage() + 1 : min($totalPage, $this->getActivePage() + 1);
  }

  /**
   * Get show number
   *
   * @return interger
   */
  private function getShowNum()
  {
    return max(1, 0 === $this->options['showNum'] % 2 ?
        ++$this->options['showNum'] :
        $this->options['showNum']
    );
  }

  /**
   * Get page url
   *
   * @staticvar null $paramUrl
   * @param integer $index
   * @return string
   */
  private function getUrl($index = NULL)
  {
    static $paramUrl = NULL;
    if (NULL === $paramUrl) {
      $url = $this->getParamUrl();
      if ('pathinfo' === $this->options['urlStyle']) {
        $url .= str_replace('//', '/', '/' . $this->options['pageKey'] . '/');
      } else {
        $url .= (FALSE !== strpos($url, '?') ? '' : '?') . '&' . $this->options['pageKey'] . '=';
      }

      $paramUrl = str_replace(array('//', '&&', '?&'), array('/', '&', '?'), $url);
    }

    $url = $paramUrl . $index;
    if ($this->options['hideFirstPage'] && $index === 1) {
      $url = str_replace(array(str_replace('//', '/', '/' . $this->options['pageKey'] . '/' . $index), '&' . $this->options['pageKey'] . '=' . $index), array('/', ''), $url);
    }
    return $url;
  }

  /**
   * Get param url
   *
   * @return string
   */
  private function getParamUrl()
  {
    $url = '';
    $urlStyle = $this->options['urlStyle'];
    foreach ($this->params as $key => $value) {
      if ('pathinfo' === $urlStyle) {
        $url .= '/' . urlencode($key) . '/' . urlencode($value);
      } else {
        $url .= '&' . urlencode($key) . '=' . urlencode($value);
      }
    }
    if ('pathinfo' === $urlStyle) {
      $url = str_replace('//', '/', $this->baseUrl . $url);
    } else {
      $url = str_replace(array('&&', '?&'), array('&', '?'), $this->baseUrl . (FALSE != strpos($this->baseUrl, '?') ? '' : '?') . $url);
    }

    return $url;
  }

  /**
   * Get jump html
   *
   * @return string
   */
  private function getJumpHtml($ajax = FALSE)
  {
    $jumpTmpl = $this->options['jumpTmpl'];
    $index = $this->getActivePage();
    if ($ajax) {
      $click = '';
    } else {
      $click = "var p=$(this).parent().children('[type=number]'),v=Number(p.val());if(isNaN(v)||v<1||v>Number(p.attr('max'))||v===Number(p.attr('placeholder')))return;window.location.href='" . $this->getUrl() . "'+(v!==1?v:'')";
    }
    $totalPage = $this->getTotalPage();
    $max = $totalPage ? $totalPage : $this->getActivePage();
    return '' === $jumpTmpl ? '' : str_replace(
        array('%placeholder%', '%maxlength%', '%min%', '%max%', '%click%'), array($index, strlen($max), 1, $max, $click), $jumpTmpl);
  }

  private function getContainerId()
  {
    $id = $this->options['containerId'];
    if ('' === $id) {
      $id = 'pagination-' . uniqid();
    }
    return $id;
  }

  private function getScript($uniqid)
  {
    /* $showNum = $this->getShowNum();
      $pageOffset = floor($showNum / 2);
      $totalPage = $this->getTotalPage(); */
    $script = '';
    if (Pagination::$first === TRUE) {
      $script = $this->getScriptLibrary(TRUE);
    }

    $prevHtml = str_replace('"', '\"', $this->options['prevHtml']);
    $nextHtml = str_replace('"', '\"', $this->options['nextHtml']);
    $jumpHtml = str_replace('"', '\"', $this->getJumpHtml(TRUE));
    $script .= <<<EOF
(function(w){
  w.jQuery("#{$uniqid}").pagination({
    baseUrl: "{$this->getUrl()}",
    prevHtml: "{$prevHtml}",
    nextHtml: "{$nextHtml}",
    prevClass: "{$this->options['prevClass']}",
    nextClass: "{$this->options['nextClass']}",
    activeClass: "{$this->options['activeClass']}",
    indexClass: "{$this->options['indexClass']}",
    showNum: {$this->getShowNum()},
    totalPage: {$this->getTotalPage()},
    jumpHtml: "{$jumpHtml}",
    ajaxCallback: "{$this->options['ajaxCallback']}"
  });
}(window));
EOF;

    return "<script>$script</script>";
  }

  private function getScriptLibrary($compress = TRUE)
  {
    if ($compress) {
      return <<<EOF
(function(h){var c=window.jQuery;c.fn.pagination=function(d){var e=c(this);d=d||{};var A=d.baseUrl||"",x=d.prevClass||"prev",y=d.nextClass||"next",l=d.prevHtml||"Prev",B=d.nextHtml||"Next",r=d.activeClass||"active",n=d.indexClass||"index",w=d.showNum||5,s=h.Math.floor(w/2),f=d.totalPage||0,z,t,p,q,m,g,u=function(b){b.preventDefault();k(b.currentTarget.text)},k=function(b,a){!a&&(1>b||0<f&&b>f||b===Number(c("a."+r,e).text()))||c.ajax({url:A+b,type:"get",dataType:"json",success:function(a){z(a);a=b;
a=Number(a);var d=h.Math.max(1,0<f&&a+s>f?f-w+1:a-s),k=h.Math.min(f||a,1>a-s?w:a+s),l,m;e.children("a."+n+",font").remove();if(4>=d)d=1;else for(c("<font>...</font>").insertAfter(p),g=2;1<=g;g--)c('<a class="'+n+'"/>').attr("href","#").text(g).bind("click",u).insertAfter(p);l=e.children("font");m=e.children().index(l);for(g=d;g<=(0<f&&k>=f-3?f:k);g++){var v=e.children("."+n+":last");e.children().index(v)<m&&(v=l);d=c('<a class="'+n+(a===Number(g)?" "+r:"")+'"/>').attr("href","#").text(g).bind("click",
u);0<v.length?d.insertAfter(v):d.insertAfter(p)}if(k<f-3)for(c("<font>...</font>").insertBefore(t),g=f-1;g<=f;g++)c('<a class="'+n+'"/>').attr("href","#").text(g).bind("click",u).insertBefore(t);q.attr("placeholder",a).val("")}})};e.html(d.jumpHtml||"");t=c('<a class="'+y+'" href="#">').html(B);t.prependTo(e);p=c('<a class="'+x+'" href="#">').html(l);p.prependTo(e);l=c("a",e);q=c("input[type=number]",e);m=c("button",e);z=function(b){if("function"===typeof h[b])return h[b];if("string"===typeof b&&
b.indexOf(".")){b=b.split(".");var a,d,c;for(d=0;d<b.length;d++)if(c=b[d],a&&a[c])a=a[c];else if(!a&&h[c])a=h[c];else break;a||(a=function(){});return a}return function(){}}(d.ajaxCallback);l.each(function(){var b=c(this);0<h.Number(b.text())?b.bind("click",u):b.hasClass(x)?b.bind("click",function(a){a.preventDefault();a=c("a."+r,e).filter(function(){return 0<Number(c(this).text())});a=Number(a.text());k(--a)}):b.hasClass(y)&&b.bind("click",function(a){a.preventDefault();a=c("a."+r,e).filter(function(){return 0<
Number(c(this).text())});a=Number(a.text());k(++a)})});m.bind("click",function(b){b.preventDefault();b=Number(q.val()||q.attr("placeholder"));b=h.Math.min(h.Math.max(1,b),f);k(b)});q.bind("keydown",function(b){13===b.which&&m.trigger("click")});k(1,!0)}})(window);
EOF;
    }
    return <<<EOF
(function(w){
  'use strict';
  var $ = window.jQuery;
    $.fn.pagination = function(options) {
      var ctn = $(this),
        options = options || {},
        baseUrl = options.baseUrl || '',
        prevClass = options.prevClass || "{$this->options['prevClass']}",
        nextClass = options.nextClass || "{$this->options['nextClass']}",
        prevHtml = options.prevHtml || "{$this->options['prevHtml']}",
        nextHtml = options.nextHtml || "{$this->options['nextHtml']}",
        activeClass = options.activeClass || "{$this->options['activeClass']}",
        indexClass = options.indexClass || "{$this->options['indexClass']}",
        showNum = options.showNum || {$this->getShowNum()},
        pageOffset = w.Math.floor(showNum/2),
        totalPage = options.totalPage || 0,
        ajaxCallback, nt, pr, cn, ph, bt, i,
        f = function(e) {
          e.preventDefault();
          var t = e.currentTarget.text;
          r(t);
        },
        r = function(t, f) {
          if (!f && (t < 1 || totalPage > 0 && t > totalPage ||
            t === Number($('a.'+activeClass, ctn).text()))) {
            return;
          }
          $.ajax({
            url: baseUrl+t,
            type: 'get',
            dataType: 'json',
            success: function(rs) {
              ajaxCallback(rs);
              s(t);
            }
          });
        },
        s = function(t) {
          t = Number(t);
          var min = w.Math.max(1, totalPage>0 && t + pageOffset > totalPage ? totalPage - showNum + 1 : t - pageOffset),
          max = w.Math.min(totalPage||t, t - pageOffset < 1 ? showNum : t + pageOffset),
          a, fnt, fnti;

          ctn.children('a.'+indexClass+',font').remove();

          if (min <= 4) {
            min = 1;
          } else {
            $('<font>...</font>').insertAfter(pr);
            for(i = 2; i>=1; i--) {
              $('<a class="'+indexClass+'"/>').attr('href', '#')
               .text(i).bind('click', f).insertAfter(pr);
            }
          }

          fnt = ctn.children('font');
          fnti = ctn.children().index(fnt);
          for (i = min; i <= (totalPage>0 && max >= totalPage - 3? totalPage: max); i++) {
            var li = ctn.children('.'+indexClass+':last');
            if (ctn.children().index(li) < fnti) {
              li = fnt;
            }
            a = $('<a class="'+indexClass+(t===Number(i)?' '+activeClass:'')+'"/>').attr('href', '#')
            .text(i).bind('click', f);

            if (li.length>0) {
              a.insertAfter(li);
            } else {
              a.insertAfter(pr);
            }
          }

          if (max < totalPage - 3) {
            $('<font>...</font>').insertBefore(nt);
            for(i = totalPage - 1; i<=totalPage; i++) {
              $('<a class="'+indexClass+'"/>').attr('href', '#')
                .text(i).bind('click', f).insertBefore(nt);
            }
          }
          ph.attr('placeholder', t).val('');
        };

        ctn.html(options.jumpHtml || '');
        nt = $('<a class="'+nextClass+'" href="#">').html(nextHtml);
        nt.prependTo(ctn),
        pr = $('<a class="'+prevClass+'" href="#">').html(prevHtml);
        pr.prependTo(ctn);
        cn = $('a', ctn);
        ph = $('input[type=number]', ctn);
        bt = $('button', ctn);
        ajaxCallback = (function(callback){
          if (typeof w[callback] === 'function') {
            return w[callback];
          } else if (typeof callback === 'string' && callback.indexOf('.')) {
            var l = callback.split('.'), c, i, t;
            for (i = 0; i < l.length; i++) {
              t = l[i];
              if (c && c[t]) {
                c = c[t];
              } else if (!c && w[t]) {
                c = w[t];
              } else {
                break;
              }
            }
            if (!c) {
              c = function(){};
            }
            return c;
          }
          return function(){};
        }(options.ajaxCallback));

        cn.each(function(){
          var \$this = $(this);
          if (w.Number(\$this.text())>0) {
            \$this.bind('click', f);
          } else if (\$this.hasClass(prevClass)) {
            \$this.bind('click', function(e) {
              e.preventDefault();
              var active = $('a.'+activeClass, ctn)
                .filter(function(){return Number(\$(this).text())>0}),
                t = Number(active.text());
              r(--t);
            });
          } else if (\$this.hasClass(nextClass)) {
            \$this.bind('click', function(e) {
              e.preventDefault();
              var active = $('a.'+activeClass, ctn)
                .filter(function(){return Number(\$(this).text())>0}),
              t = Number(active.text());
              r(++t);
            });
          }
        });
        bt.bind('click', function(e){
          e.preventDefault();
          var t = Number(ph.val() || ph.attr('placeholder'));
          t = w.Math.min(w.Math.max(1, t), totalPage);
          r(t);
        });
        ph.bind('keydown', function(e){
          if (e.which === 13) {
            bt.trigger('click');
          }
        });
        r(1, true);
    };
}(window));
EOF;
  }

}
