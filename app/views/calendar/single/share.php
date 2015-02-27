<? use Studip\Button, Studip\LinkButton; ?>
<? if (Request::isXhr()) : ?>
    <? foreach (PageLayout::getMessages() as $messagebox) : ?>
        <?= $messagebox ?>
    <? endforeach ?>
<? else : ?>
    <? SkipLinks::addIndex(_('Kalender teilen'), 'main_content', 100); ?>
<? endif; ?>
<form data-dialog="size=auto" action="<?= $controller->url_for('calendar/single/share/' . $calendar->getRangeId()) ?>" method="post">
    <input type="hidden" name="studip_ticket" value="<?= get_ticket() ?>">
    <table class="default">
        <caption>
            <?= _('Kalender mit anderen Teilen und in andere Kalender einbetten') ?>
        </caption>
        <? if (!$short_id) : ?>
        <tr>
            <td>
                <?= _('Sie k�nnen sich eine Adresse generieren lassen, mit der Sie Termine aus Ihrem Stud.IP-Terminkalender in externen Terminkalendern einbinden k�nnen.') ?>
                <div style="text-align: center;">
                    <?= Button::create(_("Adresse generieren!"), 'new_id') ?>
                </div>
            </td>
        </tr>
        <? else : ?>
        <tr>
            <td>
                <?= _('Die folgende Adresse k�nnen Sie in externe Terminkalenderanwendungen eintragen, um Ihre Termine dort anzuzeigen:') ?>
                <? $url = URLHelper::getLink($GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/ical/index/' . $short_id, null, true) ?>
                <div style="font-weight: bold;">
                    <a href="<?= $url ?>" target="_blank"><?= htmlReady($url) ?></a>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <?= Button::create(_('Neue Adresse generieren.'), 'new_id') ?>
                <?= _('(Achtung: Die alte Adresse wird damit ung�ltig!)') ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= Button::create(_('Adresse l�schen.'), 'delete_id') ?>
                <?= _('(Ein Zugriff auf Ihre Termine �ber diese Adresse ist dann nicht mehr m�glich!)') ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= CSRFProtection::tokenTag() ?>
                <?=  _('Verschicken Sie die Export-Andresse als Email:') ?>
                <input type="email" name="email" value="<?= htmlReady($GLOBALS['user']->email) ?>" required="required">
                <?= Button::create(_('Abschicken'), 'submit_email', array('title' => _('Abschicken'))) ?>
            </td>
        </tr>
        <? endif; ?>
    </table>
</form>
