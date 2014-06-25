<?= $question ?>
<? if($perm || $news): ?>
<section class="contentbox">
    <header>
        <nav>
            <? if ($perm): ?>
            <a href="<?= $controller->link_for('news/edit_news/new/' . $range); ?>" rel="get_dialog">
                <?= Assets::img('icons/16/blue/add.png'); ?>
            </a>
            <? endif; ?>
            <? if ($rss_id): ?>
            <a href="<?= URLHelper::getLink('rss.php', array('id' => $rss_id)) ?>">
                <img src="<?= Assets::image_path('icons/16/blue/rss.png') ?>"
                     <?= tooltip(_('RSS-Feed')) ?>>
            </a>
            <? endif; ?>
        </nav>
        <h1>
            <?= Assets::img('icons/16/black/news.png') ?>
            <?= _('Ankündigungen') ?>
        </h1>
    </header>
    <? foreach ($news as $new): ?>
    <? $is_new = ($new['chdate'] >= object_get_visit($new->id, 'news', false, false))
            && ($new['user_id'] != $GLOBALS['user']->id); ?>
    <article class="<?= ContentBoxHelper::classes($new->id, $is_new) ?>" id="<?= $new->id ?>">
        <header>
            <nav>
                <?= $this->render_partial('news/_actions.php', array('new' => $new, 'range' => $range)) ?>
            </nav>
            <h1>
                <a href="<?= ContentBoxHelper::href($new->id) ?>">
                    <?= Assets::img('icons/16/grey/news.png'); ?>
                    <?= htmlReady($new['topic']); ?>
                </a>
            </h1>
        </header>
        <section>
            <?= formatReady($new['body']) ?>

        </section>
        <?= $this->render_partial('news/_comments.php', array('new' => $new, 'range' => $range)) ?>
    </article>
    <? endforeach; ?>
    <? if (!$news): ?>
    <section>
        <?= _('Es sind keine aktuellen Ankündigungen vorhanden. Um neue Ankündigungen zu erstellen, klicken Sie rechts auf das Plus-Zeichen.') ?>
    </section>
    <? endif; ?>
</section>
<?endif;