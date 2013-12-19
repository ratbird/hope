<?php
namespace API\Consumer;
use StudipAutoloader, DBManager, OAuthRequestVerifier;

StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . DIRECTORY_SEPARATOR . 'vendor/oauth-php/library/');

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo    documentation
 */
class OAuth extends Base
{
    public function detect()
    {
        return OAuthRequestVerifier::requestIsSigned();
    }

    public function authenticate()
    {
        $user_id = false;

        $parameters = (in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST')))
                    ? null
                    : $GLOBALS['_' . $_SERVER['REQUEST_METHOD']];

        $req = new OAuthRequestVerifier(null, null, $parameters);
        $result = $req->verifyExtended('access');

        // @todo
        # self::$consumer_key = $result['consumer_key'];

        $query = "SELECT user_id FROM oauth_mapping WHERE oauth_id = :oauth_id";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':oauth_id', $result['user_id']);
        $statement->execute();
        $user_id = $statement->fetchColumn();

        return $user_id;
    }
}
