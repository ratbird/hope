<?
if ($errors = $flash['errors']) {
    if ($flash['create']) {
        echo MessageBox::error(_("Beim Anlegen der Studiengruppe traten folgende Fehler auf:"),htmlReady($errors));        
    } elseif ($flash['edit']) {
        echo MessageBox::error(_("Beim Bearbeiten der Studiengruppe traten folgende Fehler auf:"),htmlReady($errors));
    }
}

if ($success = $flash['success']) {
    echo MessageBox::success(htmlReady($success));   
}

if ($info = $flash['info']) {
    echo MessageBox::info(htmlReady($info)); 
}

if ($messages = $flash['messages']) {
    foreach ($messages as $type => $message_data) {
        echo MessageBox::$type( $message_data['title'], htmlReady($message_data['details']));
    }
}

if ($flash['question']) {
    echo $flash['question'];
}
