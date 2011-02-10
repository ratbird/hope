<?
# Lifter010: TODO
$cat = -1;

// do we have a category-color?
foreach ($GLOBALS['PERS_TERMIN_KAT'] as $key => $data) :
    if ($data['color'] == $entry['color']) :
        $cat = $key; break;
    endif;
endforeach;

$element_id = md5(uniqid());
?>

<div id="schedule_entry_<?= $element_id ?>_<?= $entry['id'] ?>" class="schedule_entry <?= ((isset($entry['visible']) && !$entry['visible']) ? 'invisible_entry' : '') . ($entry['onClick'] ? " clickable" : "") ?>" style="top: <?= $top ?>px; height: <?= $height ?>px; width: <?= $width ?>%<?= ($col > 0) ? ';left:'. ($col * $width) .'%' : '' ?>" title="<?= htmlReady($entry['title']) ?>">
    <a<? /*  $entry['url'] ? ' href="'.$entry['url'].'"' : "" */ ?>
        <?= $entry['onClick'] ? 'onClick="STUDIP.Calendar.clickEngine('. $entry['onClick'].', this, event); return false;"' : '' ?>>
    <!-- for safari5 we need to set the height for the dl as well -->
    <dl class="hover" style="height: <?= $height - 2 ?>px;
        border: 1px solid <?= $entry['color'] ?>;
        background-image: url('<?= Assets::url('images/calendar/category'. $cat .'.jpg') ?>');
        background-position: left top;">
        <dt style="background-color: <?= $entry['color'] ?>;">
            <?= floor($entry['start']/100).":".(($entry['start']%100) < 10 ? "0" : "").($entry['start']%100) ?> - <?= floor($entry['end']/100).":".(($entry['end']%100) < 10 ? "0" : "").($entry['end']%100) ?><?= $entry['title'] ? ', <b>'. htmlReady($entry['title']) .'</b>' : '' ?>
        </dt>
        <dd>
            <?= nl2br(htmlReady($entry['content'])) ?><br>
        </dd>
    </dl>
    </a>

    <div class="snatch" style="display: none"><div> </div></div>

    <?= $this->render_partial('calendar/entries/icons', compact('element_id')) ?>
</div>
