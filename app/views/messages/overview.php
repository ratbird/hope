<input type="hidden" name="received" id="received" value="<?= (int) $received ?>">
<input type="hidden" name="since" id="since" value="<?= time() ?>">
<input type="hidden" name="folder_id" id="tag" value="<?= htmlReady(ucfirst(Request::get("tag"))) ?>">
<input type="hidden" name="search" id="search" value="<?= htmlReady(Request::get("search")) ?>">
<input type="hidden" name="search_autor" id="search_autor" value="<?= htmlReady(Request::get("search_autor")) ?>">
<input type="hidden" name="search_subject" id="search_subject" value="<?= htmlReady(Request::get("search_subject")) ?>">
<input type="hidden" name="search_content" id="search_content" value="<?= htmlReady(Request::get("search_content")) ?>">

<? if (Request::get("tag")) : ?>
    <form action="<?= $controller->url_for('messages/delete_tag', array('tag' => Request::get("tag"))) ?>" method="post" id="delete_tags_form">
        <?= CSRFProtection::tokenTag() ?>
    </form>
<? endif ?>

<? if ($settings['display_bulk_actions']) : ?>
<form action="?" method="post" id="bulk">
    <?= CSRFProtection::tokenTag() ?>
<? endif ?>


<table class="default" id="messages">
    <caption>
        <?= $received ? _("Eingang") : _("Gesendet") ?>
        <? if (Request::get("tag")) : ?>
            <?= ", "._("Schlagwort: ").htmlReady(ucfirst(Request::get("tag"))) ?>
            <button onClick="if (window.confirm('<?= _("Schlagwort wirklich löschen?") ?>') { jQuery('#delete_tags_form').submit(); }" style="background: none; border: none; cursor: pointer;" title="<?= _("Schlagwort von allen Nachrichten entfernen.") ?>">
                <?= Assets::img("icons/20/blue/trash") ?>
            </button>
        <? endif ?>
    </caption>
    <thead>
        <tr>
            <? if ($settings['display_bulk_actions']) : ?>
            <th><input type="checkbox" data-proxyfor="#bulk :checkbox"></th>
            <? endif ?>
            <th></th>
            <th></th>
            <th><?= _("Betreff") ?></th>
            <th><?= $received ? _("Gesendet") : _("Empfänger") ?></th>
            <th><?= _("Zeit") ?></th>
            <th><?= _("Schlagworte") ?></th>
            <th></th>
        </tr>
    </thead>

    <tbody aria-relevant="additions" aria-live="polite">
        <? if (count($messages) > 0) : ?>
            <? if ($more || (Request::int("offset") > 0)) : ?>
            <noscript>
            <tr>
                <td colspan="8">
                    <? if (Request::int("offset") > 0) : ?>
                    <a title="<?= _("zurück") ?>" href="<?= URLHelper::getLink("?", array('offset' => Request::int("offset") - $messageBufferCount > 0 ? Request::int("offset") - $messageBufferCount : null)) ?>"><?= Assets::img("icons/16/blue/arr_1left", array("class" => "text-bottom")) ?></a>
                    <? endif ?>
                    <? if ($more) : ?>
                    <div style="float:right">
                        <a title="<?= _("weiter") ?>" href="<?= URLHelper::getLink("?", array('offset' => Request::int("offset") + $messageBufferCount)) ?>"><?= Assets::img("icons/16/blue/arr_1right", array("class" => "text-bottom")) ?></a>
                    </div>
                    <? endif ?>
                </td>
            </tr>
            </noscript>
            <? endif ?>
            <? foreach ($messages as $message) : ?>
            <?= $this->render_partial("messages/_message_row.php", compact("message", "received". "settings")) ?>
            <? endforeach ?>
            <? if ($more || (Request::int("offset") > 0)) : ?>
            <noscript>
            <tr>
                <td colspan="8">
                    <? if (Request::int("offset") > 0) : ?>
                        <a title="<?= _("zurück") ?>" href="<?= URLHelper::getLink("?", array('offset' => Request::int("offset") - $messageBufferCount > 0 ? Request::int("offset") - $messageBufferCount : null)) ?>"><?= Assets::img("icons/16/blue/arr_1left", array("class" => "text-bottom")) ?></a>
                    <? endif ?>
                    <? if ($more) : ?>
                        <div style="float:right">
                            <a title="<?= _("weiter") ?>" href="<?= URLHelper::getLink("?", array('offset' => Request::int("offset") + $messageBufferCount)) ?>"><?= Assets::img("icons/16/blue/arr_1right", array("class" => "text-bottom")) ?></a>
                        </div>
                    <? endif ?>
                </td>
            </tr>
            </noscript>
            <? endif ?>
        <? else : ?>
        <tr>
            <td colspan="8" style="text-align: center"><?= _("Keine Nachrichten") ?></td>
        </tr>
        <? endif ?>
        <tr id="reloader" class="more">
            <td colspan="8"><?= Assets::img("ajax_indicator_small.gif") ?></td>
        </tr>
    </tbody>
</table>

<? if ($settings['display_bulk_actions']) : ?>
    </form>
<? endif ?>


<div style="display: none; background-color: rgba(255,255,255, 0.3); padding: 3px; border-radius: 5px; border: thin solid black;" id="move_handle">
    <?= Assets::img("icons/20/blue/mail", array('class' => "text-bottom")) ?>
    <span class="title"></span>
</div>

<? if ($message_id): ?>
<script>
jQuery(function ($) {
    STUDIP.Dialog.fromURL('<?= $controller->url_for('messages/read/' . $message_id) ?>');
});
</script>
<? endif; ?>

<?php

$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/mail-sidebar.png');

$actions = new ActionsWidget();
$actions->addLink(
    _("Neue Nachricht schreiben"),
    $controller->url_for('messages/write'),
    'icons/16/blue/add/mail.png',
    array('data-dialog' => 'width=650;height=600')
);
if (Navigation::getItem('/messaging/messages/inbox')->isActive() && $messages) {
    $actions->addLink(
        _('Alle als gelesen markieren'),
        $controller->url_for('messages/overview', array('read_all' => 1)),
        'icons/16/blue/accept.png'
    );
}
if ($settings['display_bulk_actions']) {
    $actions->addLink(
        _('Ausgewählte Nachrichten löschen'),
        "#",
        'icons/16/blue/trash.png',
        array(
            'onclick' => "jQuery('#bulk').submit(); return false;"
        )
    );
}
$sidebar->addWidget($actions);

$search = new SearchWidget(URLHelper::getLink('?'));
$search->addNeedle(_('Nachrichten durchsuchen'), 'search', true);
$search->addFilter(_('Betreff'), 'search_subject');
$search->addFilter(_('Inhalt'), 'search_content');
$search->addFilter(_('AutorIn'), 'search_autor');
$sidebar->addWidget($search);

$folderwidget = new ViewsWidget();
$folderwidget->forceRendering();
$folderwidget->title = _('Schlagworte');
$folderwidget->id    = 'messages-tags';
$folderwidget
    ->addLink(_("Alle Nachrichten"), URLHelper::getURL("?"), null, array('class' => "tag"))
    ->setActive(!Request::submitted("tag"));
if (empty($tags)) {
    $folderwidget->style = 'display:none';
} else {
    foreach ($tags as $tag) {
        $folderwidget
            ->addLink(ucfirst($tag), URLHelper::getURL("?", array('tag' => $tag)), null, array('class' => "tag"))
            ->setActive(Request::get("tag") === $tag);
    }
}

$sidebar->addWidget($folderwidget);
