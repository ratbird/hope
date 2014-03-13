<?php
/**
 * WysiwygHtmlHeadBeforeJS.php - 
 * Include this file in HTML-files before ckeditor.js is loaded.
 */
?>
<script>
    CKEDITOR_BASEPATH = '<?=
        $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'assets/javascripts/ckeditor/'
    ?>';
</script>
