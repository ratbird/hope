<?
# Lifter010: TODO
$cat = 1;

// do we have a category-color?
foreach ($GLOBALS['PERS_TERMIN_KAT'] as $key => $data) :
    if ($data['color'] == $entry['color']) :
        $cat = $key; break;
    endif;
endforeach;
?>

<div class="schedule_entry <?= (isset($entry['visible']) && !$entry['visible']) ? 'invisible_entry' : '' ?>" style="top: <?= $top ?>px; height: <?= $height ?>px; width: <?= $width ?>%<?= ($col > 0) ? ';left:'. ($col * $width) .'%' : '' ?>" title="<?= htmlReady($entry['title']) ?>">
    <a href="<?= ($entry['url']) ? $entry['url'] : $controller->url_for('calendar/'. $context .'/entry/'. $entry['id']) ?>"
        <?= $entry['onClick'] ? 'onClick="' . $entry['onClick'] . '"' : '' ?>>
    <!-- for safari5 we need to set the height for the dl as well -->
    <dl class="hover" style="height: <?= $height - 2 ?>px; 
        border: 1px solid <?= $entry['color'] ?>;
        background-image: url('<?= Assets::url('images/calendar/category'. $cat .'.jpg') ?>');
        background-position: left top;
        ">
        <dt style="background-color: <?= $entry['color'] ?>;">
            <?= $entry['start_formatted'] ?> - <?= $entry['end_formatted'] ?><?= $entry['title'] ? ', <b>'. htmlReady($entry['title']) .'</b>' : '' ?>
        </dt>
        <dd> 
            <?= nl2br(htmlReady($entry['content'])) ?><br>
        </dd>
    </dl>
    </a>

    <div class="snatch" style="display: none"><div> </div></div>

    <div id="schedule_icons">
        <? if (is_array($entry['icons'])) foreach ($entry['icons'] as $icon) : ?>
            <? if($icon['url']) : ?>
            <a href="<?= $icon['url'] ?>" <?= $icon['onClick'] ? 'onClick="'. $icon['onClick'] .'"' : '' ?>>
                <?= Assets::img($icon['image'], array('title' => htmlReady($icon['title']), 'alt' => htmlReady($icon['title']))) ?>
            </a>
            <? else : ?>
            <?= Assets::img($icon['image'], array('title' => htmlReady($icon['title']))) ?>
            <? endif; ?>
        <? endforeach ?>
    </div>
</div>
