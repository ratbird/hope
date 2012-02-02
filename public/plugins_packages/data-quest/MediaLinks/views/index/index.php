<div id="MediaLinks" style="padding-left:1em;padding-right:1em">
    <?php if ($isDozent): ?>
        <div><a href="index/add">Erstellen</a></div>
    <?php endif; ?>
    <?php if ($links): ?>
        <ul>
            <?php foreach ($links as $link): ?>
            <li><a href="<?= htmlReady($link->url) ?>" target="_blank"><?= empty($link->name) ? htmlReady($link->url) : htmlReady($link->name) ?></a>
                
                    <?php if ($isDozent): ?>
                        <span><a href="index/edit?id=<?= $link->id ?>"><?= Assets::img('icons/16/black/comment.png', array('alt' => 'Bearbeiten')) ?></a><a href="index/delete?id=<?= $link->id ?>"><?= Assets::img('icons/16/black/decline.png', array('alt' => 'Löschen')) ?></a></span>
                <?php endif; ?>
                      
            <div style="clear:both"></div>
            <i><?= htmlReady($link->description) ?></i>
              </li>
            <?php endforeach; ?>
        </ul>
        <?php else:?>
        Die Veranstaltung besitzt keine Links
    <?php endif; ?>
</div>