<?
if ($success = $flash['success']) {
    echo MessageBox::success($success);
}

if ($error = $flash['error']) {
    echo MessageBox::error($error);
}

if ($info = $flash['info']) {
    echo MessageBox::info($info);
}

if ($messages = $flash['messages']) {
    foreach ($messages as $type => $message_data) {
        echo MessageBox::$type( $message_data['title'], $message_data['details']);
    }
}

if ($flash['question']) {
    echo $flash['question'];
}
