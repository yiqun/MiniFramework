(function (W) {
    'use strict';
    if (W.jQuery === undefined) {
        throw 'jQuery library required';
    }
    var $              = W.jQuery,
        //form = $('#{$this->getId()}'),
        form           = $('form'),
        submitDisabled = false,
        checkEmail     = function (emailAddress) {
            var sQtext         = '[^\\x0d\\x22\\x5c\\x80-\\xff]',
                sDtext         = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]',
                sAtom          = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+',
                sQuotedPair    = '\\x5c[\\x00-\\x7f]',
                sDomainLiteral = '\\x5b(' + sDtext + '|' + sQuotedPair + ')*\\x5d',
                sQuotedString  = '\\x22(' + sQtext + '|' + sQuotedPair + ')*\\x22',
                sDomain_ref    = sAtom,
                sSubDomain     = '(' + sDomain_ref + '|' + sDomainLiteral + ')',
                sWord          = '(' + sAtom + '|' + sQuotedString + ')',
                sDomain        = sSubDomain + '(\\x2e' + sSubDomain + ')*',
                sLocalPart     = sWord + '(\\x2e' + sWord + ')*',
                sAddrSpec      = sLocalPart + '\\x40' + sDomain, // complete RFC822 email address spec
                sValidEmail    = '^' + sAddrSpec + '$'; // as whole string

            return (new RegExp(sValidEmail)).test(emailAddress);
        },
        checkUrl       = function (url) {
            //URL pattern based on rfc1738 and rfc3986
            var rg_pctEncoded  = "%[0-9a-fA-F]{2}",
                rg_protocol    = "(http|https):\\/\\/",
                rg_userinfo    = "([a-zA-Z0-9$\\-_.+!*'(),;:&=]|" + rg_pctEncoded + ")+" + "@",
                rg_decOctet    = "(25[0-5]|2[0-4][0-9]|[0-1][0-9][0-9]|[1-9][0-9]|[0-9])", // 0-255
                rg_ipv4address = "(" + rg_decOctet + "(\\." + rg_decOctet + "){3}" + ")",
                rg_hostname    = "([a-zA-Z0-9\\-\\u00C0-\\u017F]+\\.)+([a-zA-Z]{2,})",
                rg_port        = "[0-9]+",
                rg_hostport    = "(" + rg_ipv4address + "|localhost|" + rg_hostname + ")(:" + rg_port + ")?",
                // chars sets
                // safe           = "$" | "-" | "_" | "." | "+"
                // extra          = "!" | "*" | "'" | "(" | ")" | ","
                // hsegment       = *[ alpha | digit | safe | extra | ";" | ":" | "@" | "&" | "=" | escape ]
                rg_pchar       = "a-zA-Z0-9$\\-_.+!*'(),;:@&=",
                rg_segment     = "([" + rg_pchar + "]|" + rg_pctEncoded + ")*",
                rg_path        = rg_segment + "(\\/" + rg_segment + ")*",
                rg_query       = "\\?" + "([" + rg_pchar + "/?]|" + rg_pctEncoded + ")*",
                rg_fragment    = "\\#" + "([" + rg_pchar + "/?]|" + rg_pctEncoded + ")*";

            return (new RegExp(
                "^"
                + rg_protocol
                + "(" + rg_userinfo + ")?"
                + rg_hostport
                + "(\\/"
                + "(" + rg_path + ")?"
                + "(" + rg_query + ")?"
                + "(" + rg_fragment + ")?"
                + ")?"
                + "$"
            )).test(url);

        },
        checkNumber    = function (num) {
            return num && !isNaN(num);
        },
        checkRegExp    = function (reg, v) {
            return (new RegExp(reg)).test(v);
        },
        checkMinNum    = function (num, v) {
            return checkNumber(v) && v >= num;
        },
        checkMaxNum    = function (num, v) {
            return checkNumber(v) && v <= num;
        },
        checkMinLen    = function (len, v) {
            return v.length >= len;
        },
        checkMaxLen    = function (len, v) {
            return v.length <= len;
        },
        checkInclude   = function (strs, v) {
            return -1 !== v.indexOf(strs);
        },
        checkExclude   = function (strs, v) {
            return -1 === v.indexOf(strs);
        },
        checkRange     = function (arr, v) {
            return -1 !== $.inArray(v, arr);
        },
        checkUnRange   = function (arr, v) {
            return -1 === $.inArray(v, arr);
        },
        dates          = {
            convert: function (d) {
                // Converts the date in d to a date-object. The input can be:
                //   a date object: returned without modification
                //  an array      : Interpreted as [year,month,day]. NOTE: month is 0-11.
                //   a number     : Interpreted as number of milliseconds
                //                  since 1 Jan 1970 (a timestamp)
                //   a string     : Any format supported by the javascript engine, like
                //                  "YYYY/MM/DD", "MM/DD/YYYY", "Jan 31 2009" etc.
                //  an object     : Interpreted as an object with year, month and date
                //                  attributes.  **NOTE** month is 0-11.
                return (
                    d.constructor === Date ? d :
                        d.constructor === Array ? new Date(d[0], d[1], d[2]) :
                            d.constructor === Number ? new Date(d) :
                                d.constructor === String ? new Date(d) :
                                    typeof d === "object" ? new Date(d.year, d.month, d.date) :
                                        NaN
                );
            },
            compare: function (a, b) {
                // Compare two dates (could be of any type supported by the convert
                // function above) and returns:
                //  -1 : if a < b
                //   0 : if a = b
                //   1 : if a > b
                // NaN : if a or b is an illegal date
                // NOTE: The code inside isFinite does an assignment (=).
                return (
                    isFinite(a = this.convert(a).valueOf()) &&
                    isFinite(b = this.convert(b).valueOf()) ?
                    (a > b) - (a < b) :
                        NaN
                );
            },
            inRange: function (d, start, end) {
                // Checks if date in d is between dates in start and end.
                // Returns a boolean or NaN:
                //    true  : if d is between start and end (inclusive)
                //    false : if d is before start or after end
                //    NaN   : if one or more of the dates is illegal.
                // NOTE: The code inside isFinite does an assignment (=).
                return (
                    isFinite(d = this.convert(d).valueOf()) &&
                    isFinite(start = this.convert(start).valueOf()) &&
                    isFinite(end = this.convert(end).valueOf()) ?
                    start <= d && d <= end :
                        NaN
                );
            }
        },
        checkBegin     = function (bg, v) {
            return -1 < dates.compare(v, bg);
        },
        checkEnd       = function (bg, v) {
            return 1 > dates.compare(v, bg);
        },
        showFormError  = function (e, msg) {
            var t = e[0].tagName, ep = e.closest('.form-group'), html,
                fc                   = $('.form-control,[data-form-role=element],.note-editor', ep);
            if (t === 'INPUT' && -1 !== $.inArray(e.attr('type'), ['text', 'number', 'email', 'phone', 'url'])
                || t === 'TEXTAREA') {
                e.focus();
            } else if (t === 'SELECT') {
                e.prev('.select2-container').select2('open');
            }
            ep.removeClass('has-success').addClass('has-error');
            $('.help-block', ep).html(msg);
            if (ep.hasClass('has-feedback')) {
                $('.form-control-feedback', ep).remove();
                html = $("<span class=\"glyphicon glyphicon-warning-sign form-control-feedback\"/>");
                html.appendTo(ep);
            }
            fc.bind('keydown click', function () {
                ep.removeClass('has-error');
                $('.help-block', ep).html(e.data('helper'));
                if (ep.hasClass('has-feedback')) {
                    html.remove();
                }
                fc.unbind('keydown click');
            });
        },
        validate       = function () {
            var pass = true;
            $('[data-form-role=element]', form).each(function () {
                var e = $(this), d = W.FormElementGetDataFns[e.attr('id')](), r = e.data('rules'), i, j;
                if (r) {
                    for (i = 0; i < r.length; i++) {
                        j = r[i];
                        // {rule: rule-expression, optVal: rule-optional-value, errMsg: error-message}
                        switch (j.rule) {
                            case undefined:
                                break;
                            case 'required':
                                pass = '' !== d;
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'email':
                                pass = checkEmail(d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'url':
                                pass = checkUrl(d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'number':
                                pass = checkNumber(d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'regexp':
                                pass = checkRegExp(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'minNum':
                                pass = checkMinNum(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'maxNum':
                                pass = checkMaxNum(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'minLen':
                                pass = checkMinLen(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'maxLen':
                                pass = checkMaxLen(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'include':
                                pass = checkInclude(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'exclude':
                                pass = checkExclude(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'range':
                                pass = checkRange(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'unrange':
                                pass = checkUnRange(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'begin':
                                pass = checkBegin(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                            case 'end':
                                pass = checkEnd(j.optVal, d);
                                if (!pass) {
                                    showFormError(e, j.errMsg);
                                    return false;
                                }
                                break;
                        }
                    }
                }
            });
            return pass;
        };
    // extra jQuery
    $.showFormError = showFormError;

    form.bind('submit', function (e) {
        e.preventDefault();
        if (!validate()) {
            return;
        }
        var $btn = $('[type=submit]', form);
        $btn.button('loading');

        if (form.attr('submitType') === 'direct') {
            form[0].submit();
            return;
        }

        var formData = {};
        for (var i in W.FormElementGetDataFns) {
            if (i != 'undefined') {
                var name = $('#' + i).attr('name');
                if (name === undefined) {
                    console.log(i + '   undefined');
                    return;
                }
                if (name.substr(-2) === '[]') {
                    if (typeof formData[name] === 'undefined') {
                        formData[name] = [];
                    }
                    formData[name].push(W.FormElementGetDataFns[i]());
                } else {
                    formData[name] = W.FormElementGetDataFns[i]();
                }
            }
        }
        var fd = $('[name=formData]');
        W.http.timeout = 30;
        W.http.post(form.attr('action'), formData, function (data) {
            $btn.button('reset');
            'successCallback'
        }, function (msg) {
            $btn.button('reset');
            //'failCallback'
        });
        return false;
    });
}(window));