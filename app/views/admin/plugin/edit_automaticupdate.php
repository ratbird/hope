<?
if (strpos($_SERVER['SERVER_NAME'], ':') !== false) {
    list($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']) =
        explode(':', $_SERVER['SERVER_NAME']);
}
if ($_SERVER['SERVER_NAME'] === "localhost" || $_SERVER['SERVER_NAME'] = "127.0.0.1") {
    $domain_warning = sprintf(_("Achtung, mit %s als Domain kann der Webhook-Aufruf von github nicht funktionieren."), $_SERVER['SERVER_NAME']);
}
$DOMAIN_STUDIP = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
$DOMAIN_STUDIP .= '://'.$_SERVER['SERVER_NAME'];

if ($_SERVER['HTTPS'] == 'on' && $_SERVER['SERVER_PORT'] != 443 ||
    $_SERVER['HTTPS'] != 'on' && $_SERVER['SERVER_PORT'] != 80) {
    $DOMAIN_STUDIP .= ':'.$_SERVER['SERVER_PORT'];
}
?>

<div>
    <? if (isset($msg)) : ?>
    <? foreach ($msg as $m) : ?>
        <?= $m ?>
    <? endforeach ?>
    <? endif ?>
</div>
<form action="<?= $controller->url_for('admin/plugin/edit_automaticupdate/'.$plugin['id']) ?>" method="post" class="studip_form" data-dialog>
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
    <?= MessageBox::info(_("Sie k�nnen gitlab, github.com oder dem neuen Stud.IP-Plugin-Marktplatz mitteilen, dass Ihr Stud.IP per Webhook �ber �nderungen im Code des Plugins benachrichtigt werden soll.")) ?>
    <fieldset>
        <legend><?= _("Einstellungen von Stud.IP") ?></legend>
        <label>
            <?= _("URL, von der das Plugin als ZIP-Datei bezogen werden soll") ?>
            <input type="url" name="automatic_update_url" value="<?= htmlReady($plugin['automatic_update_url']) ?>">
        </label>
        <label>
            <?= _("Absichern �ber Sicherheitstoken (optional)") ?>
            <input type="checkbox" name="use_security_token" value="1"<?= $plugin['automatic_update_secret'] || !$plugin['automatic_update_url'] ? " checked" : "" ?>>
        </label>
    </fieldset>

    <? if ($plugin['automatic_update_url']) : ?>
    <fieldset>
        <legend><?= _("Daten f�r das bereitstellende System") ?></legend>
        <p class="info">
            <?= _("Tragen Sie bei gitlab, github.com oder dem Pluginmarktplatz untenstehende URL ein, die der Webhook aufrufen soll.") ?>
            <? if ($plugin['automatic_update_secret']) : ?>
            <?= _("Dieser Aufruf muss noch mit dem Sicherheitstoken abgesichert werden.") ?>
            <? endif ?>
        </p>
        <label>
            <?= _("URL") ?>
            <input type="text" readonly value="<?= htmlReady(URLHelper::getURL("api.php/plugin/".$plugin['class']."/trigger_automaticupdate", array('s' => md5($GLOBALS['STUDIP_INSTALLATION_ID'].$plugin['id'])), true)) ?>">
        </label>
        <? if ($plugin['automatic_update_secret']) : ?>
        <label>
            <?= _("Sicherheitstoken f�r das andere System (schreibgesch�tzt)") ?>
            <input type="text" readonly value="<?= htmlReady($plugin['automatic_update_secret']) ?>">
        </label>
        <? endif ?>
    </fieldset>
    <? endif ?>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>