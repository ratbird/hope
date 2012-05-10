<?= Assets::img('infobox/board1.jpg', array('style' => 'float: right')) ?>

<blockquote>
    <?= _('Die folgenden Veranstaltungen können Sie betreten, ohne '
         .'sich im System registriert zu haben.') ?>
</blockquote>
<blockquote>
    <?= sprintf(_('In den %s blau markierten Veranstaltungen dürfen '
                 .'Sie nur lesen und Dokumente herunterladen.'),
                '<span class="gruppe6">&nbsp;&nbsp;</span>') ?>
    <br>
    <?= sprintf(_('In den %s orange markierten Veranstaltungen '
                 .'können Sie sich zusätzlich mit eigenen Beiträgen '
                 .'im Forum beteiligen.'),
                '<span class="gruppe2">&nbsp;&nbsp;</span>') ?>
</blockquote>
<blockquote>
    <?= _('In der rechten Spalte können Sie sehen, was in den '
          .'einzelnen Veranstaltungen an Inhalten vorhanden ist.') ?>
</blockquote>

<? if (empty($seminars)): ?>
    <?= Messagebox::info(_('Es gibt keine Veranstaltungen, die einen freien Zugriff erlauben!'))?>
<? endif; ?>

<? if (!empty($seminars)): ?>
<table class="default zebra-hover">
    <colgroup>
        <col width="1em">
        <col>
        <col>
        <col>
        <col>
    </colgroup>
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th><a href="<?= URLHelper::getLink('?sortby=Name') ?>"><?= _('Name') ?></a></th>
            <th><a href="<?= URLHelper::getLink('?sortby=status') ?>"><?=_ ('Veranstaltungstyp') ?></a></th>
            <th><a href="<?= URLHelper::getLink('?sortby=Institut') ?>"><?= _('Einrichtung') ?></a></th>
            <th><?= _('Inhalt') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($seminars as $id => $values): ?>
        <tr>
            <td class="<?= $values['Schreibzugriff'] ? 'gruppe6' : 'gruppe2' ?>">&nbsp;</td>
            <td>
                <a href="<?= URLHelper::getLink('seminar_main.php?auswahl=' . $id) ?>">
                    <?= htmlReady($values['name']) ?>
                </a>
            </td>
            <td><?= htmlReady($GLOBALS['SEM_TYPE'][$values['status']]['name']) ?></td>
            <td>
                <a href="<?= URLHelper::getLink('institut_main.php?auswahl=' . $values['id']) ?>">
                    <?= htmlReady($values['Institut']) ?>
                </a>
            </td>
            <td style="white-space: nowrap;">
                <? print_seminar_content($id, $values) ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>
<? endif; ?>
