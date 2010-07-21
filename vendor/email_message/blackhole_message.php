<?php
/*
 * blackhole_message.php
 *
 * no escape :)
 *
 */


class blackhole_message_class extends email_message_class
{

    function SendMail($to,$subject,$body,$headers,$return_path)
    {
    }

}
?>