<?php
namespace API\Consumer;
use AuthUserMd5, DBManager, DBManagerException, PDO;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo    documentation
 */
abstract class Base extends \SimpleOrMap
{
    abstract public static function detect();

/* Concrete */

    protected static $known_types = array();
    
    public static function AddType($type, $class)
    {
        self::$known_types[$type] = $class;
    }
    
    public static function RemoveType($type)
    {
        unset(self::$known_types[$type]);
    }

    public static function find($id)
    {
        $query = "SELECT consumer_type
                  FROM api_consumers
                  WHERE consumer_id = :id";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->execute();
        $type = $statement->fetchColumn();

        if (!isset(self::$known_types[$type])) {
            throw new \Exception('Consumer #' . $id . ' is of unknown type "' . $type . '"');
        }

        return new self::$known_types[$type]($id);
    }
    
    public static function findAll()
    {
        $query = "SELECT consumer_id FROM api_consumers";
        $statement = DBManager::get()->query($query);
        $ids = $statement->fetchAll(PDO::FETCH_COLUMN);
        
        return array_map('self::find', $ids);
    }

    public static function Create($type)
    {
        if (!isset(self::$known_types[$type])) {
            throw new Exception('Consumer is of unknown type "' . $type . '"');
        }

        return new self::$known_types[$type];
    }
    
    public static function DetectConsumer($type = null)
    {
        $needles = $type === null
                 ? array_keys(self::$known_types)
                 : array($type);
        foreach ($needles as $needle) {
            if (!isset(self::$known_types)) {
                throw new Exception('Trying to detect consumer of unkown type "' . $needle . '"');
            }
            $consumer_class = self::$known_types[$needle];
            if ($consumer = $consumer_class::detect()) {
                return $consumer;
            }
        }
        return false;
    }

    protected $user = null;

    public function __construct($id = null, $user = null)
    {
        $this->db_table = 'api_consumers';

        parent::__construct($id);
        
        if ($user !== null) {
            $this->setUser($user);
        }
    }
    
    public function getPermissions()
    {
        return new API\ConsumerPermissions($this->id);
    }
    
    public function setUser($user)
    {
        if (!is_object($user)) {
            $user = AuthUserMd5::find($user);
        }
        $this->user = $user;
        return $this;
    }

    public function hasUser()
    {
        return $this->user !== null && $this->user->id && $this->user->id !== 'nobody';
    }

    public function getUser()
    {
        return $this->user;
    }
}
