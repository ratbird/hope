<?php
/**
 * WysiwygHtmlHead.php - 
 * Include this file in HTML-files after Stud.IP's JS library is loaded.
 */
if (\Studip\Wysiwyg\Settings::getInstance()->isGloballyDisabled()) {
    return;
}
?>
<script type="text/javascript">
<?php if (!\Studip\Wysiwyg\Settings::getInstance()->isDisabled()) { ?>
    STUDIP.WYSIWYG_CONTEXT = '<?= $_SESSION['SessionSeminar'] ?>';
<?php } ?>
    STUDIP.wysiwyg = {
        disabled: <?= \Studip\Wysiwyg\Settings::getInstance()->isDisabled() ? 'true' : 'false' ?>,
        settings: <?= \Studip\Wysiwyg\Settings::getInstance()->asJson() ?>,
        seminarId: '<?= $_SESSION['SessionSeminar'] ?>'
    };
</script>
