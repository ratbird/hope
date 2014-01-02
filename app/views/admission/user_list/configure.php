<?php
use Studip\Button, Studip\LinkButton;

//Infobox:
$info = array();
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Nutzerlisten dienen dazu, um Sonderfälle erfassen zu ".
                        "können, die in Anmeldeverfahren gesondert behandelt ".
                        "werden sollen (Härtefälle etc.).");
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Stellen Sie hier ein, wie die Chancen bei der ".
                        "Platzverteilung verändert werden sollen. Ein Wert ".
                        "von 1 bedeutet normale Verteilung, ein Wert kleiner ".
                        "als 1 führt zur Benachteiligung, mit einem Wert ".
                        "größer als 1 werden die betreffenden Personen ".
                        "bevorzugt.");

$infobox = array(
    array("kategorie" => _('Informationen:'),
          "eintrag" => $info
    )
);
$infobox = array('content' => $infobox,
                 'picture' => 'infobox/administration.png'
);

?>
<?= $this->render_partial('dialog/confirm_dialog') ?>
<h2><?= ($userlist && $userlist->getId()) ? _('Nutzerliste bearbeiten') : _('Nutzerliste anlegen') ?></h2>
<form action="<?= $controller->url_for('admission/userlist/save', (($userlist && $userlist->getId()) ? $userlist->getId() : '')) ?>" method="post">
    <div class="table_row_<?= TextHelper::cycle('even', 'odd'); ?> admission_data">
        <div class="admission_label"><?= _('Name der Nutzerliste:') ?></div>
        <div class="admission_value">
            <input type="text" size="60" maxlength="255" name="name" value="<?= $userlist ? htmlReady($userlist->getName()) : '' ?>"/>
        </div>
    </div>
    <div class="table_row_<?= TextHelper::cycle('even', 'odd'); ?> admission_data">
        <div class="admission_label"><?= _('Faktor zur Modifikation der Platzverteilung:') ?></div>
        <div class="admission_value" id="factordiv">
            <input type="text" size="4" maxlength="255" name="factor" id="factor" value="<?= $userlist ? $userlist->getFactor() : '1' ?>"/>
            <div id="factor-slider"></div>
            <script>
                $(function() {
                    $('#factor-slider').slider({
                        range: "max",
                        min: 0.25,
                        max: 3,
                        value: <?= $userlist ? floatval($userlist->getFactor()) : 1 ?>,
                        step: 0.25,
                        slide: function(event, ui) {
                            $('#factor').val(ui.value);
                            $('#factorval').html(ui.value);
                        }
                    });
                    $('#factor-slider').css('width', 100);
                    $('#factor').val($('#factor-slider').slider('value'));
                    $('#factor').css('display', 'none');
                    $('#factordiv').prepend('<span id="factorval">'+$('#factor-slider').slider('value')+'</span>');
                });
            </script>
        </div>
    </div>
    <div class="table_row_<?= TextHelper::cycle('even', 'odd'); ?> admission_data">
        <div class="admission_label"><?= _('NutzerInnen:') ?></div>
        <div class="admission_value" id="search">
            <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="image" src="<?= Assets::image_path('icons/16/yellow/arr_2down') ?>"
                   <?= tooltip(_('NutzerIn hinzufügen')) ?> border="0" name="add_user">
            <?= $search ?>
            <br/><br/>
            <div id="users">
            <?php
                if ($userlist) {
                    foreach ($userlist->getUsers() as $userId => $assigned) {
            ?>
            <div id="user_<?= $userId ?>" class="userlist_user">
                <?= get_fullname($userId, 'full_rev').' ('.get_username($userId).')' ?>
                <input type="hidden" name="users[]" value="<?= $userId ?>"/>
                <a href="<?= $controller->url_for('admission/userlist/delete_user', 
                    $userId, $userlist->getId()) ?>"
                    onclick="return STUDIP.Admission.removeUserFromUserlist('<?= $userId ?>')">
                    <?= Assets::img('icons/16/blue/trash.png', 
                        array('alt' => _('Diesen Eintrag löschen'), 
                              'title' => _('Diesen Eintrag löschen'))); ?>
                </a>
            </div>
            <?php
                    }
            ?>
            <?php } else { ?>
                <span id="nousers">
                    <i><?= _('Sie haben noch niemanden hinzugefügt.') ?></i>
                </span>
                <br/>
            <?php } ?>
            </div>
        </div>
    </div>
    <div class="table_row_<?= TextHelper::cycle('even', 'odd'); ?> admission_buttons">
        <?= Button::createAccept(_('Speichern'), 'submit') ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admission/userlist')) ?>
    </div>
</form>