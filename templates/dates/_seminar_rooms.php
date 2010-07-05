<?php
unset($freetext['']);
if (!isset($link)) $link = false;
if (($assigned && sizeof($assigned)) || sizeof($freetext)) :
  
    if ($assigned):
        $rooms = getFormattedRooms($assigned, $link);
    endif;

    if ($freetext):
        foreach ($freetext as $name => $count) :
            if ($name) $rooms[] = '(' . $name . ')';
        endforeach;
    endif;
    $ort .= ' Ort: ';
    $ort .= implode(', ', array_slice($rooms, 0, 3));
else :
    $ort = ' ' . _('k.A.');
endif;
echo $ort;
