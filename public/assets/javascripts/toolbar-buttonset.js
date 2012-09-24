/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Default toolbar buttonset
 * ------------------------------------------------------------------------ */
(function ($) {

    // Creates a wrapper function that wraps the passed string using the
    // passed prefix and suffix. If the suffix is omitted, it will be replaced
    // by the prefix.
    // Be aware that the wrap function will not wrap a string twice.
    function createWrap(prefix, suffix) {
        if (suffix === "undefined") {
            suffix = prefix;
        }
        return function (string) {
            return (string.substr(0, prefix.length) === prefix && string.substr(-suffix.length) === suffix)
                ? string
                : prefix + string + suffix;
        };
    }

    // Define default stud.ip button set
    STUDIP.Toolbar.buttonSet = {
        left: {
            bold:           {label: "<strong>B</strong>",   evaluate: createWrap("**")},
            italic:         {label: "<em>i</em>",           evaluate: createWrap("%%")},
            underline:      {label: "<u>u</u>",             evaluate: createWrap("__")},
            strikethrough:  {label: "<del>u</del>",         evaluate: createWrap("{-", "-}")},
            code:           {label: "<code>code</code>",                 evaluate: createWrap("[code]", "[/code]")},
            larger:         {label: "A+",                   evaluate: createWrap("++")},
            smaller:        {label: "A-",                   evaluate: createWrap("--")},
            signature:      {label: "signature",            evaluate: createWrap("", "\u2013~~~")},
            link: {
                label: "link",
                evaluate: function (string) {
                    string = string || (window.prompt("Text:"));
                    if (string === null) {
                        return string;
                    }

                    var url = window.prompt("URL:");
                    return url === null ? string : "[" + string + "]" + url;
                }
            },
            image: {
                label: "img",
                evaluate: function (string) {
                    var url = window.prompt("URL:");
                    return url === null ? string : "[img=" + string + "]" + url;
                }
            }
        },
        right: {
            smilies: {
                label: ":)",
                evaluate: function () {
                    window.open(STUDIP.URLHelper.getURL("dispatch.php/smileys"), "_blank");
                }
            },
            help: {
                label: "?",
                evaluate: function () {
                    var url = $("link[rel=help].text-format").attr("href");
                    window.open(url, "_blank");
                }
            }
        }
    };

}(jQuery));
