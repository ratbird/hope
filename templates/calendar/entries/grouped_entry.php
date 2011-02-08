<?
# Lifter010: TODO
$cat = 1;

// do we have a category-color?
foreach ($GLOBALS['PERS_TERMIN_KAT'] as $key => $data) :
    if ($data['color'] == $entry[0]['color']) :
        $cat = $key; break;
    endif;
endforeach;

$title = $heading = $ids = array();

// check, if at least one entry is visible
$show = false;
foreach ($entry as $element) :
    $title[] = $element['content'];
    if ($element['title']) :
        $heading[] = $element['title'];
    endif;
    $ids[] = $element['id'];
    if ($element['visible']) $show = true;
endforeach;
?>

<? if ($show || $show_hidden) : ?>
<div class="schedule_entry <?= !$show ? 'invisible_entry' : '' ?>" style="top: <?= $top ?>px; height: <?= $height ?>px; width: <?= $width ?>%<?= ($col > 0) ? ';left:'. ($col * $width) .'%' : '' ?>" title="<?= htmlReady(implode(', ', $title)) ?>">

    <a href="<?= $entry[0]['url'] ?>"
        <?= $entry[0]['onClick'] ? 'onClick="' . $entry[0]['onClick'] . '"' : '' ?> data="<?= $entry[0]['start'] .'/'. $entry[0]['end'] .'/'. implode(',', $ids) ?>">
    <!-- for safari5 we need to set the height for the dl as well -->
    <dl class="hover" style="height: <?= $height ?>px;
        border: 1px solid <?= $entry[0]['color'] ?>;
        background-image: url('<?= Assets::url('images/calendar/category'. $cat .'.jpg') ?>')">
        <dt style="background-color: <?= $entry[0]['color'] ?>">
            <?= $entry[0]['start_formatted'] ?> - <?= $entry[0]['end_formatted'] ?>, <b><?= htmlReady(implode(', ', $heading)) ?></b>
        </dt>
        <dd>
            <? foreach ($entry as $element) :
                if (!isset($element['visible']) || $element['visible']) : ?>
                <?= htmlReady($element['content']) ?><br>
                <? elseif ($show_hidden) : ?>
                <span class="invisible_entry"><?= htmlReady($element['content']) ?></span><br>
                <? endif ?>
            <? endforeach; /* the elements for this grouped entry */ ?>
        </dd>
    </dl>
    </a>

    <div class="snatch" style="display: none"><div> </div></div>

    <div style="position: absolute; right: 0px; top: 0px;">
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
<? endif ?>
