<?
# Lifter010: TODO
?>
<!-- Startseite (nicht eingeloggt) -->
<? if ($logout) : ?>
    <?= MessageBox::success(_("Sie sind nun aus dem System abgemeldet."), array($GLOBALS['UNI_LOGOUT_ADD'])) ?>
<? endif; ?>

<div class="index_main">
    <nav>
        <h1><?= htmlReady($GLOBALS['UNI_NAME_CLEAN']) ?></h1>
        <? foreach (Navigation::getItem('/login') as $key => $nav) : ?>
            <? if ($nav->isVisible()) : ?>
                <? list($name, $title) = explode(' - ', $nav->getTitle()) ?>
                <div class="login_link">
                    <? if (is_internal_url($url = $nav->getURL())) : ?>
                        <a href="<?= URLHelper::getLink($url) ?>">
                    <? else : ?>
                        <a href="<?= htmlReady($url) ?>" target="_blank">
                    <? endif ?>
                    <? SkipLinks::addLink($name, $url) ?>
                        <?= htmlReady($name) ?>
                            <p>
                                <?= htmlReady($title ? $title : $nav->getDescription()) ?>
                            </p>
                        </a>
                </div>
            <? endif ?>
        <? endforeach ?>


    </nav>
    <footer>
        <? if ($GLOBALS['UNI_LOGIN_ADD']) : ?>
            <div class="uni_login_add">
                <?= $GLOBALS['UNI_LOGIN_ADD'] ?>
            </div>
        <? endif; ?>

        <table class="login_info">
            <tr>
                <td>
                    <?= _("Aktive Veranstaltungen") ?>
                </td>
                <td>
                    <?= $num_active_courses ?>
                </td>
            </tr>

            <tr>
                <td>
                    <?= _("Registrierte NutzerInnen") ?>
                </td>
                <td>
                    <?= $num_registered_users ?>
                </td>
            </tr>

            <tr>
                <td>
                    <?= _("Davon online") ?>
                </td>
                <td>
                    <?= $num_online_users ?>
                </td>
            </tr>

            <tr>
                <td>
                    <? foreach ($GLOBALS['INSTALLED_LANGUAGES'] as $temp_language_key => $temp_language): ?>
                        <a href="index.php?set_language=<?= $temp_language_key ?>">
                            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/languages/<?= $temp_language['picture'] ?>" border="0" <?= tooltip($temp_language['name']) ?>>
                        </a>
                    <? endforeach; ?>
                </td>
                <td>
                    <a href="dispatch.php/siteinfo/show">
                        <?= _("mehr") ?>...
                    </a>
                </td>
            </tr>
        </table>

        <a href="http://www.studip.de">
            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/logos/logoklein@2x.png" border="0" width="215" height="83"  <?= tooltip(_("Zur Portalseite")) ?> >
        </a>
    </footer>
</div>
