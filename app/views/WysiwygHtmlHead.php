<?php
/**
 * WysiwygHtmlHead.php -
 * Include this file in HTML-files after Stud.IP's JS library is loaded.
 */

if (!\Config::get()->WYSIWYG) {
    return; // wysiwyg is switched off, don't insert it's JS object
}
?>
<script type="text/javascript">
    STUDIP.wysiwyg = {
        seminarId: '<?= $_SESSION['SessionSeminar'] ?>'
    };
</script>
