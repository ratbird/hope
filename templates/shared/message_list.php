<? if (is_array($messages)) foreach ($messages as $message) : ?>
  <? $msg = explode(chr(167), $message); /* use ordinal value of paragraph symbol */ ?>
      <?
      switch ($msg[0]) {
         case 'info':
           echo MessageBox::info($msg[1]); break;
         case 'error':
           echo MessageBox::error($msg[1]); break;
         case 'msg':
           echo MessageBox::success($msg[1]); break;
      }
      ?>
<? endforeach ?>
