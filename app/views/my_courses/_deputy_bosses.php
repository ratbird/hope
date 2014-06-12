<?
# Lifter010: TODO
?>
<? SkipLinks::addIndex(_("Personen, deren Standardvertretung ich bin"), 'my_deputy_bosses') ?>
<table class="default" id="my_deputy_bosses">
    <caption>
        <?= _("Personen, deren Standardvertretung ich bin") ?>
    </caption>
    <colgroup>
        <col width="30px">
        <col>
    </colgroup>
    <thead>
    <tr>
        <th></th>
        <th><?= _("Name") ?></th>
    </tr>
    </thead>
    <tbody>
    <? foreach ($my_bosses as $boss) : ?>
        <tr>
            <td>
                <?= Avatar::getAvatar($boss['user_id'])->getImageTag(Avatar::SMALL, array('title' => htmlReady($boss['fullname']))) ?>
            </td>
            <td>
                <?php
                $name_text = '';
                if ($boss['edit_about'] && $deputies_edit_about_enabled) {
                    $name_text .= '<a href="' . URLHelper::getLink('dispatch.php/profile', array('username' => $boss['username'])) . '">';
                }
                $name_text .= $boss['fullname'];
                if ($boss['edit_about'] && $deputies_edit_about_enabled) {
                    $name_text .= '</a>';
                }
                echo $name_text;
                ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>
<br/>