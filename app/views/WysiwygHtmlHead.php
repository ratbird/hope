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
        seminarId: '<?= $_SESSION['SessionSeminar'] ?>'
    };
</script>
