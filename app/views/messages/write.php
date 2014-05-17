<form action="<?= URLHelper::getLink("dispatch.php/messages/send") ?>" method="post" style="max-width: 600px; margin-left: auto; margin-right: auto;">
    <? $message_id = Request::option("message_id") ?: md5(uniqid("neWMesSagE")) ?>
    <input type="hidden" name="message_id" id="message_id" value="<?= htmlReady($message_id) ?>">
    <div>
        <label for="user_id_1"><h4><?= _("An") ?></h4></label>
        <ul style="list-style-type: none; margin: 0px; padding: 0px;" id="adressees">
            <li id="template_adressee" style="display: none; padding: 0px;" class="adressee">
                <input type="hidden" name="message_to[]" value="">
                <span class="visual"></span>
                <a class="remove_adressee"><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?></a>
            </li>
            <? foreach ($to as $user_id) : ?>
            <li style="padding: 0px;" class="adressee">
                <input type="hidden" name="message_to[]" value="<?= htmlReady($user_id) ?>">
                <span class="visual">
                    <?= Avatar::getAvatar($user_id)->getImageTag(Avatar::SMALL) ?>
                    <?= htmlReady(get_fullname($user_id, 'full_rev')) ?>
                </span>
                <a class="remove_adressee"><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?></a>
            </li>
            <? endforeach ?>
        </ul>
        <?= QuickSearch::get("user_id", new StandardSearch("user_id"))
            ->fireJSFunctionOnSelect("STUDIP.Messages.add_adressee")
            ->render() ?>

        <?
        $search_obj = new SQLSearch("SELECT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms "
            . "FROM auth_user_md5 "
            . "LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) "
            . "WHERE "
            . "username LIKE :input OR Vorname LIKE :input "
            . "OR CONCAT(Vorname,' ',Nachname) LIKE :input "
            . "OR CONCAT(Nachname,' ',Vorname) LIKE :input "
            . "OR Nachname LIKE :input OR {$GLOBALS['_fullname_sql']['full_rev']} LIKE :input "
            . " ORDER BY fullname ASC",
            _("Nutzer suchen"), "user_id");
        $mps = MultiPersonSearch::get("add_adressees")
            ->setLinkText(_('Mehrere Adressaten hinzufügen'))
            //->setDefaultSelectedUser($defaultSelectedUser)
            ->setTitle(_('Mehrere Adressaten hinzufügen'))
            ->setExecuteURL(URLHelper::getURL("dispatch.php/messages/write"))
            ->setJSFunctionOnSubmit("STUDIP.Messages.add_adressees")
            ->setSearchObject($search_obj);
        foreach (Statusgruppen::findContactGroups() as $group) {
            $mps->addQuickfilter(
                $group['name'],
                $group->members->map(function($member) { return $member['user_id']; })
            );
        }
        echo $mps->render();
        ?>

    </div>
    <div>
        <label>
            <h4><?= _("Betreff") ?></h4>
            <input type="text" name="message_subject" style="width: 100%" required value="<?= htmlReady($default_subject) ?>">
        </label>
    </div>
    <div>
        <label>
            <h4><?= _("Nachricht") ?></h4>
            <textarea style="width: 100%; height: 200px;" name="message_body" class="add_toolbar"><?= htmlReady($default_body) ?></textarea>
        </label>
    </div>
    <div>
        <ul style="list-style-type: none; text-align: center;">
            <li style="display: inline-block; min-width: 70px;">
                <a href="" onClick="jQuery('#attachments').toggle('fade');return false;">
                    <?= Assets::img("icons/40/blue/staple") ?>
                    <br>
                    <strong><?= _("Anhänge") ?></strong>
                </a>
            </li>
            <li style="display: inline-block; min-width: 70px;">
                <a href="" onClick="jQuery('#tags').toggle('fade');return false;">
                    <?= Assets::img("icons/40/blue/star") ?>
                    <br>
                    <strong><?= _("Tags") ?></strong>
                </a>
            </li>
            <li style="display: inline-block; min-width: 70px;">
                <a href="" onClick="jQuery('#settings').toggle('fade');return false;">
                    <?= Assets::img("icons/40/blue/admin") ?>
                    <br>
                    <strong><?= _("Einstellungen") ?></strong>
                </a>
            </li>
        </ul>
    </div>

    <div id="attachments" style="display: none;">
        <h4><?= _("Anhänge") ?></h4>
        <div>
            <ul class="files">
                <li style="display: none;" class="file">
                    <span class="icon"></span>
                    <span class="name"></span>
                    <span class="size"></span>
                </li>
            </ul>
            <div id="statusbar_container">
                <div class="statusbar" style="display: none;">
                    <div class="progress"></div>
                    <div class="progresstext">0%</div>
                </div>
            </div>
            <label>
                <input type="file" id="fileupload" multiple onChange="STUDIP.Messages.upload_from_input(this);" style="display: none;">
                <a style="cursor: pointer;">
                    <?= Assets::img("icons/20/blue/upload", array('title' => _("Datei hochladen"), 'class' => "text-bottom")) ?>
                    <?= _("Datei hochladen") ?>
                </a>
            </label>

            <div id="upload_finished" style="display: none"><?= _("wird verarbeitet") ?></div>
            <div id="upload_received_data" style="display: none"><?= _("gespeichert") ?></div>
        </div>
    </div>
    <div id="tags" style="<?= Request::get("default_tags") ? "" : 'display: none; ' ?>">
        <label>
            <h4><?= _("Tags") ?></h4>
            <input type="text" name="message_tags" style="width: 100%" placeholder="<?= _("z.B. klausur termin statistik etc.") ?>" value="<?= htmlReady(Request::get("default_tags")) ?>">
        </label>
    </div>
    <div id="settings" style="display: none;">
        <h4><?= _("Einstellungen") ?></h4>
        <table class="" style="width: 100%">
            <tbody>
                <tr>
                    <td>
                        <label for="message_mail"><strong><?= _("Immer per Mail weiterleiten") ?></strong></label>
                    </td>
                    <td>
                        <input type="checkbox" name="message_mail" id="message_mail" value="1">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="message_signature"><strong><?= _("Signatur anhängen") ?></strong></label>
                    </td>
                    <td>
                        <input type="checkbox" name="message_signature" id="message_signature" value="1">
                        <br>
                        <textarea name="message_signatur_content" id="message_signatur_content" style="width: 100%;"></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="text-align: center;">
        <?= \Studip\Button::create(_("abschicken")) ?>
    </div>

</form>


<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/mail-sidebar.png"));

if (false && count($tags)) {
    $folderwidget = new LinksWidget();
    $folderwidget->setTitle(_("Verwendete Tags"));
    foreach ($tags as $tag) {
        $folderwidget->addLink(ucfirst($tag), URLHelper::getURL("?", array('tag' => $tag)), null, array('class' => "tag"));
    }
    $sidebar->addWidget($folderwidget, 'folder');
}