<a href="<?= URLHelper::getLink('about.php?username=' . $user->username) ?>">
    <span style="color: #339;">
        <?= htmlReady($user->getFullName()) ?>
    </span>
</a>
<?= date('d.m.Y', $news_item['date']) ?> |
<span style="color: #050"><?= object_return_views($news_item['news_id']) ?></span> |

<? if ($news_item['allow_comments']):
    $num = StudipComments::NumCommentsForObject($news_item['news_id']);
    $visited = object_get_visit($news_item['news_id'], 'news', false, false);
    $new = StudipComments::NumCommentsForObjectSinceLastVisit($news_item['news_id'], $visited, $GLOBALS['user']->id);
?>

<? if ($new): ?>
<span style="color: red;" title="<?= sprintf(_('%s neue(r) Kommentar(e)'), $new) ?>">
<? else: ?>
<span style="color: #aa6;">
<? endif; ?>
    <?= $num ?>
</span> |
<? endif; ?>