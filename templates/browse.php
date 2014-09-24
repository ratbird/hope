<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<!-- SEARCHBOX -->
<form action="<?= URLHelper::getLink() ?>" method="post">
<?= CSRFProtection::tokenTag() ?>

<? if($sms_msg): ?>
    <? parse_msg($sms_msg); ?>
<? endif; ?>

<!-- form zur wahl der institute -->
<table class="default nohover">
    <caption>
        <?= _('Suche nach Personen') ?>
    </caption>
    <colgroup>
        <col width="25%">
        <col width="75%">
    </colgroup>
    <tbody>
    <? if (count($institutes)): ?>
        <tr>
            <td>
                <strong><?= _('in Einrichtungen:') ?></strong>
            </td>
            <td>
                <select name="inst_id" style="min-width: 400px;">
                    <option value="0">&nbsp;</option>
                <? foreach ($institutes as $institute): ?>
                    <option value="<?= $institute['id'] ?>" <?= $institute['id'] == $inst_id ? 'selected="selected"' : '' ?>>
                        <?= htmlReady($institute['name']) ?>
                    </option>
                <? endforeach;?>
                </select>
            </td>
        </tr>
    <? endif ?>
        <!-- form zur wahl der seminare -->
    <? if (count($courses)): ?>
        <tr>
            <td>
                <strong><?= _('in Veranstaltungen:') ?></strong>
            </td>
            <td>
                <select name="sem_id" style="min-width: 400px;">
                    <option value="0">&nbsp;</option>
                <? foreach ($courses as $course): ?>
                    <option value="<?= $course['id'] ?>" <?= $course['id'] == $sem_id ? 'selected="selected"' : '' ?>>
                        <?= htmlReady($course['name']) ?>
                    </option>
                <? endforeach;?>
                </select>
            </td>
        </tr>
    <? endif ?>
        <!-- form zur freien Suche -->
        <tr>
            <td>
                <strong><?= _('Name:') ?></strong>
            </td>
            <td>
                <?= QuickSearch::get('name', $search_object)
                        ->setInputStyle('width: 400px')
                        ->setAttributes(array('autofocus' => ''))
                        ->defaultValue('', $name)
                        ->fireJSFunctionOnSelect('STUDIP.Browse.selectUser')
                        ->noSelectbox()
                        ->render() ?>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">
                <?= Button::createAccept(_('Suchen'), 'send')?>
                <?= Button::create(_('Zurücksetzen'), 'reset')?>
            </td>
        </tr>
    </tfoot>
</table>
</form>

<!-- RESULTS -->
<? if (isset($users)):?>
<table class="default nohover">
    <caption>
        <?= _('Ergebnisse') ?>
    </caption>
    <thead>
        <tr>
            <th>
                <a href="<?= URLHelper::getLink('', compact('name', 'sem_id', 'inst_id')) ?>">
                    <?= _('Name') ?>
                </a>
            </th>
            <th>
            <? if ($inst_id): ?>
                <?= _('Funktion an der Einrichtung') ?>
            <? elseif ($sem_id): ?>
                <a href="<?= URLHelper::getLink('', compact('name', 'sem_id') + array('sortby' => 'status')) ?>">
                    <?= _('Status in der Veranstaltung') ?>
                </a>
            <? else: ?>
                <a href="<?= URLHelper::getLink('', compact('name') + array('sortby' => 'perms')) ?>">
                    <?= _('globaler Status') ?>
                </a>
            <? endif; ?>
            </th>
            <th align="right">
                <?= _('Nachricht verschicken') ?>
            </th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($users as $user): ?>
        <tr>
            <td>
                <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $user['username'])) ?>">
                    <?= Avatar::getAvatar($user['user_id'], $user['username'])->getImageTag(Avatar::SMALL) ?>
                    <?= htmlReady($user['fullname']) ?>
                </a>
            </td>
            <td>
                <?= htmlReady($user['status']) ?>
            </td>
            <td align="right">
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array('rec_uname' => $user['username'])) ?>" data-dialog>
                    <?= Assets::img('icons/16/blue/mail.png', array('class' => 'text-top', 'title' => _('Nachricht an Benutzer verschicken'))) ?>
                </a>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
<? elseif ($name != ''): ?>
    <?= MessageBox::info(_('Es wurde niemand gefunden.')) ?>
<? elseif (isset($name)): ?>
    <?= MessageBox::error(_('Bitte einen Vor- oder Nachnamen eingeben.')) ?>
<? endif; ?>

<?
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/person-sidebar.png');
if (get_config('SCORE_ENABLE')) {
    $widget = new NavigationWidget();
    $widget->addLink(_('Zur Stud.IP-Rangliste'), URLHelper::getLink('dispatch.php/score'));
    $sidebar->addWidget($widget);
}