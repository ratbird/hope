<?
# Lifter010: TODO
?>
<table class "default">
    <caption>
		<?=_("Personen, deren Standardvertretung ich bin") ?>
	</caption>      
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
                    $name_text .= '<a href="'.URLHelper::getLink('dispatch.php/profile', array('username' => $boss['username'])).'">';
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