<?
$translations = array();

$translations["suchen"] = _("suchen");

# TODO (mlunzena) studip UTF-8 encode

?>
String.toLocaleString({
  "<?= strtr($language, "_", "-") ?>": <?= json_encode($translations) ?>

});
console.log(<?= json_encode($translations) ?>, "<?= $language ?>");
