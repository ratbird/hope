<? use Studip\Button, Studip\LinkButton ?>
<?=CSRFProtection::tokenTag(); ?>
<?=$delete_question?>
<div id="edit_tour_content" class="edit_tour_content">
<h2><?= _('Tour bearbeiten') ?></h2>
<form class="studip_form" action="<?=URLHelper::getURL('dispatch.php/tour/admin_details/'.$tour->tour_id)?>" method="POST">
    <fieldset>
        <legend><?= _('Grunddaten') ?></legend>
        <label for="tour_name" class="caption">
            <?= _('Name der Tour:') ?>
            <span class="required">*</span>
        </label>
        <input type="text" size="60" maxlength="255" name="tour_name"
            value="<?= $tour ? htmlReady($tour->name) : '' ?>"
            required="required" aria-required="true"
            placeholder="<?= _('Bitte geben Sie einen Namen für die Tour an') ?>"/>
        <label for="tour_description" class="caption">
            <?= _('Beschreibung:') ?>
            <span class="required">*</span>
        </label>
        <textarea cols="60" rows="5" name="tour_description"
            required="required" aria-required="true"
            placeholder="<?= _('Bitte geben an, welchen Inhalt die Tour hat') ?>"><?= $tour ? htmlReady($tour->description) : '' ?></textarea>
        <label for="tour_type" class="caption">
            <?= _('Art der Tour:') ?>
        </label>
        <select name="tour_type">
        <option value="tour"<?=($tour->type == 'tour') ? ' selected' : ''?>><?=_('Tour (passiv)')?></option>
        <option value="wizard"<?=($tour->type == 'wizard') ? ' selected' : ''?>><?=_('Wizard (interaktiv)')?></option>
        </select>
        <label for="tour_access" class="caption">
            <?= _('Zugang zur Tour:') ?>
        </label>
        <select name="tour_access">
        <option value="link"<?=($tour->settings->access == 'link') ? ' selected' : ''?>><?=_('unsichtbar')?></option>
        <option value="standard"<?=($tour->settings->access == 'standard') ? ' selected' : ''?>><?=_('Anzeige im Hilfecenter')?></option>
        <option value="autostart"<?=($tour->settings->access == 'autostart') ? ' selected' : ''?>><?=_('Startet bei jedem Aufruf der Seite, bis die Tour abgeschlossen wurde')?></option>
        <option value="autostart_once"<?=($tour->settings->access == 'autostart_once') ? ' selected' : ''?>><?=_('Startet nur beim ersten Aufruf der Seite')?></option>
        </select>
        <? if (! count($tour->steps)) :?>
        <label for="tour_name" class="caption">
            <?= _('Startseite der Tour:') ?>
            <span class="required">*</span>
        </label>
        <input type="text" size="60" maxlength="255" name="tour_startpage"
            value="<?= $tour_startpage ? htmlReady($tour_startpage) : '' ?>"
            required="required" aria-required="true"
            placeholder="<?= _('Bitte geben Sie eine Startseite für die Tour an') ?>"/>
        <? endif ?>
        <label for="tour_roles[]" class="caption">
            <?= _('Geltungsbereich (Nutzendenstatus):') ?>
        </label>
        <? foreach (array("autor", "tutor", "dozent", "admin", "root") as $role) : ?>
            <label><input type="checkbox" name="tour_roles[]" value="<?=$role?>"<?=(strpos($tour->roles, $role) !== false) ? ' checked' : ''?>><?=$role?></label>
        <? endforeach?>
        <!--label for="tour_audience" class="caption">
            <?= _('Bedingung') ?>
        </label>
        <select name="tour_audience_type">
        <option value=""></option>
        <option value="sem"<?=($audience->type == 'sem') ? ' selected' : ''?>><?=_('TeilnehmerIn der Veranstaltung')?></option>
        <option value="inst"<?=($audience->type == 'inst') ? ' selected' : ''?>><?=_('Mitglied der Einrichtung')?></option>
        <option value="studiengang"<?=($audience->type == 'studiengang') ? ' selected' : ''?>><?=_('Eingeschrieben in Studiengang')?></option>
        <option value="abschluss"<?=($audience->type == 'abschluss') ? ' selected' : ''?>><?=_('Angestrebter Abschluss')?></option>
        <option value="userdomain"<?=($audience->type == 'userdomain') ? ' selected' : ''?>><?=_('Zugeordnet zur Nutzerdomäne')?></option>
        </select>
        <input type="text" size="60" maxlength="255" name="tour_audience_range_id"
            value="<?= $audience ? htmlReady($audience->range_id) : '' ?>"
            placeholder="<?= _('interne ID des Objekts') ?>"/-->
        <div class="submit_wrapper">
            <?= CSRFProtection::tokenTag() ?>
            <?= Button::createAccept(_('Speichern'), 'save_tour_details') ?>
            <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('tour/admin_overview')) ?>
        </div>
    </fieldset>
    <? if (!$tour->isNew()) :?>
    <table class="default">
        <caption>
            <div class="step_list_title"><?=_('Schritte')?></div>
        </caption>
        <colgroup>
            <col width="2%">
            <col width="25%">
            <col>
            <col width="15%">
            <col width="80">
        </colgroup>                   
        <thead><tr>
            <th><?=_("Nr.")?></th>
            <th><?=_("Überschrift")?></th>
            <th><?=_("Inhalt")?></th>
            <th><?=_("Seite")?></th>
            <th><?=_("Aktion")?></th>
        </tr></thead>
        <tbody>
        <? if (count($tour->steps)) : ?>
            <? foreach ($tour->steps as $step) : ?>
                <tr id="<?=$tour_id . '_' . $step->step?>">
                <td><?=$step->step?></td>
                <td><?=htmlReady($step->title)?></td>
                <td><?=htmlReady($step->tip)?></td>
                <td><?=htmlReady($step->route)?></td>
                <td>
                <a href="<?=URLHelper::getURL('dispatch.php/tour/edit_step/'.$tour->tour_id.'/'.$step->step)?>" target="blank" <?=tooltip(_('Schritt bearbeiten'))?> data-dialog="size=auto;reload-on-close">
                <img src="<?= Assets::image_path('icons/16/blue/edit.png')?>"></a>
                <input type="image" name="delete_tour_step_<?=$step->step?>" 
                       src="<?= Assets::image_path('icons/16/blue/trash.png')?>" 
                       aria-label="<?= _('Schritt löschen')?>" <?=tooltip(_("Schritt löschen"),false)?>>
                <a href="<?=URLHelper::getURL('dispatch.php/tour/edit_step/'.$tour->tour_id.'/'.($step->step + 1).'/new')?>" target="blank" <?=tooltip(_('Neuen Schritt hinzufügen'))?> data-dialog="size=auto;reload-on-close">
                <img src="<?= Assets::image_path('icons/16/blue/add.png')?>"></a>
                </td>
                </tr>
            <? endforeach ?>
        <? else : ?>
            <tr>
            <td colspan="6">
            <?=_('In dieser Tour sind bisher keine Schritte vorhanden.')?>
            </td>
            </tr>
        <? endif ?>
        </tbody>
        <tfoot>
            <tr><td colspan="6">
            <?=LinkButton::create(_('Neuen Schritt hinzufügen'), URLHelper::getURL('dispatch.php/tour/edit_step/'.$tour->tour_id.'/'.(count($tour->steps)+1).'/new'), array('target' => 'blank', 'data-dialog' => 'size=auto')) ?>
            </td></tr>
        </tfoot>
    </table>
    <? endif ?>
</form>
</div>