<date><?
echo $date->toString();
if ($date->getResourceId()) :
    echo _(", Ort:");
    echo implode(', ', getFormattedRooms(array($date->getResourceId() => '1'), false));
endif ?></date>
