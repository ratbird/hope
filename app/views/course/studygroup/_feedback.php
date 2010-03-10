<?
if ($errors = $flash['errors']) {
    if ($flash['create']) {
        echo MessageBox::error(_("Beim Anlegen der Studiengruppe traten folgende Fehler auf:"),$errors);        
    } elseif ($flash['edit']) {
        echo MessageBox::error(_("Beim Bearbeiten der Studiengruppe traten folgende Fehler auf:"),$errors);
    }
}

if ($success = $flash['success']) {
    echo MessageBox::success( $success );   
}

if ($info = $flash['info']) {
    echo MessageBox::info( $info ); 
}

if ($messages = $flash['messages']) {
    foreach ($messages as $type => $message_data) {
        echo MessageBox::$type( $message_data['title'], $message_data['details'] );
    }
}

if ($flash['question']) {
    echo $flash['question'];
}
