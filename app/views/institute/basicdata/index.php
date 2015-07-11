<?= $question ?>
<form method="POST" name="edit" action="<?= $controller->url_for('institute/basicdata/store/' . $i_view) ?>">
    <?= CSRFProtection::tokenTag() ?>

    <table class="default nohover">
        <caption>
            <?= _('Verwaltung der Einrichtungsgrunddaten') ?>
        </caption>
        <tbody>
        <tr>
            <td>
                <label for="Name"><?= _('Name') ?></label>
            </td>
            <td>
                <input type="text" size="80" <?= LockRules::Check($institute->id, 'name') ? 'readonly disabled' : '' ?> name="Name" id="Name"
                       required value="<?= htmlReady(Request::get('Name', $institute->Name)) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="Fakultaet"><?= _('Fakultät') ?></label>
            </td>
            <td>
            <? if (count($institute->sub_institutes) > 0): ?>
                <small>
                    <?= _('Diese Einrichtung hat den Status einer Fakultät.') ?><br>
                    <?= sprintf(_('Es wurden bereits %u andere Einrichtungen zugeordnet.'), count($institute->sub_institutes)) ?>
                </small>
                <input type="hidden" name="Fakultaet" id="Fakultaet" value="<?= $institute->id ?>">
            <? else: ?>
                <select <?= !$may_edit_faculty ? 'readonly disabled' : '' ?> name="Fakultaet" id="Fakultaet">
                <? if ($GLOBALS['perm']->have_perm('root')): ?>
                    <option value="<?= $institute->id ?>" <?= ($institute->fakultaets_id == Request::option('Fakultaet', $institute->id)) ? 'selected' : '' ?>>
                        <?= _('Diese Einrichtung hat den Status einer Fakultät.') ?>
                    </option>
                <? endif; ?>
                <? if ($may_edit_faculty) : ?>
                    <? foreach ($faculties as $faculty): ?>
                        <option value="<?= $faculty->id ?>"
                                <? if ($faculty->id === $institute->id): echo 'disabled'; ?>
                                <? elseif ($faculty->id == Request::option('Fakultaet', $institute->fakultaets_id)): echo 'selected'; ?>
                                <? endif; ?>>
                            <?= htmlReady($faculty->name) ?>
                        </option>
                    <? endforeach; ?>
                <? else : ?>
                    <option value="<?= $institute->fakultaets_id ?>">
                        <?= htmlReady($institute->faculty->name) ?>
                    </option>
                <? endif; ?>
                </select>
            <? endif; ?>
            </td>
        </tr>
        <tr>
            <td>
                <label for="type"><?= _('Bezeichnung') ?></label>
            </td>
            <td>
                <select name="type" id="type" <?= LockRules::Check($institute->id, 'type') ? 'readonly disabled' : '' ?> >
                <? foreach ($GLOBALS['INST_TYPE'] as $i => $inst_type): ?>
                    <option value="<?= $i ?>" <?= (Request::int('type', $institute->type) == $i) ? 'selected' : '' ?>>
                        <?= htmlReady($inst_type['name']) ?>
                    </option>
                <? endforeach; ?>
               </select>
            </td>
        </tr>
        <tr>
            <td>
                <label for="strasse"><?= _('Straße') ?></label>
            </td>
            <td>
                <input type="text" size="80" <?= LockRules::Check($institute->id, 'strasse') ? 'readonly disabled' : '' ?> id="strasse" name="strasse"
                       value="<?= htmlReady(Request::get('strasse', $institute->strasse)) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="plz"><?= _('Ort') ?></label>
            </td>
            <td>
                <input type="text" size="80" <?= LockRules::Check($institute->id, 'plz') ? 'readonly disabled' : '' ?> id="plz" name="plz"
                       value="<?= htmlReady(Request::get('plz', $institute->plz)) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="telefon"><?= _('Telefonnummer') ?></label>
            </td>
            <td>
                <input type="text" size="80" <?= LockRules::Check($institute->id, 'telefon') ? 'readonly disabled' : '' ?> id="telefon" name="telefon" maxlength="32"
                       value="<?= htmlReady(Request::get('telefon', $institute->telefon)) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="fax"><?= _('Faxnummer') ?></label>
            </td>
            <td>
                <input type="text" size="80" <?= LockRules::Check($institute->id, 'fax') ? 'readonly disabled' : '' ?> id="fax" name="fax" maxlength="32"
                       value="<?= htmlReady(Request::get('fax', $institute->fax)) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="email"><?= _('E-Mail-Adresse') ?></label>
            </td>
            <td>
                <input type="text" size="80" <?= LockRules::Check($institute->id, 'email') ? 'readonly disabled' : '' ?> id="email" name="email"
                       value="<?= htmlReady(Request::get('email', $institute->email)) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <label for="home"><?= _('Homepage') ?></label>
            </td>
            <td>
                <input type="text" size="80" <?= LockRules::Check($institute->id, 'url') ? 'readonly disabled' : '' ?> id="home" name="home"
                       value="<?= htmlReady(Request::get('home', $institute->url)) ?>">
            </td>
        </tr>

    <? if (get_config('LITERATURE_ENABLE') && $institute->is_fak):
           // choose preferred lit plugin ?>
        <tr>
            <td>
                <label for="lit_plugin_name"><?= _('Bevorzugter Bibliothekskatalog') ?></label>
            </td>
            <td>
                <select id="lit_plugin_name" name="lit_plugin_name">
                <? foreach (StudipLitSearch::GetAvailablePlugins() as $name => $title): ?>
                    <option value="<?= $name ?>" <?= ($name == Request::get('lit_plugin_name', $institute->lit_plugin_name)) ? 'selected' : '' ?>>
                        <?= htmlReady($title) ?>
                   </option>
                <? endforeach; ?>
            </select>
            </td>
        </tr>
    <? endif; ?>

    <? if ($GLOBALS['perm']->have_perm('root')):
           // Select lockrule to apply ?>
        <tr>
            <td>
                <label for="lock_rule"><?= _('Sperrebene') ?></label>
            </td>
            <td>
                <select id="lock_rule" name="lock_rule">
                    <option value="">&nbsp;</option>
                <? foreach (LockRule::findAllByType('inst') as $rule): ?>
                    <option value="<?= $rule->getId() ?>" <?= ($rule->getId() == Request::option('lock_rule', $institute->lock_rule)) ? 'selected' : '' ?>>
                        <?= htmlReady($rule->name) ?>
                    </option>
                <? endforeach;?>
                </select>
            </td>
        </tr>
    <? endif; ?>

    <? foreach ($datafields as $key => $datafield): ?>
        <tr>
            <td style="color: <?= $datafield['color'] ?>">
                <label for="datafield_<?= $key ?>"><?= htmlReady($datafield['title']) ?></label>
            </td>
            <td>
                <?= $datafield['value'] ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align: center;">
            <? if ($i_view != 'new' && !$institute->isNew()): ?>
                <input type="hidden" name="i_id" value="<?= $institute->id ?>">
                <?= Studip\Button::createAccept(_('Übernehmen'), 'i_edit') ?>
                <?= Studip\LinkButton::create(_('Löschen'),
                                              $controller->url_for('institute/basicdata/index/' . $i_view, array('i_trykill' => 1)),
                                              !$may_delete ? array('disabled' => '') : array()) ?>
                <? if (!$may_delete && strlen($reason_txt) > 0): ?>
                    <?= tooltipIcon($reason_txt, true) ?>
                <? endif; ?>
            <? else: ?>
                <?= Studip\Button::create(_('Anlegen'), 'create') ?>
            <? endif; ?>
               <input type="hidden" name="i_view" value="<?= $i_view ?>">
            </td>
        </tr>
    </foot>
</table>
</form>
<?
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/institute-sidebar.png');

$widget = new ActionsWidget();
$widget->addLink(_('Infobild ändern'), URLHelper::getLink('dispatch.php/institute/avatar/update/' . $institute->id), 'icons/16/blue/edit.png');
if (InstituteAvatar::getAvatar($institute->id)->is_customized()) {
    $widget->addLink(_('Infobild löschen'), URLHelper::getLink('dispatch.php/institute/avatar/delete/' . $institute->id), 'icons/16/blue/trash.png');
}
$sidebar->addWidget($widget);
