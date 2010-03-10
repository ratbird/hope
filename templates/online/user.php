<tr class="<?= $background ? $background : 'steel1' ?>">

    <td width="1%" <?= $gruppe ? "class=\"$gruppe\"" : '' ?>>
        <? if (!$is_buddy) : ?>
            <a href="<?= URLHelper::getLink('online.php', array('cmd' => 'add_user',
                                                                'add_uname' => $tmp_online_uname)) ?>">
                <?= Assets::img('add_buddy.gif', array('alt' => _("zu den Buddies hinzufügen"),
                                                       'style' => 'padding-right: 0.33em;')) ?>
            </a>
        <? else : ?>
            &nbsp;
        <? endif ?>
    </td>

    <td width="4%">
        <a href="<?= URLHelper::getLink('about.php', array('username' => $tmp_online_uname)) ?>">
            <?= Avatar::getAvatar($tmp_user_id)->getImageTag(Avatar::SMALL) ?>
        </a>
    </td>

    <td width="60%">
        <a href="<?= URLHelper::getLink('about.php', array('username' => $tmp_online_uname)) ?>">
            <font size="-1">
                <?= htmlReady($fullname) ?>
            </font>
            <? foreach (StudipKing::is_king($tmp_user_id, TRUE) as $type => $text) : ?>
                <?= Assets::img("crown.gif", array('alt' => $text, 'title' => $text)) ?>
            <? endforeach ?>
        </a>
    </td>

    <td width="20%">
        <font size="-1">
            <?= date("i:s", $zeit) ?>
        </font>
    </td>

    <td width="5%" align="center">

        <? if ($GLOBALS['CHAT_ENABLE']) : ?>
            <?= chat_get_online_icon($tmp_user_id, $tmp_online_uname) ?>
        <? else : ?>
            &nbsp;
        <? endif ?>

    </td>

    <td width="5%" align="center">
        <a href="<?= URLHelper::getLink('sms_send.php', array('sms_source_page' => 'online.php',
                                                              'rec_uname' => $tmp_online_uname)) ?>">
            <?= Assets::img('nachricht1.gif', array('alt' => _("Nachricht an User verschicken"))) ?>
        </a>
    </td>

    <td width="5%" align="center">
        <? if ($is_buddy) : ?>
            <a href="<?= URLHelper::getLink("online.php",
                                            array("cmd" => "delete_user",
                                                  "delete_uname" => $tmp_online_uname)) ?>">
                <?= Assets::img('trash.gif', array('alt' => _("aus der Buddy-Liste entfernen"))) ?>
            </a>
        <? else : ?>
            &nbsp;
        <? endif ?>
    </td>
</tr>
