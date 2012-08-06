<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= (isset($flash['error'])) ? MessageBox::error($flash['error'], $flash['error_detail']) : '' ?>

<? if (empty($object_typ)) : ?>
<h3><?= _('Verwaltung von generischen Datenfeldern') ?></h3>
<form action="<?= $controller->url_for('admin/datafields/new/') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tr class="steel1">
        <td>
        <?= _('Datenfeldtyp:') ?>
        </td>
        <td>
        <select name="datafield_type">
        <? foreach ($allclasses as $key => $class): ?>
             <option value = "<?= $key ?>">
                 <?= htmlReady($class) ?>
             </option>
        <? endforeach; ?>
        </select>
        </td>
    </tr>
    <tr>
        <td align="center" colspan="2">
            <?= Button::create(_('Auswählen'), 'auswaehlen', array('title' => _('Datenfeld auswählen')))?>
        </td>
    </tr>
</table>
</form>

<? else : ?>

<h3><?= sprintf(_('Einen neuen Datentyp für die Kategorie "%s" erstellen'), $type_name) ?></h3>
<form action="<?= $controller->url_for('admin/datafields/new/'.$object_typ) ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td><?= _("Name") ?>:</td>
            <td>
               <input type="text" name="datafield_name" size="60" maxlength="254" value="<?= htmlReady($this->flash['request']['datafield_name']) ?>">
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td><?= _("Feldtyp") ?>:</td>
            <td>
            <select name="datafield_typ" id="datafield_typ">
               <? foreach (DataFieldEntry::getSupportedTypes() as $param): ?>
                    <option value="<?= $param ?>">
                        <?= htmlReady($param) ?>
                    </option>
                <? endforeach; ?>
            </select>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td><? if ($object_typ == 'sem') echo _('Veranstaltungskategorie'); elseif ($object_typ == 'inst') echo _('Einrichtungstyp'); else echo _('Nutzerstatus'); ?>:</td>
            <td>
                <? if ($object_typ == 'sem'): ?>
                    <select name="object_class">;
                        <option value="NULL"><?= _('alle') ?></option>
                        <? foreach ($GLOBALS['SEM_CLASS'] as $key=>$val): ?>
                            <option value="<?= $key ?>"><?= htmlReady($val['name']) ?> </option>
                        <? endforeach; ?>
                <? elseif ($object_typ== 'inst'): ?>
                    <select name="object_class">;
                        <option value="NULL"><?= _('alle') ?></option>
                        <? foreach ($GLOBALS['INST_TYPE'] as $key=>$val): ?>
                            <option value="<?= $key ?>"><?= htmlReady($val['name']) ?> </option>
                        <? endforeach; ?>
                <? else: ?>
                     <select multiple size="7" name="object_class[]">
                        <option value="NULL"><?= _('alle') ?></option>
                            <option value="1">user</option>
                            <option value="2">autor</option>
                            <option value="4">tutor</option>
                            <option value="8">dozent</option>
                            <option value="16">admin</option>
                            <option value="32">root</option>
                <? endif; ?>
                    </select>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td><?= _("benötigter Status") ?>:</td>
            <td>
                <select name="edit_perms">
                    <option value="user">user</option>
                    <option value="autor">autor</option>
                    <option value="tutor">tutor</option>
                    <option value="dozent">dozent</option>
                    <option value="admin">admin</option>
                    <option value="root">root</option>
                </select>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td><?= _("Sichtbarkeit") ?>:</td>
            <td>
                <select name="visibility_perms">
                    <option value="user">user</option>
                    <option value="autor">autor</option>
                    <option value="tutor">tutor</option>
                    <option value="dozent">dozent</option>
                    <option value="admin">admin</option>
                    <option value="root">root</option>
                </select>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td>
               <?= _("Reihenfolge") ?>:
            </td>
            <td>
                <input type="text" maxlength="10" size="2" value="<?= htmlReady($this->flash['request']['priority']) ?>" name="priority">
           </td>
        </tr>
        <? if (in_array($object_typ, array('sem'))): ?>
             <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
                <td>
                   <?= _("Pflichtfeld") ?>:
                </td>
                <td>
                    <input type="checkbox" name="mandatory" value="true" <?= ($this->flash['request']['priority']?'checked="checked"':'') ?>>
               </td>
            </tr>
            <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
                <td>
                   <?= _("Beschreibung") ?>:
                </td>
                <td>
                     <textarea cols="58" rows="3" name="description" id="description"><?= htmlReady($this->flash['request']['description']) ?></textarea>
               </td>
            </tr>
        <? endif ?>
        <tr>
            <td colspan="2" align="center">
                <?= Button::create(_('Anlegen'),'anlegen', array('title' => _('Neues Datenfeld anlegen')))?>
                <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/datafields'), array('title' => _('Zurück zur Übersicht')))?>
            </td>
        </tr>
    </table>
</form>

<? endif ?>

<? //infobox
$infobox = array(
    'picture' => 'infobox/administration.png',
    'content' => array(
        array(
            'kategorie' => _('Aktionen:'),
            'eintrag'   => array(
                array(
                    'icon' => 'icons/16/black/arr_2right.png',
                    'text' => $this->render_partial('admin/datafields/class_filter', compact('allclasses', 'class_filter'))
                ),
                array(
                    'text' => '<a href="'.$controller->url_for('admin/datafields/new/'.$class_filter).'">'._('Neues Datenfeld anlegen').'</a>',
                    'icon' => 'icons/16/black/plus.png',
                )
            )
        ),
        array(
            'kategorie' => _("Information"),
            'eintrag'   => array(
                array(
                   "text" => _("Hier haben Sie die Möglichkeit, ein neues Datenfeld im gewählten Bereich anzulegen."),
                   "icon" => "icons/16/black/info.png"
                )
            )
        )
    )
);
