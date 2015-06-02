<? use Studip\Button, Studip\LinkButton; ?>

<form method="POST" action="<?=URLHelper::getLink('?change_global_settings=TRUE')?>">
    <?= CSRFProtection::tokenTag() ?>

<table class="default zebra" style="margin: 0 1%; width: 98%;">
    <colgroup>
        <col width="4%">
        <col width="96%">
    </colgroup>
    <tbody>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Zulassen von <i>Raum</i>anfragen')?></b><br>
                <br>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="checkbox" name="allow_requests"
                           <? if (Config::get()->RESOURCES_ALLOW_ROOM_REQUESTS) echo 'checked'; ?>>
                    <?= _('NutzerInnen können im Rahmen der Veranstaltungsverwaltung Raumeigenschaften und konkrete Räume wünschen.') ?>
                </label>
                <br>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Sperrzeiten für die Bearbeitung von <i>Raum</i>belegungen') ?></b><br>
                <br>
                <?= _('Die <b>Bearbeitung</b> von Belegungen soll für alle lokalen Ressourcen-Administratoren zu folgenden Bearbeitungszeiten geblockt werden:') ?><br>
                <br>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="checkbox" name="locking_active"
                           <? if (Config::get()->RESOURCES_LOCKING_ACTIVE) echo 'checked'; ?>>
                    <?= _('Blockierung ist zu den angegebenen Sperrzeiten aktiv:') ?><br>
                </label>
                <br>

                <?= $this->render_partial('resources/display_locks', array('locks' => $locks['edit'])) ?>

                <?= LinkButton::create(_('Neue Sperrzeit anlegen'), URLHelper::getLink('?create_lock=edit')) ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?=_('Sperrzeiten für <i>Raum</i>belegungen') ?></b><br>
                <br>
                <?= _('Die <b>Belegung</b> soll für alle lokalen Ressourcen-Administratoren zu folgenden Belegungszeitenzeiten geblockt werden:') ?><br>
                <br>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="checkbox" name="assign_locking_active"
                           <? if (Config::get()->RESOURCES_ASSIGN_LOCKING_ACTIVE) echo 'checked'; ?>>
                    <?= _('Blockierung ist zu den angegebenen Sperrzeiten aktiv:') ?><br>
                </label>
                <br>

                <?= $this->render_partial('resources/display_locks', array('locks' => $locks['assign'])) ?>

                <?= LinkButton::create(_('Neue Sperrzeit anlegen'), URLHelper::getLink('?create_lock=assign')) ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Optionen beim Bearbeiten von Anfragen') ?></b><br>
                <br>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <?= _('Anzahl der Belegungen, ab der Räume dennoch mit Einzelterminen passend belegt werden können:') ?>
                    <input type="text" size="5" maxlength="10"
                           name="allow_single_assign_percentage"
                           value="<?= Config::get()->RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE ?>">%
                    <br>
                </label>

                <label>
                    &nbsp;&nbsp;&nbsp;
                    <?= _('Anzahl ab der Einzeltermine gruppiert bearbeitet werden sollen:') ?>
                    <input type="text" size="3" maxlength="5"
                           name="allow_single_date_grouping"
                           value="<?= Config::get()->RESOURCES_ALLOW_SINGLE_DATE_GROUPING ?>"><br>
                </label>
                <br>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Einordnung von <i>Räumen</i> in Orga-Struktur') ?></b><br>
                <br>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="checkbox" name="enable_orga_classify"
                           <? if (Config::get()->RESOURCES_ENABLE_ORGA_CLASSIFY) echo 'checked'; ?>>
                    <?= _('<i>Räume</i> können Fakultäten und Einrichtungen unabhängig von Besitzerrechten zugeordnet werden.')?><br>
                </label>
                <br>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Anlegen von <i>Räumen</i>') ?></b><br>
                <br>
                <label>
                    <?= _('Das Anlegen von <i>Räumen</i> kann nur durch folgende Personenkreise vorgenommen werden:') ?><br>
                    <br>
                    &nbsp;&nbsp;&nbsp;
                    <select name="allow_create_resources">
                        <option value="1" <? if (Config::get()->RESOURCES_ALLOW_CREATE_ROOMS == 1) echo 'selected'; ?>>
                            <?= _('NutzerInnen ab globalem Status Tutor') ?>
                        </option>
                        <option value="2" <? if (Config::get()->RESOURCES_ALLOW_CREATE_ROOMS == 2) echo 'selected'; ?>>
                            <?= _('NutzerInnen ab globalem Status Admin') ?>
                        </option>
                        <option value="3" <? if (Config::get()->RESOURCES_ALLOW_CREATE_ROOMS == 3) echo 'selected'; ?>>
                            <?= _('nur globale Ressourcenadministratoren') ?>
                        </option>
                    </select>
                    <br>
                </label>
                <br>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Vererbte Berechtigungen von Veranstaltungen und Einrichtungen für Ressourcen')?></b><br>
                <br>
                <?= _('Mitglieder von Veranstaltungen oder Einrichtungen erhalten '
                     .'folgende Rechte in Ressourcen, die diesen Veranstaltungen '
                     .'oder Einrichtungen gehören:') ?><br>
                <br>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="inheritance_rooms" value="1"
                           <? if (Config::get()->RESOURCES_INHERITANCE_PERMS_ROOMS == 1) echo 'checked'; ?>>
                    <?= _('die lokalen Rechte der Einrichtung oder Veranstaltung werden übertragen') ?>
                    <br>
                </label>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="inheritance_rooms" value="2"
                           <? if (Config::get()->RESOURCES_INHERITANCE_PERMS_ROOMS == 2) echo 'checked'; ?>>
                    <?= _('nur Autorenrechte (eigene Belegungen anlegen und bearbeiten)') ?>
                    <br>
                </label>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="inheritance_rooms" value="3"
                           <? if (Config::get()->RESOURCES_INHERITANCE_PERMS_ROOMS == 3) echo 'checked'; ?>>
                    <?= _('keine Rechte') ?>
                    <br>
                </label>
                <br>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Vererbte Berechtigungen von Veranstaltungen und Einrichtungen für <i>Räume</i>') ?></b><br>
                <br>
                <?= _('Mitglieder von Veranstaltungen oder Einrichtungen erhalten '
                     .'folgende Rechte in <i>Räumen</i>, die diesen Veranstaltungen '
                     .'oder Einrichtungen gehören:') ?><br>
                <br>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="inheritance" value="1"
                           <? if (Config::get()->RESOURCES_INHERITANCE_PERMS == 1) echo 'checked'; ?>>
                    <?= _('die lokalen Rechte der Einrichtung oder Veranstaltung werden übertragen') ?><br>
                </label>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="inheritance" value="2"
                           <? if (Config::get()->RESOURCES_INHERITANCE_PERMS == 2) echo 'checked'; ?>>
                    <?= _('nur Autorenrechte (eigene Belegungen anlegen und bearbeiten)') ?><br>
                </label>
                <label>
                    &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="inheritance" value="3"
                           <? if (Config::get()->RESOURCES_INHERITANCE_PERMS == 3) echo 'checked'; ?>>
                    <?= _('keine Rechte') ?><br>
                </label>
                <br>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td class="table_footer" colspan="2" style="text-align:center">
                <?= Button::create(_('Übernehmen'), '_send_settings') ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
<br><br>
