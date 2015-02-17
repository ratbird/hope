<form action="<?= $controller->url_for('admin/semester/delete/bulk') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

<table class="default" id="semesters">
    <caption><?= _('Semester') ?></caption>
    <colgroup>
        <col width="20px">
        <col>
        <col width="10%">
        <col width="15%">
        <col width="15%">
        <col width="20%">
        <col width="48px">
    </colgroup>
    <thead>
        <tr>
            <th>
                <input type="checkbox"
                       data-proxyfor="#semesters tbody :checkbox"
                       data-activates="#semesters tfoot button">
            </th>
            <th><?= _('Name') ?></th>
            <th><?= _('K�rzel') ?></th>
            <th><?= _('Zeitraum') ?></th>
            <th><?= _('Veranstaltungszeitraum') ?></th>
            <th><?= _('Veranstaltungen') ?></th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<? if (empty($semesters)): ?>
        <tr>
            <td colspan="7" style="text-align: center;">
            <? if ($filter): ?>
                <?= _('In der gew�hlten Ansicht gibt es keine Eintr�ge.') ?>
            <? else: ?>
                <?= _('Es wurden noch keine Semester angelegt.') ?><br>
                <?= Studip\LinkButton::create(_('Neues Semester anlegen'),
                                              $controller->url_for('admin/semester/edit'),
                                              array('data-dialog' => 'size=auto')) ?>
            <? endif; ?>
            </td>
        </tr>
<? else: ?>
    <? foreach ($semesters as $semester): ?>
        <tr <? if ($semester->current) echo 'style="font-weight: bold;"'; ?>>
            <td>
                <input type="checkbox" name="ids[]" value="<?= $semester->id ?>"
                       <? if ($semester->absolute_seminars_count) echo 'disabled'; ?>>
            </td>
            <td title="<?= htmlReady($semester->description) ?>">
                <?= htmlReady($semester->name) ?>
            </td>
            <td>
                <?= htmlReady($semester->semester_token ?: '- ' . _('keins') . ' -') ?>
            </td>
            <td>
                <?= strftime('%x', $semester->beginn) ?>
                -
                <?= strftime('%x', $semester->ende) ?>
            </td>
            <td>
                <?= strftime('%x', $semester->vorles_beginn) ?>
                -
                <?= strftime('%x', $semester->vorles_ende) ?>
            </td>
            <td>
                <?= $semester->absolute_seminars_count ?>
                <?= sprintf(_('(+%u implizit)'),
                            $semester->continuous_seminars_count + $semester->duration_seminars_count) ?> 
            </td>
            <td class="actions">
                <a data-dialog="size=auto" href="<?= $controller->url_for('admin/semester/edit/' . $semester->id) ?>">
                    <?= Assets::img('icons/16/blue/edit.png', tooltip2(_('Semesterangaben bearbeiten'))) ?>
                </a>
            <? if ($semester->absolute_seminars_count): ?>
                <?= Assets::img('icons/16/grey/trash.png', tooltip2(_('Semester hat Veranstaltungen und kann daher nicht gel�scht werden.'))) ?>
            <? else: ?>
                <?= Assets::input('icons/16/blue/trash.png', tooltip2(_('Semester l�schen')) + array(
                        'formaction'   => $controller->url_for('admin/semester/delete/' . $semester->id),
                        'data-confirm' => _('Soll das Semester wirklich gel�scht werden?'),
                )) ?>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
<? endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7">
                <?= _('Markierte Eintr�ge') ?>
                <?= Studip\Button::create(_('L�schen'), 'delete', array(
                        'data-confirm' => _('Sollen die Semester wirklich gel�scht werden?')
                )) ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
