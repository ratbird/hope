<?= createQuestion(sprintf(_('Sind Sie sicher, dass das Anmeldeset "%s" '.
    'gel�scht werden soll? Damit werden alle Regeln zur Anmeldung zu den '.
    'verkn�pften Veranstaltungen aufgehoben.'), $courseset->getName()), 
    array('really' => true), array('cancel' => true), 
    $controller->url_for('admission/courseset/delete', $courseset->getId()));
?>
