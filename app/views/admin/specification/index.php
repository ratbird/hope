<?
# Lifter010: TODO
?>
<? if (isset($flash['error'])) : ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])) : ?>
    <?= MessageBox::success($flash['success'], $flash['success_detail']) ?>
<? elseif (isset($flash['delete'])) : ?>
    <?= createQuestion(sprintf(_('Wollen Sie die Regel "%s" wirklich löschen?'), $flash['delete']['name']), array('delete' => 1), array('back' => 1), $controller->url_for('admin/specification/delete/'.$flash['delete']['lock_id'])) ?>
<? endif; ?>

<table class="default">
    <caption>
        <?= _('Verwaltung von Zusatzangaben') ?>
    </caption>
    <colgroup>
        <col width="45%">
        <col width="45%">
        <col width="10%">
    </colgroup>
    <thead>
    <tr>
        <th><?= _('Name') ?></th>
        <th><?= _('Beschreibung') ?></th>
        <th><?= _('Aktionen') ?></th>
    </tr>
    </thead>
    <tbody>
   <? foreach ($allrules as $index=>$rule) : ?>
    <tr>
        <td>
            <?= htmlReady($rule['name']) ?>
        </td>
        <td>
            <?= htmlReady($rule['description']) ?>
        </td>
        <td class="actions">
            <a href="<?=$controller->url_for('admin/specification/edit/'.$rule['lock_id']) ?>">
                <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Regel bearbeiten'))) ?>
            </a>
            <a href="<?=$controller->url_for('admin/specification/delete/'.$rule['lock_id'])?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Regel löschen'))) ?>
            </a>
        </td>
    </tr>
    <? endforeach ?>
    </tbody>
</table>

<? //infobox
$infobox = array(
    'picture' => 'sidebar/admin-sidebar.png',
    'content' => array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag' => array(
                array(
                    "icon" => "icons/16/black/add.png",
                    "text" => '<a href="' . $controller->url_for('admin/specification/edit') . '">' . _('Neue Regel anlegen') . '</a>'
                )
            )
        ),
        array(
            'kategorie' => _("Hinweis"),
            'eintrag' => array(
                array(
                    "icon" => "icons/16/black/info.png",
                    "text" => _("Zusatzangaben werden zentral vom Systemadministrator definiert. "
                               ."Damit wird verhindert, dass Dozenten beliebige Informationen von Studierenden "
                               ."abfragen (Datenschutz) können und die Bedienungsfehler bei der Aktivierung "
                               ."der Abfrage von Zusatzangaben minimiert werden.")
                )
            )
        )
    )
);
?>
