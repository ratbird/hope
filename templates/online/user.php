<?
# Lifter010: TODO
?>
<tr class="<?= $background ? $background : 'table_row_even' ?>">

    <td width="1%" <?= $gruppe ? "class=\"$gruppe\"" : '' ?>>
        <? if (!$is_buddy) : ?>
            <a href="<?= URLHelper::getLink('online.php', array('cmd' => 'add_user',
                                                                'add_uname' => $tmp_online_uname)) ?>">
                <?= Assets::img('icons/16/yellow/arr_2left.png', array('title' => _("zu den Buddies hinzufügen"),
                                                       'style' => 'padding-right: 0.33em;', 'class' => 'middle')) ?>
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

    <td width="66%">
        <a href="<?= URLHelper::getLink('about.php', array('username' => $tmp_online_uname)) ?>">
            <?= htmlReady($fullname) ?>
        </a>
            <? foreach (StudipKing::is_king($tmp_user_id, TRUE) as $type => $text) : ?>
                <?= Assets::img("icons/16/yellow/crown.png", array('title' => $text, 'title' => $text, 'class' => 'text-bottom')) ?>
            <? endforeach ?>
    </td>

    <td width="20%">
            <?= date("i:s", $zeit) ?>
    </td>

    <td width="3%" align="center">
        <? if (get_config('CHAT_ENABLE')) : ?>
            <?= chat_get_online_icon($tmp_user_id, $tmp_online_uname) ?>
        <? else : ?>
            &nbsp;
        <? endif ?>
    </td>

    <td width="3%" align="center">
        <a href="<?= URLHelper::getLink('sms_send.php', array('sms_source_page' => 'online.php',
                                                              'rec_uname' => $tmp_online_uname)) ?>">
            <?= Assets::img('icons/16/blue/mail.png', array('title' => _("Nachricht an Benutzer verschicken"), 'class' => 'text-bottom')) ?>
        </a>
    </td>

    <td width="3%" align="center">
        <? if ($is_buddy) : ?>
            <a href="<?= URLHelper::getLink("online.php",
                                            array("cmd" => "delete_user",
                                                  "delete_uname" => $tmp_online_uname)) ?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _("aus der Buddy-Liste entfernen"), 'class' => 'text-bottom')) ?>
            </a>
        <? else : ?>
            &nbsp;
        <? endif ?>
    </td>
</tr>
