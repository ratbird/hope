<h2><?= htmlReady($institute->getFullname())?></h2>
<ul style="list-style-type:none;padding:0px;">
    <? if ($institute->strasse) : ?>
        <li><b><?=_("Straße:")?></b> <?=htmlReady($institute->strasse)?></li>
    <? endif ?>

    <? if ($institute->Plz) : ?>
        <li><b><?=_("Ort:")?></b> <?=htmlReady($institute->Plz)?></li>
    <? endif ?>

    <? if ($institute->telefon) : ?>
        <li><b><?=_("Tel.:")?></b> <?=htmlReady($institute->telefon)?></li>
    <? endif ?>

    <? if ($institute->fax) : ?>
        <li><b><?=_("Fax:")?></b> <?=htmlReady($institute->fax)?></li>
    <? endif ?>

    <? if ($institute->url) : ?>
        <li><b><?=_("Homepage:")?></b> <?=htmlReady($institute->url)?></li>
    <? endif ?>

    <? if ($institute->email) : ?>
        <li><b><?=_("E-Mail:")?></b> <?=htmlReady($institute->email)?></li>
    <? endif ?>

    <? if ($institute->fakultaets_id) : ?>
        <li><b><?=_("Fakultät:")?></b> <?=htmlReady($institute->faculty->name)?></li>
    <? endif ?>

    <? foreach ($institute->datafields->map(function ($d) {return $d->getTypedDatafield();}) as $entry) : ?>
        <? if ($entry->isVisible() && $entry->getValue()) : ?>
            <li><b><?=htmlReady($entry->getName())?>: </b>
            <?=$entry->getDisplayValue();?>
            </li>
        <? endif?>
    <? endforeach ?>
</ul>

<?
// Anzeige von News
($rechte) ? $show_admin=TRUE : $show_admin=FALSE;
show_news($institute_id,$show_admin, 0, $institut_main_data["nopen"], "100%", object_get_visit($institute_id, "inst"), $institut_main_data);

// include and show votes and tests
if (get_config('VOTE_ENABLE')) {
    show_votes ($institute_id, $auth->auth["uid"], $GLOBALS['perm'], YES);
}

// display plugins
$plugins = PluginEngine::getPlugins('StandardPlugin', $institute_id);
$layout = $GLOBALS['template_factory']->open('shared/index_box');

foreach ($plugins as $plugin) {
    $template = $plugin->getInfoTemplate($institute_id);

    if ($template) {
        echo $template->render(NULL, $layout);
        $layout->clear_attributes();
    }
}
?>