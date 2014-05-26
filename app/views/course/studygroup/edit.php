<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>

<?= $this->render_partial("course/studygroup/_feedback") ?>
<h1><?= _("Studiengruppe bearbeiten") ?></h1>


<form action="<?= $controller->url_for('course/studygroup/update/'.$sem_id) ?>" method=post>
<?= CSRFProtection::tokenTag() ?>
<input type='submit' class="invisible" name="<?=_("Änderungen übernehmen")?>" aria-hidden="true">
<table class="blank" width="75%" cellspacing="5" cellpadding="0" border="0">

<tr>
  <td style='text-align:right; font-size:150%;'><?= _('Name:') ?></td>
  <td style='font-size:150%;'><input type='text' name='groupname' size='25' value='<?= htmlReady($sem->getName()) ?>' style='font-size:100%'></td>
</tr>

<tr>
  <td style='text-align:right; vertical-align:top;'><?= _('Beschreibung:') ?></td>
  <td><textarea name='groupdescription' rows=5 cols=50><?= htmlReady($sem->description) ?></textarea></td>
</tr>

<? if ($GLOBALS['perm']->have_studip_perm('dozent', $sem_id)) : ?>
    <?= $this->render_partial("course/studygroup/_replace_founder", array('tutors' => $tutors)) ?>
<? endif; ?>

<tr>
  <td style='text-align:right; vertical-align:top;'><?= _('Inhaltselemente:') ?></td>
  <td>
    <? foreach($available_modules as $key => $name) : ?>
        <? if ($key === "documents_folder_permissions") : ?>
            <?
            // load metadata of module
            $adminModules = new AdminModules();
            $description = $adminModules->registered_modules[$key]['metadata']['description'];
            ?>
            <label <?= tooltip(kill_format($description)); ?>>
                <input name="groupplugin[<?= $key ?>]" type="checkbox" <?= ($modules->getStatus($key, $sem_id, 'sem')) ? 'checked="checked"' : '' ?>>
                <?= htmlReady($name) ?>
            </label><br>
        <? else : ?>
            <? $module = $sem_class->getSlotModule($key) ?>
            <? if ($module && $sem_class->isModuleAllowed($module) && !$sem_class->isSlotMandatory($key)) : ?>
                <?
                // load metadata of module
                $studip_module = $sem_class->getModule($key);
                $info = $studip_module->getMetadata();
                ?>
                <label <?= tooltip(isset($info['description']) ? kill_format($info['description']) : ("Für dieses Element ist keine Beschreibung vorhanden.")) ?>>
                    <input name="groupplugin[<?= $module ?>]" type="checkbox" <?= ($modules->getStatus($key, $sem_id, 'sem')) ? 'checked="checked"' : '' ?>>
                    <?= htmlReady($name) ?>
                    <? $studip_module = $sem_class->getModule($module);
                    if (is_a($studip_module, "StandardPlugin")) : ?>
                        (<?= htmlReady($studip_module->getPluginName()) ?>)
                    <? endif ?>
                </label><br>
            <? endif;?>
        <? endif ?>
    <? endforeach; ?>

    <? foreach($available_plugins as $key => $name) : ?>
        <? if ($sem_class->isModuleAllowed($key) && !$sem_class->isModuleMandatory($key) && !$sem_class->isSlotModule($key)) : ?>
            <?
            // load metadata of plugin
            $plugin = $sem_class->getModule($key);
            $info = $plugin->getMetadata();
            ?>
            <label <?= tooltip(isset($info['description']) ? kill_format($info['description']) : ("Für dieses Element ist keine Beschreibung vorhanden.")) ?>>
                <input name="groupplugin[<?= $key ?>]" type="checkbox" <?= ($enabled_plugins[$key]) ? 'checked="checked"' : '' ?>>
                <?= htmlReady($name) ?>
            </label><br>
        <? endif ?>
    <? endforeach; ?>
  </td>
</tr>

<tr>
  <td style='text-align:right;'></td>
</tr>

<tr>
  <td style='text-align:right;'><?= _('Zugang:') ?></td>
  <td>
      <select name="groupaccess">
          <option <?= ($sem->admission_prelim == 0) ? 'selected="selected"':'' ?> value="all"><?= _('Offen für alle') ?></option>
          <option <?= ($sem->admission_prelim == 1) ? 'selected="selected"':'' ?> value="invite"><?= _('Auf Anfrage') ?></option>
          <? if(Config::get()->STUDYGROUPS_INVISIBLE_ALLOWED || $sem->visible == 0): ?>
            <option <?= ($sem->visible == 0) ? 'selected="selected"':'' ?> value="invisible" <?= Config::get()->STUDYGROUPS_INVISIBLE_ALLOWED ? '' : 'disabled="true"' ?>><?= _('Unsichtbar') ?></option>
          <? endif; ?>
      </select>
  </td>
</tr>

<tr>
  <td style='text-align:right;'></td>
  <td>&nbsp;</td>
</tr>

<tr>
  <td></td>
  <td>
      <?= Button::createAccept(_('Übernehmen'), array('title' => _("Änderungen übernehmen"))); ?>
      <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('seminar_main.php')); ?>
  </td>
</tr>

</table>
</form>
