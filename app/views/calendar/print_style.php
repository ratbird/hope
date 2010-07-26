div.schedule_day {
    height: <?= $whole_height ?>px;
    border-right: 3px double #DDDDDD;
    position: relative;
}

div.schedule_marker {
    border-bottom: 1px dotted #DDDDDD;
    border-top: 1px solid #DDDDDD;
    height: <?= floor($entry_height / 2)  ?>px;
    line-height: <?= floor($entry_height / 2) ?>px;
    margin-bottom: <?= floor($entry_height / 2) ?>px;
    padding: 0px;
}

div.schedule_hours {
    height: <?= $entry_height ?>px;
    border-top: 1px solid #DDDDDD;
    border-right: 1px solid #DDDDDD;
    padding-bottom: 1px;
    color: black;
    padding-right: 3px;
}