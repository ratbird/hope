<?
# Lifter010: TODO
?>
<div class="topic"><b><?=_("Stud.IP-Rangliste")?></b></div>
<? if(count($persons)>0) : ?>
<table class="default">
<tr>
    <th width="3%" align="left"><?= _("Platz") ?></th>
    <th width="1%"></th>
    <th align="left" width="51%"><?= _("Name") ?></th>
    <th align="left" width="15%"></th>
    <th align="left" width="15%"><?= _("Punkte") ?></th>
    <th align="left" width="15%"><?= _("Titel") ?></th>
    <th></th>
</tr>
<? foreach ($persons as $index=>$person): ?>
<tr class="<?=TextHelper::cycle('hover_odd', 'hover_even')?>">
    <td align="right"><?= $index+(($page-1)*get_config('ENTRIES_PER_PAGE'))+1 ?>. </td>
    <td> <?=$person['avatar']?></td>
    <td>
        <a href="<?=URLHelper::getLink("about.php?username=". $person['username'])?>"><?=$person['name']?></a>
        <? foreach ($person['is_king'] as $type => $text) : ?>
            <?= Assets::img("icons/16/yellow/crown.png", array('alt' => $text, 'title' => $text, 'class' => 'text-top')) ?>
        <? endforeach ?>
    </td>
    <td><?=$person['content']?></td>
    <td><?=$person['score']?></td>
    <td><?=$person['title']?></td>
    <td align="right"><? if($person['userid']==$user->id): ?><a href="<?=URLHelper::getLink('score.php?cmd=kill')?>"><?= Assets::img('icons/16/blue/trash.png', array('title' => _("Ihren Wert von der Liste löschen"), 'class' => 'text-top')) ?></a><? endif; ?></td>
</tr>
<? endforeach ?>
</table>
<? if (ceil($numberOfPersons / get_config('ENTRIES_PER_PAGE')) > 1) : ?>
<div style="text-align:right; padding-top: 2px; padding-bottom: 2px" class="content_seperator"><?= $this->render_partial("shared/pagechooser", array("perPage" => get_config('ENTRIES_PER_PAGE'), "num_postings" => $numberOfPersons,
    "page"=>$page, "pagelink" => "score.php?page=%s"));
?></div>
<? endif ?>
<? endif ?>

<?php
if ($score->ReturnPublik()) {
    $icon = 'icons/16/black/remove/crown.png';
    $action = '<a href="'. URLHelper::getLink('score.php?cmd=kill') .'">'._("Ihren Wert von der Liste löschen").'</a>';
} else {
    $icon = 'icons/16/black/add/crown.png';
    $action = '<a href="'. URLHelper::getLink('score.php?cmd=write') .'">'._("Diesen Wert auf der Liste veröffentlichen").'</a>';
}
$infobox = array(
    'picture' => 'infobox/board2.jpg',
    'content' => array(
        array("kategorie" => _("Ihre Punkte: ").$score->ReturnMyScore()),
        array("kategorie" => _("Ihr Titel: ").$score->ReturnMyTitle()),
        array("kategorie" => _("Information:"),
            "eintrag" => array(
                array(
                    "icon" => "icons/16/black/info.png",
                    "text" => _("Auf dieser Seite können Sie abrufen, wie weit Sie in der Stud.IP-Rangliste aufgestiegen sind. Je aktiver Sie sich im System verhalten, desto höher klettern Sie!")
                ),
                array(
                    "icon" => "icons/16/black/info.png",
                    "text" => _("Sie erhalten auf der Profilseite von MitarbeiternInnen an Einrichtungen auch weiterführende Informationen, wie Sprechstunden und Raumangaben.")
                )
            )
        ),
        array("kategorie" => _("Aktionen:"),
            "eintrag" => array(
                array(
                    "icon" => $icon,
                    "text" => $action
                )
            )
        )
    )
);
