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
                <?= Icon::create('edit', 'clickable', ['title' => _('Regel bearbeiten')])->asImg() ?>
            </a>
            <a href="<?=$controller->url_for('admin/specification/delete/'.$rule['lock_id'])?>">
                <?= Icon::create('trash', 'clickable', ['title' => _('Regel löschen')])->asImg() ?>
            </a>
        </td>
    </tr>
    <? endforeach ?>
    </tbody>
</table>

<?

$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/admin-sidebar.png');
$sidebar->setTitle(_('Zusatzangaben'));
$actions = new ActionsWidget();
$actions->addLink(_('Neue Regel anlegen'), $controller->url_for('admin/specification/edit'), Icon::create('add', 'clickable'));
$sidebar->addWidget($actions);

?>
