<? if (!ForumPerm::has('fav_entry', $seminar_id)) return; ?>

<!-- set/unset favorite -->
<? if (!$favorite) : ?>
    <a href="<?= PluginEngine::getLink('coreforum/index/set_favorite/'. $topic_id) ?>" onClick="STUDIP.Forum.setFavorite('<?= $topic_id ?>');return false;">
        <?= Assets::img('icons/16/blue/exclaim.png', array('title' => _('Beitrag merken'))) ?>
    </a>
<? else : ?>
    <a href="<?= PluginEngine::getLink('coreforum/index/unset_favorite/'. $topic_id) ?>" onClick="STUDIP.Forum.unsetFavorite('<?= $topic_id ?>');return false;">
        <?= Assets::img('icons/16/red/exclaim.png', array('title' => _('Beitrag nicht mehr merken'))) ?>
    </a>
<? endif ?>
