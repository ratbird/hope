<input type="hidden" name="received" id="received" value="<?= (int) $received ?>">
<input type="hidden" name="since" id="since" value="<?= time() ?>">
<input type="hidden" name="folder_id" id="tag" value="<?= htmlReady(ucfirst(Request::get("tag"))) ?>">
<input type="hidden" name="search" id="search" value="<?= htmlReady(Request::get("search")) ?>">
<input type="hidden" name="search_autor" id="search_autor" value="<?= htmlReady(Request::get("search_autor")) ?>">
<input type="hidden" name="search_subject" id="search_subject" value="<?= htmlReady(Request::get("search_subject")) ?>">
<input type="hidden" name="search_content" id="search_content" value="<?= htmlReady(Request::get("search_content")) ?>">

<? if (Request::get("tag")) : ?>
<h4>
<?= _("Zum Tag: ").htmlReady(ucfirst(Request::get("tag"))) ?>
</h4>
<? endif ?>

<table class="default" id="messages">
    <thead>
        <tr>
            <th></th>
            <th><?= _("Betreff") ?></th>
            <th><?= _("Autor") ?></th>
            <th><?= _("Zeit") ?></th>
            <th><?= _("Tags") ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody aria-relevant="additions" aria-live="polite">
        <? if (count($messages) > 0) : ?>
            <? if ($more || (Request::int("offset") > 0)) : ?>
            <noscript>
            <tr>
                <td colspan="5">
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
            <?= $this->render_partial("messages/_message_row.php", compact("message")) ?>
            <? endforeach ?>
            <? if ($more || (Request::int("offset") > 0)) : ?>
            <noscript>
            <tr>
                <td colspan="5">
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
            <td colspan="6" style="text-align: center"><?= _("Keine Nachrichten") ?></td>
        </tr>
        <? endif ?>
        <tr id="reloader" class="more">
            <td colspan="6"><?= Assets::img("ajax_indicator_small.gif") ?></td>
        </tr>
    </tbody>
</table>

<div style="display: none; background-color: rgba(255,255,255, 0.3); padding: 3px; border-radius: 5px; border: thin solid black;" id="move_handle">
    <?= Assets::img("icons/20/blue/mail", array('class' => "text-bottom")) ?>
    <span class="title"></span>
</div>

<script>
STUDIP.jsupdate_enable = true;
jQuery(function () {
    jQuery("#nav__messaging_messages_write").attr("data-dialog", "buttons=false");
});
</script>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/mail-sidebar.png"));

if (count($tags)) {
    $folderwidget = new LinksWidget();
    $folderwidget->setTitle(_("Verwendete Tags"));
    foreach ($tags as $tag) {
        $folderwidget->addLink(ucfirst($tag), URLHelper::getURL("?", array('tag' => $tag)), null, array('class' => "tag"));
    }
    $sidebar->addWidget($folderwidget, 'folder');
}

$actions = new ActionsWidget();
$actions->addLink(
    _("Neue Nachricht schreiben"),
    URLHelper::getURL("dispatch.php/messages/write"),
    null,
    array('data-dialog' => "buttons=false")
);
$sidebar->addWidget($actions);

$search = new SearchWidget();
$search->addElement(new WidgetElement(
    $this->render_partial('messages/_search'),
    'search'
));
$sidebar->addWidget($search);