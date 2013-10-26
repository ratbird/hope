<? $videofiles = $og->getVideoFiles() ?>
<? $audiofiles = $og->getAudioFiles() ?>
<div class="opengraph<?= count($videofiles) > 0 ? " video" : "" ?>">
    <a href="<?= URLHelper::getLink($og['url'], array(), false) ?>" class="opengraph_info" target="_blank">
        <? if ($og['image'] && !count($videofiles)) : ?>
        <div class="opengraph_info_image" style="background-image: url('<?= htmlReady($og['image']) ?>');">
        </div>
        <? endif ?>
        <strong><?= htmlReady($og['title']) ?></strong>
        <? if (!count($videofiles)) : ?>
        <p>
        <?= htmlReady($og['description']) ?>
        </p>
        <? endif ?>
    </a>
    <? if (count($videofiles)) : ?>
    <div class="video">
        <? if (count($videofiles) === 1 && $videofiles[0][1] === "application/x-shockwave-flash") : ?>
        <iframe width="100%" height="200px" frameborder="0" src="<?= htmlReady($videofiles[0][0]) ?>"></iframe>
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
        <? if (count($audiofiles) === 1 && $audiofiles[0][1] === "application/x-shockwave-flash") : ?>
        <iframe width="100%" height="100px" frameborder="0" src="<?= htmlReady($og['audio']) ?>"></iframe>
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