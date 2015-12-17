<?php
use Studip\Button, Studip\LinkButton;

Helpbar::get()->addPlainText(_('Info'), "Personenlisten dienen dazu, um Sonderf�lle erfassen zu ".
                                        "k�nnen, die in Anmeldeverfahren gesondert behandelt ".
                                        "werden sollen (H�rtef�lle etc.).");
Helpbar::get()->addPlainText(_('Info'), "Stellen Sie hier ein, wie die Chancen bei der ".
                                        "Platzverteilung ver�ndert werden sollen. Ein Wert ".
                                        "von 1 bedeutet normale Verteilung, ein Wert kleiner ".
                                        "als 1 f�hrt zur Benachteiligung, mit einem Wert ".
                                        "gr��er als 1 werden die betreffenden Personen ".
                                        "bevorzugt.");
?>
<?= $this->render_partial('dialog/confirm_dialog') ?>
<?= $error ? $error : '' ?>
<h1><?= ($userlist_id) ? _('Personenliste bearbeiten') : _('Personenliste anlegen') ?></h1>
<form class="default" action="<?= $controller->url_for('admission/userlist/save', $userlist_id) ?>" method="post">
    <label>
        <span class="required">
            <?= _('Name der Personenliste') ?>
        </span>
        <input type="text" size="60" maxlength="255" name="name" value="<?= $userlist ? htmlReady($userlist->getName()) : '' ?>"/>
    </label>
    <br/>
    <label for="factor">
        <?= _('Faktor zur Modifikation der Platzverteilung') ?>
    </label>
    <div id="factordiv">
        <input type="text" size="4" maxlength="255" name="factor" id="factor" value="<?= $userlist_id ? $userlist->getFactor() : 1 ?>"/>
        <div id="factor-slider"></div>
        <script>
            $(function() {
            <?php
            $factor = 3;
            $realfactor = 1;
            if ($userlist) {
                $realfactor = $userlist->getFactor();
                if ($userlist->getFactor() < 1) {
                    $factor = intval($realfactor*4);
                } else if ($realfactor <= 5) {
                    $factor = $realfactor+2;
                } else {
                    $factor = 8;
                }
            }
            ?>
            var factor = <?= $realfactor ?>;
            var realfactor = <?= $factor ?>;
                $('#factor-slider').slider({
                    range: "max",
                    min: 0,
                    max: 8,
                    value: realfactor,
                    step: 1,
                    slide: function(event, ui) {
                    if (ui.value < 3) {
                    factor = ui.value/4;
                } else if (ui.value < 8) {
                    factor = ui.value-2;
                    } else {
                    factor = 10;
                    }
                        $('#factor').val(factor);
                        $('#factorval').html(factor);
                    }
                });
                $('#factor-slider').css('width', 150);
                $('#factor').val(factor);
                $('#factor').css('display', 'none');
                $('#factordiv').prepend('<span id="factorval">'+factor+'</span>');
            });
        </script>
    </div>
    <br/>
    <table class="default">
        <caption>
            <?= _('Personen') ?>
            <span class="actions">
                <?= MultiPersonSearch::get('add_userlist_member_' . $userlist_id)
                    ->setTitle(_('Personen zur Liste hinzuf�gen'))
                    ->setSearchObject($userSearch)
                    ->setDefaultSelectedUser(array_map(function ($u) { return $u->id; }, $users))
                    ->setDataDialogStatus(Request::isXhr())
                    ->setJSFunctionOnSubmit(Request::isXhr() ? 'jQuery(this).closest(".ui-dialog-content").dialog("close");' : false)
                    ->setExecuteURL($controller->url_for('admission/userlist/add_members', $userlist_id))
                    ->render() ?>
            </span>
        </caption>
        <thead>
            <tr>
                <th></th>
                <th><?= _('Name') ?></th>
                <th class="actions"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? if (count($users) === 0): ?>
            <tr>
                <td colspan="3">
                    <?= _('Niemand ist in die Liste eingetragen.') ?>
                </td>
            </tr>
        <? else: $i = 1; ?>
            <? foreach ($users as $u) : ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $u->username) ?>">
                            <?= Avatar::getAvatar($u->id, $u->username)->getImageTag(Avatar::SMALL) ?>
                        </a>
                    </td>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $u->username) ?>">
                            <?= $u->getFullname('full_rev') . ' (' . $u->username . ')' ?>
                        </a>
                        <input type="hidden" name="users[]" value="<?= $u->id ?>"/>
                    </td>
                    <td class="actions">
                        <a href="<?= $controller->url_for('admission/userlist/delete_member',
                            $userlist_id, $u->id) ?>" onclick="return confirm('<?= sprintf(
                            _('Soll %s wirklich von der Liste entfernt werden?'), $u->getFullname()) ?>');">
                            <?= Icon::create('trash', 'clickable') ?>
                        </a>
                    </td>
                </tr>
            <? endforeach; ?>
        <? endif; ?>
        </tbody>
    </table>
    <?= CSRFProtection::tokenTag() ?>
    <footer>
        <?= Button::createAccept(_('Speichern'), 'submit') ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admission/userlist')) ?>
    </footer>
</form>
