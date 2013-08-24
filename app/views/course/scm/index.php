<? use Studip\Button, Studip\LinkButton; ?>

<? if ($verification): ?>
    <?= $verification ?>
<? endif; ?>

<table class="default scm">
    <colgroup>
        <col>
        <col width="25%">
    </colgroup>
    <thead class="content_title">
        <tr>
            <td>
                <?= Assets::img('icons/16/grey/infopage.png', array('class' => 'text-top')) ?>
                <strong>
                    <?= htmlReady($scm->tab_name) ?>
                </strong>
            </td>
            <td>
                <?= sprintf(_('Zuletzt ge�ndert von %s am %s'),
                            sprintf('<a href="%s">%s</a>',
                                    URLHelper::getLink('dispatch.php/profile?username=' . $scm->user->username),
                                    htmlReady($scm->user->getFullName('full'))),
                            strftime('%x, %X', $scm->chdate)) ?>
            </td>
        </tr>
    </thead>
    <tbody class="content_body">
        <tr>
            <td colspan="2">
            <? if (!empty($scm->content)): ?>
                <?= formatReady($scm->content) ?>
            <? else: ?>
                <?= MessageBox::info(_('In diesem Bereich wurden noch keine Inhalte erstellt.')) ?>
            <? endif; ?>
            </td>
        </tr>
    </tbody>
<? if ($priviledged): ?>
    <tfoot class="table_footer">
        <tr>
            <td colspan="2">
                <?= LinkButton::create(_('Bearbeiten'), $controller->url_for('course/scm/edit/' . $scm->id)) ?>
        <? if (count($scms) > 1): ?>
            <? if ($scm->position == 0): ?>
                <?= Button::create(_('Nach vorne'), array('disabled' => 'disabled')) ?>
            <? else: ?>
                <?= LinkButton::create(_('Nach vorne'),
                                       $controller->url_for('course/scm/move/' . $scm->id),
                                       array('title' => _('Diese Seite an die erste Position setzen'))) ?>
            <? endif; ?>
                <?= LinkButton::create(_('L�schen'),
                                       $controller->url_for('course/scm/' . $scm->id . '?verify=delete'),
                                       array('title' => _('Diese Seite l�schen'))) ?>
        <? endif; ?>
            </td>
        </tr>
    </tfoot>
<? endif; ?>
</table>
