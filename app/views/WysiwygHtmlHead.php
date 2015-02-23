<?php
/**
 * WysiwygHtmlHead.php - 
 * Include this file in HTML-files after Stud.IP's JS library is loaded.
 */
use \Studip\Markup;
use \Studip\Wysiwyg\Settings;

if (Settings::getInstance()->isGloballyDisabled()) {
    return;
}
?>
<script type="text/javascript">
    STUDIP.wysiwyg = {
        disabled: <?= Settings::getInstance()->isDisabled() ? 'true' : 'false' ?>,
        settings: <?= Settings::getInstance()->asJson() ?>,
        seminarId: '<?= $_SESSION['SessionSeminar'] ?>',
        htmlMarker: '<?= addslashes(Markup::HTML_MARKER) ?>',
        htmlMarkerRegExp: '<?= addslashes(Markup::HTML_MARKER_REGEXP) ?>',
        isHtml: function isHtml(text) {
            // NOTE keep this function in sync with
            // Markup::isHtml in Markup.class.php
            if (this.hasHtmlMarker(text)) {
                return true;
            }
            text = text.trim();
            return text[0] === '<' && text[text.length - 1] === '>';
        },
        hasHtmlMarker: function hasHtmlMarker(text) {
            // NOTE keep this function in sync with
            // Markup::hasHtmlMarker in Markup.class.php
            return (new RegExp(this.htmlMarkerRegExp)).test(text);
        },
        markAsHtml: function markAsHtml(text) {
            // NOTE keep this function in sync with
            // Markup::markAsHtml in Markup.class.php
            if (this.hasHtmlMarker(text)) {
                return text; // marker already set, don't set twice
            }
            return this.htmlMarker + '\n' + text;
        }
    };
</script>
