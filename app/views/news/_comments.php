<? if ($new['allow_comments']): ?>
    <footer>
        <? if (Request::get('comments')): ?>
                <h1>
                    <?= _('Kommentare') ?>
                </h1>

            <? foreach (StudipComment::GetCommentsForObject($new['news_id']) as $index => $comment): ?>
                <?= $this->render_partial('news/_commentbox', compact('index', 'comment')) ?>
            <? endforeach; ?>
            <? if (!$nobody) : ?>
                <form action="<?= ContentBoxHelper::href($new->id, array('comments' => 1)) ?>" method="POST">
                    <?= CSRFProtection::tokenTag() ?>
                    <input type="hidden" name="comsubmit" value="<?= $new['news_id'] ?>">
                    <div align="center">
                        <textarea class="add_toolbar wysiwyg" name="comment_content" style="width:70%" rows="8" cols="38" wrap="virtual" placeholder="<?= _('Geben Sie hier Ihren Kommentar ein!') ?>"></textarea>
                        <br>
                        <?= Studip\Button::createAccept(_('Absenden')) ?>
                    </div>
                </form>
            <? endif ?>
        <? else: ?>
        <a href="<?= ContentBoxHelper::href($new['news_id'], array("comments" => 1)) ?>">
                <?= sprintf(_('Kommentare lesen (%s) / Kommentar schreiben'), StudipComment::NumCommentsForObject($new['news_id']))
                ?>
            </a>
        <? endif; ?>
    </footer>
<? endif; ?>
