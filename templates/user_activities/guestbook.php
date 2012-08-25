<table class="default">
    <thead>
        <tr>
            <td class="table_header_bold" colspan="4"><?= _('Übersicht Gästebucheinträge') ?></td>
        </tr>
    </thead>
    <tbody>
    <? if (empty($posts)): ?>
        <tr>
            <td colspan="4" class="printhead" style="text-align: center;">
                <?= _('keine Einträge gefunden') ?>
            </td>
        </tr>
    <? endif; ?>
    <? foreach ($posts as $post): ?>
        <tr>
            <? printhead(0, 0, false, $post['is_open'], false, '&nbsp;', $post['title'], $post['addon'], 0); ?>
        </tr>

        <? if ($post['is_open'] == 'open'): ?>
        <tr>
            <td class="printcontent">&nbsp;</td>
            <td class="printcontent" colspan="3">
                <div style="margin-bottom: 10px;">
                    <b>
                        <a href="<?= URLHelper::getLink('about.php?guestbook=open#guest', array('username' => $post['user']->username)) ?>">
                            <?= Assets::img('icons/16/blue/guestbook') ?>
                            <?= _('Gästebuch') ?>:
                            <?= htmlReady($post['user']->getFullName()) ?>
                        </a>
                    </b>
                </div>
                <?= show_posts_guestbook($user_id, $post['range_id']) ?>
            </td>
        </tr>
        <? endif; ?>
    <? endforeach; ?>
    </tbody>
</table>