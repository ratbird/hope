<form method="POST" name="edit" action="<?= URLHelper::getLink() ?>" class="studip_form">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend><?=_('Verwaltung der Einrichtungsgrunddaten')?></legend>
        <label for="Name"><?= _("Name") ?></label>
        <input type="text" <?= LockRules::Check($institute['Institut_id'], 'name') ? 'readonly disabled' : '' ?> name="Name"
               value="<?= htmlReady(Request::get('Name', $institute['Name'])) ?>">
        <label for="Fakultaet"><?= _("Fakultät") ?></label>
        <? if ($num_institutes): ?>
            <small>
                <?= _('Diese Einrichtung hat den Status einer Fakultät.') ?><br>
                <?= sprintf(_('Es wurden bereits %u andere Einrichtungen zugeordnet.'), $num_institutes) ?>
            </small>
            <input type="hidden" name="Fakultaet" value="<?= $institute['Institut_id'] ?>">
        <? else: ?>
            <select <?= !$may_edit_faculty ? 'readonly disabled' : '' ?> name="Fakultaet">
            <? if ($GLOBALS['perm']->have_perm('root')): ?>
                <option value="<?= $institute['Institut_id'] ?>" <?= ($institute['fakultaets_id'] == Request::option('Fakultaet', $institute['Institut_id'])) ? 'selected' : '' ?>>
                    <?= _('Diese Einrichtung hat den Status einer Fakultät.') ?>
                </option>
            <? endif; ?>
            <? if ($may_edit_faculty) : ?>
                <? foreach ($faculties as $id => $name): ?>
                    <option value="<?= $id ?>" <?= ($id == Request::option('Fakultaet', $institute['fakultaets_id'])) ? 'selected' : '' ?>>
                        <?= htmlReady($name) ?>
                    </option>
                <? endforeach; ?>
            <? else : ?>
                <option value="<?= $institute['fakultaets_id'] ?>">
                <?= htmlReady($institute['fak_name']) ?>
                </option>
            <? endif; ?>
            </select>
        <? endif; ?>
        <label for="type"><?= _("Bezeichnung") ?></label>
        <select name="type" <?= LockRules::Check($institute['Institut_id'], 'type') ? 'readonly disabled' : '' ?> >
        <? foreach ($GLOBALS['INST_TYPE'] as $i => $inst_type): ?>
            <option value="<?= $i ?>" <?= (Request::int('type', $institute['type']) == $i) ? 'selected' : '' ?>>
                <?= htmlReady($inst_type['name']) ?>
            </option>
        <? endforeach; ?>
        </select>
        <label for="strasse"><?= _("Straße") ?></label>
        <input type="text" <?= LockRules::Check($institute['Institut_id'], 'strasse') ? 'readonly disabled' : '' ?> name="strasse"
               value="<?= htmlReady(Request::get('strasse', $institute['Strasse'])) ?>">
        <label for="plz"><?= _("Ort") ?></label>
        <input type="text" <?= LockRules::Check($institute['Institut_id'], 'plz') ? 'readonly disabled' : '' ?> name="plz"
               value="<?= htmlReady(Request::get('plz', $institute['Plz'])) ?>">
        <label for="telefon"><?= _("Telefonnummer") ?></label>
        <input type="text" <?= LockRules::Check($institute['Institut_id'], 'telefon') ? 'readonly disabled' : '' ?> name="telefon" maxlength=32
               value="<?= htmlReady(Request::get('telefon', $institute['telefon'])) ?>">
        <label for="fax"><?= _("Faxnummer") ?></label>
        <input type="text" <?= LockRules::Check($institute['Institut_id'], 'fax') ? 'readonly disabled' : '' ?> name="fax" maxlength=32
               value="<?= htmlReady(Request::get('fax', $institute['fax'])) ?>">
        <label for="email"><?= _("E-Mail-Adresse") ?></label>
        <input type="text" <?= LockRules::Check($institute['Institut_id'], 'email') ? 'readonly disabled' : '' ?> name="email"
               value="<?= htmlReady(Request::get('email', $institute['email'])) ?>">
        <label for="home"><?= _("Homepage") ?></label>
        <input type="text" <?= LockRules::Check($institute['Institut_id'], 'url') ? 'readonly disabled' : '' ?> name="home"
               value="<?= htmlReady(Request::get('home', $institute['url'])) ?>">

        <? if (get_config('LITERATURE_ENABLE') && $institute['Institut_id'] == $institute['fakultaets_id']):
           // choose preferred lit plugin ?>
            <label for="lit_plugin_name"><?= _("Bevorzugter Bibliothekskatalog") ?></label>
            <select name="lit_plugin_name">
            <? foreach (StudipLitSearch::GetAvailablePlugins() as $name => $title): ?>
                <option value="<?= $name ?>" <?= ($name == Request::get('lit_plugin_name', $institute['lit_plugin_name'])) ? 'selected' : '' ?>>
                    <?= htmlReady($title) ?>
               </option>
            <? endforeach; ?>
            </select>
        <? endif; ?>

        <? if ($GLOBALS['perm']->have_perm('root')):
           // Select lockrule to apply ?>
            <label for="lock_rule"><?= _("Sperrebene") ?></label>
            <select name="lock_rule">
                <option value="">&nbsp;</option>
                <? foreach(LockRule::findAllByType('inst') as $rule): ?>
                    <option value="<?= $rule->getId() ?>" <?= ($rule->getId() == Request::option('lock_rule', $institute['lock_rule'])) ? 'selected' : '' ?>>
                        <?= htmlReady($rule->name) ?>
                    </option>
                <? endforeach;?>
            </select>
        <? endif; ?>

        <? foreach ($datafields as $key => $datafield): ?>
        <label for="datafield_<?=key?>"><?= htmlReady($datafield['title']) ?></label>
            <?= $datafield['value'] ?>
        <? endforeach; ?>

        <div class="submit_wrapper">
            <? if ($i_view != 'new' && isset($institute['Institut_id'])): ?>
                <input type="hidden" name="i_id" value="<?= $institute['Institut_id'] ?>">
                <?= Studip\Button::create(_('Übernehmen'), 'i_edit') ?>
                <?= Studip\Button::create(_('Löschen'), 'i_trykill', !$may_delete ? array('disabled' => '') : array()) ?>
                <? if(!$may_delete && strlen($reason_txt) > 0): ?>
                    <?= Assets::img('icons/16/black/info-circle.png', tooltip2($reason_txt)) ?>
                <? endif; ?>
                    
            <? else: ?>
                <?= Studip\Button::create(_('Anlegen'), 'create') ?>
            <? endif; ?>
           <input type="hidden" name="i_view" value="<?= $i_view == 'new' ? 'create' : $i_view  ?>">
        </div>
    </fieldset>
</form>
<?
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/institute-sidebar.png');
$widget = new ActionsWidget();
$widget->addLink(_('Infobild ändern'), URLHelper::getLink('dispatch.php/institute/avatar/update/' . $institute['Institut_id']), 'icons/16/black/edit.png');
$sidebar->addWidget($widget);
?>