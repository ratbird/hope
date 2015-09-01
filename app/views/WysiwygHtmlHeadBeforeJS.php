<?php
/**
 * WysiwygHtmlHeadBeforeJS.php - 
 * Include this file in HTML-files before ckeditor.js is loaded.
 */
if (\Studip\Wysiwyg\Settings::getInstance()->isDisabled()) {
    $old_packages = array_flip(PageLayout::getSqueezePackages());
    unset($old_packages['wysiwyg']);
    call_user_func_array(
        'PageLayout::setSqueezePackages',
        array_values(array_flip($old_packages))
    );
    return;
}
?>
<script>
    CKEDITOR_BASEPATH = '<?=
        $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'assets/javascripts/ckeditor/'
    ?>';
</script>
