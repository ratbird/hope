<html>
    <head>
        <title>Ablaufplan</title>
    </head>
    <body>

<? if (sizeof($dates)) : ?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">

<?
$semester = new SemesterData();
$all_semester = $semester->getAllSemesterData();

foreach ($dates as $date) :
    if ( ($grenze == 0) || ($grenze < $date['start']) ) {
        foreach ($all_semester as $zwsem) {
            if ( ($zwsem['beginn'] < $date['start']) && ($zwsem['ende'] > $date['start']) ) {
                $grenze = $zwsem['ende'];
                ?>
                <tr>
                    <td colspan="2">
                        <h1><?= $zwsem['name'] ?></h1>
                    </td>
                </tr>
                <?
            }
        }
    }
    ?>
    <tr>
        <td width="50%"><?= htmlReady($date['date'])  ?></td>
        <td width="50%"><?= htmlReady($date['title']) ?></td>
    </tr>
<? endforeach ?>
    
</table>
<? endif ?>