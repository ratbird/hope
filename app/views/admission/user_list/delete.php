<?= createQuestion(sprintf(_('Soll die Nutzerliste %s wirklich gel�scht werden?'), 
    $list->getName()), array('really' => true), array('cancel' => true), 
    $controller->url_for('admission/userlist/delete', $userlist->getId()));
?>
