<?
$cat = 1;

// do we have a category-color?
foreach ($GLOBALS['PERS_TERMIN_KAT'] as $key => $data) :
    if ($data['color'] == $entry['color']) :
        $cat = $key; break;
    endif;
endforeach;
?>

<div id="schedule_entry_<?= md5(uniqid()) ?>_<?= $entry['id'] ?>" class="schedule_entry <?= ((isset($entry['visible']) && !$entry['visible']) ? 'invisible_entry' : '') . ($entry['onClick'] ? " clickable" : "") ?>" style="top: <?= $top ?>px; height: <?= $height ?>px; width: <?= $width ?>%<?= ($col > 0) ? ';left:'. ($col * $width) .'%' : '' ?>" title="<?= htmlReady($entry['title']) ?>">
    <a<?= $entry['url'] ? ' href="'.$entry['url'].'"' : "" ?>
        <?= $entry['onClick'] ? 'onClick="STUDIP.Calendar.clickEngine('. $entry['onClick'].', this, event);"' : '' ?>>
    <!-- for safari5 we need to set the height for the dl as well -->
    <dl class="hover" style="height: <?= $height - 2 ?>px; 
        border: 1px solid <?= $entry['color'] ?>;
        background-color: <?= $entry['color'] ?>;
        ">
        <dt style="background-color: <?= $entry['color'] ?>;">
            <?= floor($entry['start']/100).":".(($entry['start']%100) < 10 ? "0" : "").($entry['start']%100) ?> - <?= floor($entry['end']/100).":".(($entry['end']%100) < 10 ? "0" : "").($entry['end']%100) ?><?= $entry['title'] ? ', <b>'. htmlReady($entry['title']) .'</b>' : '' ?>
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
            <a href="<?= $icon['url'] ?>" <?= $icon['onClick'] ? 'onClick="STUDIP.Calendar.clickEngine('. $icon['onClick'].', this, event);"' : '' ?>>
                <?= Assets::img($icon['image'], array('title' => htmlReady($icon['title']), 'alt' => htmlReady($icon['title']))) ?>
            </a>
            <? else : ?>
            <?= Assets::img($icon['image'], array('title' => htmlReady($icon['title']))) ?>
            <? endif; ?>
        <? endforeach ?>
    </div>
</div>
