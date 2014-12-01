<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if ($flash['delete']) : ?>
<?= $this->render_partial("admin/user/_delete", array('data' => $flash['delete'])) ?>
<? endif ?>

<form action="<?= $controller->url_for('admin/user/') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table class="default collapsable">
    
    <caption>
        <?= _('Benutzerverwaltung') ?>
    </caption>

    <tr>
        <td align="right" width="15%">
            <?= _("Benutzername:") ?>
        </td>
        <td width="35%">
            <input name="username" type="text" value="<?= htmlReady($user['username']) ?>">
        </td>
        <td align="right" width="15%">
            <?=_('Vorname:')?>
        </td>
        <td width="35%">
            <input name="vorname" type="text" value="<?= htmlReady($user['vorname']) ?>">
        </td>
    </tr>
    <tr>
        <td align="right" width="15%">
            <?= _("E-Mail:")?>
        </td>
        <td width="35%">
            <input name="email" type="text" value="<?= htmlReady($user['email']) ?>">
        </td>
        <td align="right" width="15%">
            <?= _("Nachname:")?>
        </td>
        <td width="35%">
            <input name="nachname" type="text" value="<?= htmlReady($user['nachname']) ?>">
        </td>
    </tr>
    <tr>
        <td align="right" width="15%">
            <?= _("Status:")?>
        </td>
        <td width="35%">
            <select name="perm">
            <? foreach(array("alle", "user", "autor", "tutor", "dozent", "admin", "root") as $one) : ?>
                <option <?= ($user['perm'] == $one) ? 'selected' : ''?> value="<?= $one ?>"><?= ($one=="alle")? _('alle') : $one ?></option>
            <? endforeach ?>
            </select>
            <input type="checkbox" name="locked" value="1" <?= ($user['locked'] == 1) ? 'checked':'' ?>> <?=_("nur gesperrt")?>
        </td>
        <td align="right" width="15%">
            <?= _("inaktiv:")?>
        </td>
        <td width="35%">
            <select name="inaktiv">
               <? foreach(array("<=" => ">=", "=" => "=", ">" => "<", "nie" =>_("nie")) as $i => $one) : ?>
                <option value="<?= htmlready($i) ?>" <?= ($user['inaktiv'] == $i) ? 'selected' : ''?>><?= htmlready($one) ?></option>
                <? endforeach ?>
            </select>
            <input name="inaktiv_tage" type="text" value="<?= htmlReady($user['inaktiv_tage']) ?>" size="10"> Tage
        </td>
    </tr>

    <tbody <?= ($advanced) ? '': 'class="collapsed"' ?>>
    <tr class="table_header header-row">
        <th colspan="4" class="toggle-indicator">
            <a class="toggler" href="<?= $controller->url_for('admin/user/')?><?= ($advanced) ? '' : 'index/advanced' ?>" title="<?= _('Zusätzliche Suchfelder ein-/ausblenden') ?>">
                <?= _('Erweiterte Suche')?>
            </a>
        </th>
    </tr>
    <tr>
        <td align="right" width="15%">
            <?= _("Nutzerdomäne:")?>
        </td>
        <td width="35%">
            <select name="userdomains">
                <option value=""><?= _("Alle")?></option>
                <option value="null-domain" <?= ($user['userdomains'] == 'null-domain') ? 'selected' : ''?>><?= _("Ohne Domäne")?></option>
                <? foreach($userdomains as $one) : ?>
                    <option <?= ($user['userdomains'] == $one->getId()) ? 'selected' : ''?> value="<?= htmlReady($one->getId()) ?>"><?= htmlReady($one->getName() ? $one->getName() : $one->getId()) ?></option>
                <? endforeach ?>
            </select>
        </td>
        <td align="right" width="15%">
            <?= _("Authentifizierung:")?>
        </td>
        <td width="35%">
            <select name="auth_plugins">
               <option value=""><?= _("Alle")?></option>
               <option value="preliminary"><?= _("vorläufig")?></option>
               <? foreach($available_auth_plugins as $one) : ?>
                <option <?= ($user['auth_plugins'] == $one) ? 'selected' : ''?>><?= htmlready($one) ?></option>
                <? endforeach ?>
            </select>
        </td>
    </tr>
    <? if (count($datafields) > 0) : ?>
        <? $i = 0; foreach($datafields as $datafield) : ?>
            <? if ($i % 2 == 0) : ?>
            <tr>
            <? endif ?>
                <td align="right" nowrap><?= htmlReady($datafield->getName()) ?>:</td>
                <td>
                <? if ($datafield->getType() == 'bool') : ?>
                    <label>
                        <input type="radio" name="<?= $datafield->getID() ?>" value="" <?= strlen($user[$datafield->getID()]) === 0 ? 'checked' : '' ?>>
                        <?= _('egal') ?>
                    </label>
                    <label>
                        <input type="radio" name="<?= $datafield->getID()?>" value="1" <?= ($user[$datafield->getID()] === "1") ? 'checked' : '' ?>>
                        <?= _('ja') ?>
                    </label>
                    <label>
                        <input type="radio" name="<?= $datafield->getID()?>" value="0" <?= ($user[$datafield->getID()] === "0") ? 'checked' : '' ?>>
                        <?= _('nein') ?>
                    </label>
                <? elseif ($datafield->getType() == 'selectbox' || $datafield->getType() == 'radio') : ?>
                    <? $datafield_entry = DataFieldEntry::createDataFieldEntry($datafield);?>
                    <select name="<?= $datafield->getID()?>">
                        <option value="---ignore---"><?= _('alle') ?></option>
                        <? foreach ($datafield_entry->type_param as $pkey => $pval) :?>
                        <? $value = $datafield_entry->is_assoc_param ? (string) $pkey : $pval; ?>
                        <option value="<?= $value ?>" <?= ($user[$datafield->getID()] === $value) ? 'selected' : '' ?>><?= htmlReady($pval) ?></option>
                        <? endforeach ?>
                    </select>
                <? else : ?>
                    <input type="text" name="<?= $datafield->getID()?>" value="<?= htmlReady($user[$datafield->getID()]) ?>">
                <? endif ?>
                </td>
            <? if ($i % 2 != 0 && $i != 0) : ?>
            </tr>
            <? endif ?>
        <? $i++; endforeach ?>
        <? if ($i % 2 != 0) : ?>
            <td></td>
            <td></td>
        </tr>
        <? endif ?>
     <? endif ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" align="center">
                <?= Button::create(_('Suchen'), 'search')?>
                <?= Button::create(_('Zurücksetzen'), 'reset')?>
            </td>
        </tr>
    </tfoot>
</table>
</form>

<? if (count($users) > 0 && $users != 0) : ?>
<?= $this->render_partial("admin/user/_results", array('users' => $users)) ?>
<? endif ?>
