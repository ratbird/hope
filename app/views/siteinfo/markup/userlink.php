<?
# Lifter010: TODO
?>
<? if ($error) : ?>
    <em><?= _("Nutzer nicht gefunden.") ?></em>
<? else : ?>
    <a href="<?= URLHelper::getLink('dispatch.php/about',
                                     array('username' => $username))
              ?>"><?= htmlReady($fullname)?></a>
<? endif ?>
