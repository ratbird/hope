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
    <tr class="table_row_even">
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
            <?= Button::create(_('Ausw�hlen'), 'auswaehlen', array('title' => _('Datenfeld ausw�hlen')))?>
        </td>
    </tr>
</table>
</form>

<? else : ?>

<h3><?= sprintf(_('Einen neuen Datentyp f�r die Kategorie "%s" erstellen'), $type_name) ?></h3>
<form action="<?= $controller->url_for('admin/datafields/new/'.$object_typ) ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
            <td><?= _("Name") ?>:</td>
            <td>
               <input type="text" name="datafield_name" size="60" maxlength="254" value="<?= htmlReady($this->flash['request']['datafield_name']) ?>">
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
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
        <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
            <td>
	            <label for="object_class">
		            <? if ($object_typ == 'sem'): ?>
			            <?= _('Veranstaltungskategorie') ?>:
		            <? elseif ($object_typ == 'inst'): ?>
			            <?= _('Einrichtungstyp') ?>:
		            <? else: ?>
			            <?= _('Nutzerstatus') ?>:
		            <? endif; ?>
	            </label>
            </td>
            <td>
                <? if ($object_typ == 'sem'): ?>
	                <select name="object_class[]" id="object_class">
                        <option value="NULL"><?= _('alle') ?></option>
                        <? foreach ($GLOBALS['SEM_CLASS'] as $key=>$val): ?>
                            <option value="<?= $key ?>"><?= htmlReady($val['name']) ?> </option>
                        <? endforeach; ?>
                <? elseif ($object_typ== 'inst'): ?>
		                <select name="object_class[]" id="object_class">
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
        <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
            <td><?= _("ben�tigter Status") ?>:</td>
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
        <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
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
        <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
            <td>
               <?= _("Reihenfolge") ?>:
            </td>
            <td>
                <input type="text" maxlength="10" size="2" value="<?= htmlReady($this->flash['request']['priority']) ?>" name="priority">
           </td>
        </tr>
        <? if (in_array($object_typ, array('sem'))): ?>
             <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
                <td>
                   <?= _("Pflichtfeld") ?>:
                </td>
                <td>
                    <input type="checkbox" name="is_required" value="true" <?= ($this->flash['request']['is_required']?'checked="checked"':'') ?>>
               </td>
            </tr>
            <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
                <td>
                   <?= _("Beschreibung") ?>:
                </td>
                <td>
                     <textarea cols="58" rows="3" name="description" id="description"><?= htmlReady($this->flash['request']['description']) ?></textarea>
               </td>
            </tr>
        <? endif ?>
        <? if (in_array($object_typ , array( 'user'))): ?>
              <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
                <td>
                    <label for="is_userfilter">
                        <?= _('M�gliche Bedingung f�r Anmelderegel') ?>:
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="is_userfilter" id="is_userfilter" value="1" <?= $this->flash['request']['is_userfilter'] ? 'checked="checked"':'' ?>  >
                </td>
            </tr>
        <? endif; ?>
        <tr>
            <td colspan="2" align="center">
                <?= Button::create(_('Anlegen'),'anlegen', array('title' => _('Neues Datenfeld anlegen')))?>
                <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/datafields'), array('title' => _('Zur�ck zur �bersicht')))?>
            </td>
        </tr>
    </table>
</form>

<? endif ?>


<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/admin-sidebar.png');
$sidebar->setTitle(_('Datenfelder'));

$actions = new ActionsWidget();
$actions->addLink(_('Neues Datenfeld anlegen'),$controller->url_for('admin/datafields/new/'.$class_filter), 'icons/16/blue/add.png');
$sidebar->addWidget($actions);


$widget = new SidebarWidget();
$widget->setTitle(_('Filter'));
$widget->addElement(new WidgetElement($this->render_partial('admin/datafields/class_filter', compact('allclasses', 'class_filter'))));
$sidebar->addWidget($widget);
