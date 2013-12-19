<?php

class Message extends SimpleORMap {

    /**
     * constructor
     * @param string id: primary key of table dokumente
     * @return null
     */
    function __construct($id = null)
    {
        $this->db_table = 'message';
        $this->has_one['author'] = array(
            'class_name' => 'User',
            'foreign_key' => 'autor_id',
            'assoc_foreign_key' => 'user_id'

        );
        $this->has_many['users'] = array(
            'class_name' => 'MessageUser'
        );
        $this->has_many['attachments'] = array(
            'class_name' => 'StudipDocument',
            'assoc_foreign_key' => 'range_id'
        );
        parent::__construct($id);
    }

    public function getSender()
    {
        return $this->users->filter(function ($mu) {return $mu->snd_rec === 'snd';})->first()->user;
    }

    public function getRecipients()
    {
        return $this->users
            ->filter(function ($mu) { return $mu->snd_rec === 'rec'; })
            ->map(function ($mu) { return $mu->user; });
    }

    public function markAsRead($user_id)
    {
        return $this->markAs($user_id, 1);
    }

    public function markAsUnread($user_id)
    {
        return $this->markAs($user_id, 0);
    }

    private function markAs($user_id, $state_of_flag)
    {
        $changed = 0;
        foreach ($this->users->findBy('user_id', $user_id) as $mu) {
            $mu->readed = $state_of_flag;
            $changed += $mu->store();
        }
        if ($changed) {
            $this->users->refresh();
        }
        return $changed;
    }

    public static function send($sender, $recipients, $subject, $message)
    {

        $messaging = new \messaging();
        $result = $messaging->insert_message($message,
                                             $recipients,
                                             $sender,
                                             time(),
                                             $message_id = md5(uniqid('message', true)),
                                             false, // deleted
                                             '', // force email
                                             $subject);
        return $result ? self::find($message_id) : null;
    }
}
