<? $videofiles = $og->getVideoFiles() ?>
<? $audiofiles = $og->getAudioFiles() ?>
<div class="opengraph <? if (count($videofiles) > 0) echo 'video'; ?> <? if (count($audiofiles) > 0) echo 'audio'; ?>">
<? if ($og['image'] && count($videofiles) === 0): ?>
    <a href="<?= URLHelper::getLink($og['url'], array(), false) ?>" class="image" target="_blank"
       style="background-image:url(<?= $og['image'] ?>)">
    </a>
<? endif; ?>  
    <a href="<?= URLHelper::getLink($og['url'], array(), false) ?>" class="info" target="_blank">
        <strong><?= htmlReady($og['title']) ?></strong>
    <? if (!count($videofiles)) : ?>
        <p><?= htmlReady($og['description']) ?></p>
    <? endif ?>
    </a>
<? if (count($videofiles)) : ?>
    <div class="video">
    <? if (in_array($videofiles[0][1], array("text/html", "application/x-shockwave-flash"))) : ?>
        <a href="<?= htmlReady($videofiles[0][0]) ?>"
           class="flash-embedder"
           style="background-image: url('<?= htmlReady($og['image']) ?>');"
           title="<?= _("Video abspielen") ?>">
            <?= Assets::img("icons/80/blue/play.svg", array('class' => "play"))?>
        </a>
    <? else : ?>
        <video width="100%" height="200px" controls>
        <? foreach ($videofiles as $file) : ?>
            <source src="<?= htmlReady($file[0]) ?>"<?= $file[1] ? ' type="'.htmlReady($file[1]).'"' : "" ?>></source>
        <? endforeach ?>
        </video>
    <? endif ?>
    </div>
<? endif ?>
<? if (count($audiofiles)) : ?>
    <div class="audio">
    <? if (in_array($audiofiles[0][1], array("text/html", "application/x-shockwave-flash"))) : ?>
        <a href="<?= htmlReady($audiofiles[0][0]) ?>"
           class="flash-embedder"
           style="background-image: url('<?= htmlReady($og['image']) ?>');"
           title="<?= _("Audio abspielen") ?>">
            <?= Assets::img("icons/100/blue/play.svg")?>
        </a>
    <? else : ?>
        <audio width="100%" height="50px" controls>
        <? foreach ($audiofiles as $file) : ?>
            <source src="<?= htmlReady($file[0]) ?>"<?= $file[1] ? ' type="'.htmlReady($file[1]).'"' : "" ?>></source>
        <? endforeach ?>
        </audio>
    <? endif ?>
    </div>
<? endif ?>
</div>