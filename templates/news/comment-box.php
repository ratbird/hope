    <tr>
        <td width="20">
        <? if ($comments_admin): ?>
            <input type="CHECKBOX" name="mark_comments[]" value="<?=$comment['comment_id']?>" <?=tooltip(_("Diesen Kommentar zum L�schen vormerken"),false)?>>
        <? endif ?>
        </td>
        <td>
            <div style="display: inline; color: #888888; font-size: 0.8em;">#<?= $index + 1 ?> - </div>
            <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $comment[2]) ?>" style="font-weight: bold; color: #888888; font-size: 0.8em;">
                <?= htmlReady($comment[1]) ?>
            </a>
            <div style="display: inline; color: #888888; font-size: 0.8em; float: right;">
            <?= reltime($comment[3]) ?>
            </div>
            <br>
            <?= formatReady($comment[0]) ?>
        </td>
        <td width="20">
        <? if ($comments_admin): ?>
            <input type="image" name="news_delete_comment_<?=$comment['comment_id']?>" src="<?= Assets::image_path('icons/16/blue/trash.png')?>" <?=tooltip(_("Kommentar entfernen"),false)?>>
        <? endif ?>
        </td>
    </tr>