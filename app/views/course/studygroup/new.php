<?php
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

$infobox = array();
$infobox['picture'] = 'infobox/studygroup.jpg';
$infobox['content'] = array(
    array(
        'kategorie'=>_("Information"),
        'eintrag'=>array(
            array("text"=>_("Studiengruppen sind eine einfache M�glichkeit, mit KommilitonInnen, KollegInnen und anderen zusammenzuarbeiten. Jeder kann Studiengruppen gr�nden."),"icon"=>"icons/16/black/info.png"),
            array("text"=>_("W�hlen Sie 'Offen f�r alle', wenn beliebige Nutzer der Gruppe ohne Nachfrage beitreten k�nnen sollen. 'Auf Anfrage' erfordert Ihr Eingreifen: Sie m�ssen jede einzelne Aufnahmeanfrage annehmen oder ablehnen."),"icon"=>"icons/16/black/info.png"),
            array("text"=>_("Alle Einstellungen k�nnen auch sp�ter noch unter dem Reiter 'Admin' ge�ndert werden."),"icon"=>"icons/16/black/info.png")
            )
    )
);

?>

<?= $this->render_partial("course/studygroup/_feedback") ?>
<h1><?= _("Studiengruppe anlegen") ?></h1>

<form action="<?= $controller->url_for('course/studygroup/create') ?>" method=post>
<?= CSRFProtection::tokenTag() ?>

<table class="blank" width="85%" cellspacing="5" cellpadding="0" border="0">
<tr>
  <td style='text-align:right; font-size:150%;'><label for="groupname"><?= _("Name:") ?></label></td>
  <td style='font-size:150%;'><input type='text' name='groupname' id='groupname' size='25' value="<?= htmlReady($this->flash['request']['groupname'])?>" style='font-size:100%'></td>
</tr>

<tr>
  <td style='text-align:right; vertical-align:top;'><label for="groupdescription"><?= _("Beschreibung:") ?></label></td>
  <td><textarea name='groupdescription' id='groupdescription' rows=5 cols=50 placeholder="<?= _("Hier aussagekr�ftige Beschreibung eingeben.") ?>"><?= htmlReady($this->flash['request']['groupdescription']) ?></textarea></td>
</tr>

<? if ($GLOBALS['perm']->have_perm('admin')) : ?>
    <?= $this->render_partial("course/studygroup/_choose_founders", array('founders' => $flash['founders'], 'results_choose_founders' => $flash['results_choose_founders'])) ?>
<? endif; ?>
<tr>
  <td style='text-align:right; vertical-align:top;'><?= _("Module:") ?></td>
  <td>
    <? foreach($available_modules as $key => $name) : ?>
        <? if ($key === "documents_folder_permissions") : ?>
        <label>
                <input name="groupplugin[<?= $key ?>]" type="checkbox"<?= ($this->flash['request']['groupplugin'][$key]) ? 'checked="checked"' : '' ?>>
                <?= htmlReady($name) ?>
        </label><br>
        <? else : ?>
            <? $module = $sem_class->getSlotModule($key) ?>
            <? if ($module && $sem_class->isModuleAllowed($module) && !$sem_class->isSlotMandatory($key)) : ?>
            <label>
                <input name="groupplugin[<?= $module ?>]" type="checkbox"<?= ($this->flash['request']['groupplugin'][$key]) ? 'checked="checked"' : '' ?>>
                <?= htmlReady($name) ?>
            </label><br>
            <? endif ?>
        <? endif ?>
    <? endforeach; ?>

    <? foreach($available_plugins as $key => $name) : ?>
        <? if ($sem_class->isModuleAllowed($key) && !$sem_class->isModuleMandatory($key) && !$sem_class->isSlotModule($key)) : ?>
        <label>
            <input name="groupplugin[<?= $key ?>]" type="checkbox" <?= ($this->flash['request']['groupplugin'][$key]) ? 'checked="checked"' : '' ?>> <?= htmlReady($name) ?>
        </label><br>
        <? endif ?>
    <? endforeach; ?>
  </td>
</tr>

<tr>
  <td style='text-align:right;'></td>
</tr>

<tr>
  <td style='text-align:right;'><label for="groupaccess"><?= _("Zugang:") ?></label></td>
  <td>
      <select name="groupaccess" id="groupaccess">
         <option <?= ($groupaccess == 'all') ? 'selected="selected"':'' ?> value="all"><?= _("Offen f�r alle") ?></option>
         <option <?= ($groupaccess == 'invite') ? 'selected="selected"':'' ?> value="invite"><?= _("Auf Anfrage") ?></option>
      </select>
  </td>
</tr>

<tr>
  <td style='text-align:right;'></td>
  <td>&nbsp;</td>
</tr>

<tr>
  <td style='text-align:right; vertical-align:top;'><p><?= _("Nutzungsbedingungen:") ?></p></td>
  <td>
    <? if ($GLOBALS['perm']->have_perm('admin')) : ?>
    <p>
      <b><?= _("Ich habe die eingetragenen Gr�nderInnen dar�ber informiert, dass in Ihrem Namen eine Studiengruppe angelegt wird und versichere, dass Sie mit folgenden Nutzungsbedingungen einverstandenen sind:") ?></b>
    </p>
    <? endif; ?>
    <p>
      <em><?= formatReady( $terms ) ?></em>
    </p>
    <p>
        <label>
            <input type=checkbox name="grouptermsofuse_ok"> <?= _("Einverstanden") ?>
        </label>
    </p>
  </td>
</tr>


<tr>
  <td></td>
  <td>
    <?= Button::createAccept(_('Speichern'), array('title' => _("Studiengruppe anlegen"))); ?>
    <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL("dispatch.php/studygroup/browse")); ?>
  </td>
</tr>

</table>
</form>
