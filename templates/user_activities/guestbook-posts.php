<table class="default">
<? foreach ($posts as $post): 
     $user_link = sprintf('<a href="%s">%s</a>',
                          URLHelper::getLink('about.php', array('username' => get_username($post['user_id']))),
                          htmlReady(get_fullname($post['user_id'])));
    $delete_link = URLHelper::getURL('', array('deletepost' => $post['post_id'], 'ticket' => get_ticket()));
?>
    <tbody>
        <tr>
            <td class="table_footer" style="font-weight: bold;">
                <?= sprintf(_('%s hat am %s geschrieben:'), $user_link, date('d.m.Y - H:i', $post['mkdate'])) ?>
            </td>
        </tr>
        <tr>
            <td class="table_row_odd">
                <?= formatready($post['content']) ?>
                <p align="right">
                    <?= Studip\LinkButton::create(_('Löschen'), $delete_link) ?>
                </p>
            </td>
        </tr>
        <tr>
            <td class="table_row_even">&nbsp;</td>
        </tr>
    </tbody>
<? endforeach; ?>
</table>
