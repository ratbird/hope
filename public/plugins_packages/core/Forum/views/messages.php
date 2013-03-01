<? if (!empty($flash['messages'])) foreach ($flash['messages'] as $type => $message): ?>
    <?= MessageBox::$type(htmlReady($message)) ?>
<? endforeach ?>