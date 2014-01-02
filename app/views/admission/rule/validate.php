<?php
if ($errors) {
    if ($via_ajax) {
        $errors = array_map('studip_utf8encode', $errors);
    }
    echo MessageBox::error(_('Fehler:'), $errors);
}
?>