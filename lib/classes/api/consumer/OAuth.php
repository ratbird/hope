<?php
namespace API\Consumer;
use StudipAutoloader, DBManager, OAuthRequestVerifier, OAuthStore, OAuthServer, Exception;
use \API\UserPermissions;

StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . DIRECTORY_SEPARATOR . 'vendor/oauth-php/library/');

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo    documentation
 */
class OAuth extends Base
{
    public static function detect()
    {
        if (OAuthRequestVerifier::requestIsSigned()) {
            $user_id = false;

            $parameters = (in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST')))
                        ? null
                        : $GLOBALS['_' . $_SERVER['REQUEST_METHOD']];

            $req = new OAuthRequestVerifier(null, null, $parameters);
            $result = $req->verifyExtended('access');

            // @todo
            # self::$consumer_key = $result['consumer_key'];

            $query = "SELECT user_id FROM api_oauth_user_Mapping WHERE oauth_id = :oauth_id";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':oauth_id', $result['user_id']);
            $statement->execute();
            $user_id = $statement->fetchColumn();
            
            if (!$user_id) {
                return;
            }

            $consumer = reset(self::findByAuth_Key($result['consumer_key']));
            $consumer->setUser($user_id);
            return $consumer;
        } else {
            try {
                // Check if there is a valid request token in the current request
                // Returns an array with the consumer key, consumer secret, token, token secret and token type.
                $rs = self::getServer()->authorizeVerify();

                $query = "SELECT consumer_id
                          FROM api_consumers
                          WHERE consumer_type = 'oauth' AND auth_key = :key";
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':key', $rs['consumer_key']);
                $statement->execute();
                $id = $statement->fetchColumn();

                if ($id) {
                    return new self($id);
                }
            } catch (Exception $e) {
            }
        }
    }
    
    public static function getServer()
    {
        return new OAuthServer();
    }
    
    
    public function __construct($id = null)
    {
        parent::__construct($id);
        
        $this->registerCallback('before_store', 'before_store');
    }
    
    protected function before_store()
    {
        static $mapping = array(
            'auth_key'    => 'consumer_key',
            'auth_secret' => 'consumer_secret',
            'active'      => 'enabled',
            'contact'     => 'requester_name',
            'email'       => 'requester_email',
            'callback'    => 'callback_uri',
            'url'         => 'application_uri',
            'title'       => 'application_title',
            'description' => 'application_descr',
            'notes'       => 'application_notes',
            'type'        => 'application_type',
            'commercial'  => 'application_commercial',
        );

        $consumer = array();
        foreach ($mapping as $from => $to) {
            $consumer[$to] = $this->$from;
        }

        $query = "SELECT osr_id
                  FROM oauth_server_registry
                  WHERE osr_consumer_key = :key AND osr_consumer_secret = :secret";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':key', $this->auth_key);
        $statement->bindValue(':secret', $this->auth_secret);
        $statement->execute();
        $consumer['id'] = $statement->fetchColumn();
        
        $consumer_key = OAuthStore::instance('PDO')->updateConsumer($consumer, null, true);

        if ($this->isNew()) {
            $consumer = OAuthStore::instance('PDO')->getConsumer($consumer_key, null, true);
            $this->auth_key    = $consumer['consumer_key'];
            $this->auth_secret = $consumer['consumer_secret'];
        }
    }

    public function grantAccess($user_id = null)
    {
        if ($user_id === null && $this->hasUser()) {
            $user_id = $this->user->id;
        }
        if (!$user_id) {
            throw new Exception('Can not grant access to unknown user');
        }

        UserPermissions::get($GLOBALS['user']->id)->set($this->id, true)->store();
        self::getServer()->authorizeFinish(true, $this->getOAuthId($user_id));
    }
    
    public function revokeAccess($user_id = null)
    {
        if ($user_id === null && $this->hasUser()) {
            $user_id = $this->user->id;
        }
        if (!$user_id) {
            throw new Exception('Can not revoke access from unknown user');
        }

        $query = "DELETE oauth_server_token
                  FROM oauth_server_token
                  JOIN oauth_server_registry
                  WHERE ost_usa_id_ref = :id AND osr_consumer_key = :key AND osr_consumer_secret = :secret";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':id', $this->getOAuthId($user_id));
        $statement->bindValue(':key', $this->auth_key);
        $statement->bindValue(':secret', $this->auth_secret);
        $statement->execute();

        UserPermissions::get($GLOBALS['user']->id)->set($this->id, false)->store();
        self::getServer()->authorizeFinish(false, $this->getOAuthId($user_id));
    }
    
    private function getOAuthId($user_id)
    {
        $query = "SELECT oauth_id FROM api_oauth_user_mapping WHERE user_id = :id";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':id', $user_id);
        $statement->execute();
        $oauth_id = $statement->fetchColumn();
        
        if (!$oauth_id) {
            $query = "INSERT INTO api_oauth_user_mapping (user_id, mkdate)
                      VALUES (:id, UNIX_TIMESTAMP())";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':id', $user_id);
            $statement->execute();
            $oauth_id = DBManager::get()->lastInsertId();
        }
        
        return $oauth_id;
    }
}
