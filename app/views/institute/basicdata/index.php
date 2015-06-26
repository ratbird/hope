<form method="POST" name="edit" action="<?= URLHelper::getLink() ?>">
    <?= CSRFProtection::tokenTag() ?>

    <table class="default">
        <caption>
            <?=_('Verwaltung der Einrichtungsgrunddaten')?>
        </caption>
        <thead>
            <tr>
                <th>&nbsp;
                </th>
                <th>&nbsp;
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= _('Name:') ?></td>
                <td>
                    <input type="text" <?= LockRules::Check($institute['Institut_id'], 'name') ? 'readonly disabled' : '' ?> name="Name"
                           value="<?= htmlReady(Request::get('Name', $institute['Name'])) ?>">
                </td>
            </tr>

            <tr>
                <td><?= _('Fakultät:') ?></td>
                <td>
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
                </td>
            </tr>

            <tr>
                <td><?= _('Bezeichnung:') ?></td>
                <td>
                    <select name="type" <?= LockRules::Check($institute['Institut_id'], 'type') ? 'readonly disabled' : '' ?> >
                    <? foreach ($GLOBALS['INST_TYPE'] as $i => $inst_type): ?>
                        <option value="<?= $i ?>" <?= (Request::int('type', $institute['type']) == $i) ? 'selected' : '' ?>>
                            <?= htmlReady($inst_type['name']) ?>
                        </option>
                    <? endforeach; ?>
                </td>
            </tr>

            <tr>
                <td><?= _('Straße:') ?></td>
                <td>
                    <input type="text" <?= LockRules::Check($institute['Institut_id'], 'strasse') ? 'readonly disabled' : '' ?> name="strasse"
                           value="<?= htmlReady(Request::get('strasse', $institute['Strasse'])) ?>">
                </td>
            </tr>
            <tr>
                <td><?= _('Ort:') ?></td>
                <td>
                    <input type="text" <?= LockRules::Check($institute['Institut_id'], 'plz') ? 'readonly disabled' : '' ?> name="plz"
                           value="<?= htmlReady(Request::get('plz', $institute['Plz'])) ?>">
                </td>
            </tr>
            <tr>
                <td><?= _('Telefonnummer:') ?></td>
                <td>
                    <input type="tel" <?= LockRules::Check($institute['Institut_id'], 'telefon') ? 'readonly disabled' : '' ?> name="telefon" maxlength=32
                           value="<?= htmlReady(Request::get('telefon', $institute['telefon'])) ?>">
                </td>
            </tr>
            <tr>
                <td><?=_("Faxnummer:")?></td>
                <td>
                    <input type="tel" <?= LockRules::Check($institute['Institut_id'], 'fax') ? 'readonly disabled' : '' ?> name="fax" maxlength=32
                           value="<?= htmlReady(Request::get('fax', $institute['fax'])) ?>">
                </td>
            </tr>
            <tr>
                <td><?= _('E-Mail-Adresse:') ?></td>
                <td>
                    <input type="email" <?= LockRules::Check($institute['Institut_id'], 'email') ? 'readonly disabled' : '' ?> name="email"
                           value="<?= htmlReady(Request::get('email', $institute['email'])) ?>">
                </td>
            </tr>
            <tr>
                <td><?= _('Homepage:') ?></td>
                <td>
                    <input type="url" <?= LockRules::Check($institute['Institut_id'], 'url') ? 'readonly disabled' : '' ?> name="home"
                           value="<?= htmlReady(Request::get('home', $institute['url'])) ?>">
                </td>
            </tr>

        <? if (get_config('LITERATURE_ENABLE') && $institute['Institut_id'] == $institute['fakultaets_id']):
           // choose preferred lit plugin ?>
            <tr class="<?= TextHelper::cycle('hover_even', 'hover_odd') ?>">
                <td><?= _('Bevorzugter Bibliothekskatalog:') ?></td>
                <td>
                    <select name="lit_plugin_name">
                    <? foreach (StudipLitSearch::GetAvailablePlugins() as $name => $title): ?>
                        <option value="<?= $name ?>" <?= ($name == Request::get('lit_plugin_name', $institute['lit_plugin_name'])) ? 'selected' : '' ?>>
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
                <td><?= _('Sperrebene') ?></td>
                <td>
                    <select name="lock_rule">
                        <option value="">&nbsp;</option>
                    <? foreach(LockRule::findAllByType('inst') as $rule): ?>
                        <option value="<?= $rule->getId() ?>" <?= ($rule->getId() == Request::option('lock_rule', $institute['lock_rule'])) ? 'selected' : '' ?>>
                            <?= htmlReady($rule->name) ?>
                        </option>
                    <? endforeach;?>
                    </select>
                </td>
            </tr>
        <? endif; ?>

        <? foreach ($datafields as $datafield): ?>
            <tr class="<?= TextHelper::cycle('hover_even', 'hover_odd') ?>">
                <td style="color: <?= $datafield['color'] ?>">
                    <?= htmlReady($datafield['title']) ?>
                </td>
                <td><?= $datafield['value'] ?></td>
            </tr>
        <? endforeach; ?>

        </tbody>
        <tfoot>
            <tr>
                <td class="table_footer" colspan="2" style="text-align: center;">
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
                </td>
            </tr>
        </foot>
    </table>
</form>
<?
    $aktionen = array();
    $aktionen[] = array(
        'icon' => 'icons/16/black/edit.png',
        'text' => sprintf('<a href="%s">%s</a>',
                          URLHelper::getLink('dispatch.php/institute/avatar/update/' . $institute['Institut_id']),
                          _('Bild ändern')),
    );
    $aktionen[] = array(
        'icon' => 'icons/16/black/trash.png',
        'text' => sprintf('<a href="%s">%s</a>',
                          URLHelper::getLink('dispatch.php/institute/avatar/delete/' . $institute['Institut_id']),
                          _('Bild löschen')),
    );
    $template->infobox = array(
        'content' => array(
            array(
                'kategorie' => _('Aktionen:'),
                'eintrag'   => $aktionen
            )
        ),
        'picture' => InstituteAvatar::getAvatar($institute['Institut_id']),
    );
?>