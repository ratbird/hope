<?
# Lifter010: TODO
?>
<table border="0" cellpadding="2" cellspacing="0" width="98%" align="center" class="blank zebra-hover">
    <thead>
        <tr>
            <th align="left"><?= _("Personen, deren Standardvertretung ich bin") ?></th>
        </tr>
    </thead>
    <tbody>
    <?
    $deputies_edit_about_enabled = get_config('DEPUTIES_EDIT_ABOUT_ENABLE');
    foreach ($my_bosses as $boss) { ?>
        <tr>
            <td>
                <?= Avatar::getAvatar($boss['user_id'])->getImageTag(Avatar::SMALL, array('title' => htmlReady($boss['fullname']))) ?>
                <?php
                $name_text = '';
                if ($boss['edit_about'] && $deputies_edit_about_enabled) {
                    $name_text .= '<a href="'.URLHelper::getLink('about.php', array('username' => $boss['username'])).'">';
                }
                $name_text .= $boss['fullname'];
                if ($boss['edit_about'] && $deputies_edit_about_enabled) {
                    $name_text .= '</a>';
                }
                echo $name_text;
                ?>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<br/>