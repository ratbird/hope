<?php
namespace Studip;

class WysiwygRequest
{
    //// security checks //////////////////////////////////////////////////////
 
    /**
     * TODO documentation
     */
    public static function verifyWritePermission($permission)
    {
        self::verifyPostRequest();
        \CSRFProtection::verifyUnsafeRequest();
        self::verifyStudipPermission($permission);
    }

    /**
     * Throw exception if HTTP request was not send as POST.
     * @throws AccessDeniedException if request was not send as HTTP POST.
     */
    public static function verifyPostRequest()
    {
        if (!\Request::isPost()) {
            throw new \AccessDeniedException(
                \_('Die Anfrage muss als HTTP POST gestellt werden.'));
        }
    }
    
    /** 
     * Throw exception if current user hasn't required access level. 
     * 
     * @param string $permission  Minimum required access level. 
     * @throws AccessDeniedException if user does not have permission. 
     */ 
    public static function verifyStudipPermission($permission)
    {
        if (! $GLOBALS['perm']->have_studip_perm($permission, self::seminarId())) {
            throw new \AccessDeniedException(
                \_("Es werden mindestens $permission-Zugriffsrechte benötigt.")); 
        }
    }

    /**
     * Return current seminar's identifier.
     *
     * @return mixed  Seminar identifier (string) or FALSE (boolean) if no
     *                seminar is selected.
     */
    public static function seminarId() {
        if (\Request::option('cid')) {
            return \Request::option('cid');
        }
        if ($GLOBALS['SessionSeminar']) {
            \URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
            return $GLOBALS['SessionSeminar'];
        }
        return false;
    }
}
