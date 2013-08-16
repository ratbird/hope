<?php
/**
* send_mail_queue.class.php
*
* @author Rasmus Fuhse <fuhse@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access public
* @since  3.0
*/
require_once 'lib/classes/CronJob.class.php';

class SendMailQueueJob extends CronJob
{

    public static function getName()
    {
        return _('Mailqueue senden');
    }

    public static function getDescription()
    {
        return _('Sendet alle Einträge in der Mailqueue bis zu 24 Stunden, nachdem sie hinzugefügt wurden.');
    }

    public function execute($last_result, $parameters = array())
    {
        MailQueueEntries::sendAll();
    }
}
