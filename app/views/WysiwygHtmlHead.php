<?php
/**
 * WysiwygHtmlHead.php - 
 * Include this file in HTML-files after Stud.IP's JS library is loaded.
 */
?>
<script type="text/javascript">
    STUDIP.WYSIWYG = <?= \Config::get()->WYSIWYG ? 'true' : 'false' ?>;
    STUDIP.WYSIWYG_CONTEXT = '<?= $_SESSION['SessionSeminar'] ?>';
</script>
