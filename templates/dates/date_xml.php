<date><?
echo $date->toString();
if ($date->getResourceId()) :
    echo ', '. _("Ort:") .' ';
    echo htmlspecialchars(implode(', ', getPlainRooms(array($date->getResourceId() => '1'))));
endif ?></date>
