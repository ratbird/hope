<tr>
  <td width="100%" colspan="2">
    <b><?=_("Statusmeldungen")?>:</b>
    <br>
  </td>
</tr>

<? for ($i = 0; $i < count($messages); $i++) : ?>
  <? $message = explode('§', $messages[$i]); ?>
  <tr>
    <td colspan="2">
      <?
      switch ($message[0]) {
         case 'info':
           echo MessageBox::info($message[1]); break;
         case 'error':
           echo MessageBox::error($message[1]); break;
         case 'msg':
           echo MessageBox::success($message[1]); break;
      }
      ?>
    </td>
  </tr>
<? endfor ?>

