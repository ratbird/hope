<?php
/**
* send_mail_queue.class.php
*
* @author Rasmus Fuhse <fuhse@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access public
* @since  3.0
*/

/**
 * Cronjob class to send the mailqueue each interval.
 */
class SendMailQueueJob extends CronJob
{

    /**
     * Returns the name of the cronjob.
     * @return string : name of the cronjob
     */
    public static function getName()
    {
        return _('Mailqueue senden');
    }

    /**
     * Returns the description of the cronjob.
     * @return string : description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Sendet alle Einträge in der Mailqueue bis zu 24 Stunden, nachdem sie hinzugefügt wurden.');
    }

    /**
     * Sends all mails in the queue.
     * @param integer $last_result : not evaluated for execution, so any integer 
     * will do. Usually it would be a unix-timestamp of last execution. But in 
     * this case we don't care at all.
     * @param array $parameters : not needed here
     */
    public function execute($last_result, $parameters = array())
    {
        MailQueueEntry::sendAll(Config::get()->MAILQUEUE_SEND_LIMIT);
    }
}
