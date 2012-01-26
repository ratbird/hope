<?
if (!Request::isXhr()) {
    global $template_factory;
    $this->set_layout($template_factory->open('layouts/base_without_infobox'));
}
?>

<?= studip_utf8encode($content) ?>
