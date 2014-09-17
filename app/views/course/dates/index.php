<style>
    .themen_list, .dozenten_list {
        margin: 0px;
        padding: 0px;
        list-style-type: none;
    }
</style>
<?php
    $lastSemester = null;
    $allSemesters = array();
    foreach ($dates as $key => $date) {
        $currentSemester = Semester::findByTimestamp($date['date']);
        if ($currentSemester && (
            !$lastSemester ||
            $currentSemester->getId() !== $lastSemester->getId()
        )) {
            $allSemesters[] = $currentSemester;
            $lastSemester = $currentSemester;
        }
    }
    $lostDateKeys = array();

    if (!count($dates)) {
        PageLayout::postMessage(
            MessageBox::info(_('Keine Termine vorhanden'))
        );
    }

    foreach ($allSemesters as $semester) {
?>
<table class="dates default">
    <caption><?= htmlReady($semester['name']) ?></caption>
    <thead>
        <tr class="sortable">
            <th class="sortasc"><?= _('Zeit') ?></th>
            <th><?= _('Typ') ?></th>
            <th><?= _('Thema') ?></th>
            <th><?= _('Raum') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
        // convert $dates object into an array
        $dateArray = array();
        foreach ($dates as $key => $date) {
            $dateArray[$key] = $date;
        }

        // sort the dates array by start time and room name
        usort($dateArray, function($a, $b) {
            if ($a->date === $b->date) {
                // two dates at the same time shouldn't have the same room
                // and if they have, they are indistinguishable (user-wise)
                // therefore ignore 0 value for a->room === b->room
                return $a->getRoom() <= $b->getRoom() ? -1 : 1;
            }
            return $a->date < $b->date ? -1 : 1;
        });

        // print dates
        foreach ($dateArray as $key => $date) {
            $dateSemester = Semester::findByTimestamp($date['date']);
            if ($dateSemester &&
                $semester->getId() === $dateSemester->getId()
            ) {
                echo $this->render_partial(
                    'course/dates/_date_row.php',
                    compact('date', 'dates', 'key')
                );
            } elseif (!$dateSemester && !in_array($key, $lostDateKeys)) {
                $lostDateKeys[] = $key;
            }
        }
    ?>
    </tbody>
</table>
<?php
    }

    if (count($lostDateKeys)) {
?>
<table class="dates default">
    <caption><?= _('Ohne Semester') ?></caption>
    <thead>
    <tr class="sortable">
        <th class="sortasc"><?= _('Zeit') ?></th>
        <th><?= _('Typ') ?></th>
        <th><?= _('Thema') ?></th>
        <th><?= _('Raum') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
        foreach ($lostDateKeys as $key) {
            $date = $dates[$key];
            echo $this->render_partial(
                'course/dates/_date_row.php',
                compact('date', 'dates', 'key')
            );
        }
    ?>
    </tbody>
</table>
<?php
    }
?>
<script>
jQuery(function($) {
    $('.dates').tablesorter({
        textExtraction: function(node) {
            var $node = $(node);
            return String($node.data('timestamp') || $node.text()).trim();
        },
        cssAsc: 'sortasc',
        cssDesc: 'sortdesc',
        sortList: [[0, 0], [3, 0]],
        headers: {
            0: { sortInitialOrder: 'asc' },
            3: { sortInitialOrder: 'asc' }    
        }
    });
});
</script>
<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path('sidebar/date-sidebar.png'));

$actions = new ActionsWidget();
$actions->addLink(
    _('Als Doc-Datei runterladen'),
    URLhelper::getURL('dispatch.php/course/dates/export')
);
$sidebar->addWidget($actions);
