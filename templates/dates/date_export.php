<?php
echo $date->toString();

if ($date->getResourceId()) : 
    echo ', '. _("Ort:") .' ';
    echo implode(', ', getPlainRooms(array($date->getResourceId() => '1')));
endif ?>
