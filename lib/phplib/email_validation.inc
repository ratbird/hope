<?php
/*
 * email_validation.php
 *
 * @(#) $Header$
 *
 */

class email_validation_class
{
    var $username_regular_expression=null;
    var $password_regular_expression="/^([[:print:]]{4,72})\$/";
    var $name_regular_expression='/^[-_ a-zA-Z�-�\']+$/';
    var $telefon_regular_expression="/^([0-9 \(\)\\/+_-]*)\$/";
    var $email_regular_expression='/^([-+.0-9=?A-Z_a-z{|}~])+@([-.0-9=?A-Z_a-z{|}~])+\.[a-zA-Z]{2,6}$/i';
    var $email_regular_expression_restricted_part = "/^([a-zA-Z0-9_]|\\-|\\.)+";
    var $timeout=10;
    var $data_timeout=10;
    var $localhost="studip.de";
    var $localuser="info";
    var $debug=0;
    var $html_debug=0;
    var $exclude_address="";
    var $getmxrr="GetMXRR";

    var $next_token="";

    function __construct()
    {
        $this->username_regular_expression = $GLOBALS['USERNAME_REGULAR_EXPRESSION'];
    }

    Function Tokenize($string,$separator="")
    {
        if(!strcmp($separator,""))
        {
            $separator=$string;
            $string=$this->next_token;
        }
        for($character=0;$character<strlen($separator);$character++)
        {
            if(GetType($position=strpos($string,$separator[$character]))=="integer")
                $found=(IsSet($found) ? min($found,$position) : $position);
        }
        if(IsSet($found))
        {
            $this->next_token=substr($string,$found+1);
            return(substr($string,0,$found));
        }
        else
        {
            $this->next_token="";
            return($string);
        }
    }

    Function OutputDebug($message)
    {
        $message.="\n";
        if($this->html_debug)
            $message=str_replace("\n","<br>\n",HtmlEntities($message));
        echo $message;
        flush();
    }

    Function GetLine($connection)
    {
        for($line="";;)
        {
            if(feof($connection))
                return(0);
            $line.=fgets($connection,100);
            $length=strlen($line);
            if($length>=2
            && substr($line,$length-2,2)=="\r\n")
            {
                $line=substr($line,0,$length-2);
                if($this->debug)
                    $this->OutputDebug("S $line");
                return($line);
            }
        }
    }

    Function PutLine($connection,$line)
    {
        if($this->debug)
            $this->OutputDebug("C $line");
        return(fputs($connection,"$line\r\n"));
    }

    Function ValidateUsername($username)
    {
        return(strtolower($username) != 'studip' && preg_match($this->username_regular_expression,$username)!=0);
    }

    Function ValidatePassword($password)
    {
        return(preg_match($this->password_regular_expression,$password)!=0);
    }

    Function ValidateName($name)
    {
        return(preg_match($this->name_regular_expression,$name)!=0);
    }

    Function ValidateTelefon($telefon)
    {
        return(preg_match($this->telefon_regular_expression,$telefon)!=0);
    }

    Function ValidateEmailAddress($email, $restriction = '')
    {
        if ($restriction) {
            $restrictions = array();
            $restriction_split = explode(',', $restriction);
            foreach ($restriction_split as $restriction_part) {
                $restrictions[] = '@' . trim($restriction_part);
            }
            $restriction = implode('|', $restrictions);
            $restriction = str_replace(array('.', '-'), array('\.', '\-'), $restriction);
            $email_regular_expression_restricted = $this->email_regular_expression_restricted_part
            . '(' . $restriction . ')$/i';
            return (preg_match($email_regular_expression_restricted, $email) != 0);
        }
        return(preg_match($this->email_regular_expression,$email)!=0);
    }

    Function ValidateEmailHost($email){
        return $this->ValidateEmailHostIntern($email, $hosts);
    }

    Function ValidateEmailHostIntern($email,&$hosts)
    {
        if (!$GLOBALS['MAIL_VALIDATE_HOST']){
            return true;
        }
        if(!$this->ValidateEmailAddress($email))
            return(0);
        $user=$this->Tokenize($email,"@");
        $domain=$this->Tokenize("");
        $hosts=$weights=array();
        $getmxrr=$this->getmxrr;
        if(function_exists($getmxrr)
        && $getmxrr($domain,$hosts,$weights))
        {
            $mxhosts=array();
            for($host=0;$host<count($hosts);$host++)
                $mxhosts[$weights[$host]]=$hosts[$host];
            KSort($mxhosts);
            for(Reset($mxhosts),$host=0;$host<count($mxhosts);Next($mxhosts),$host++)
                $hosts[$host]=$mxhosts[Key($mxhosts)];
        }
        else
        {
            if(strcmp($ip=@gethostbyname($domain),$domain)
            && (strlen($this->exclude_address)==0
            || strcmp(@gethostbyname($this->exclude_address),$ip)))
                $hosts[]=$domain;
        }
        return(count($hosts)!=0);
    }

    Function VerifyResultLines($connection,$code)
    {
        while(($line=$this->GetLine($connection)))
        {
            if(!strcmp($this->Tokenize($line," "),$code))
                return(1);
            if(strcmp($this->Tokenize($line,"-"),$code))
                return(0);
        }
        return(-1);
    }

    Function ValidateEmailBox($email)
    {
        if (!$GLOBALS['MAIL_VALIDATE_BOX']){
            return true;
        }
        if(!$this->ValidateEmailHostIntern($email,$hosts))
            return(0);
        if(!strcmp($localhost=$this->localhost,"")
        && !strcmp($localhost=getenv("SERVER_NAME"),"")
        && !strcmp($localhost=getenv("HOST"),""))
           $localhost="localhost";
        if(!strcmp($localuser=$this->localuser,"")
        && !strcmp($localuser=getenv("USERNAME"),"")
        && !strcmp($localuser=getenv("USER"),""))
           $localuser="root";
        for($host=0;$host<count($hosts);$host++)
        {
            $domain=$hosts[$host];
            if(preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$domain))
                $ip=$domain;
            else
            {
                if($this->debug)
                    $this->OutputDebug("Resolving host name \"".$hosts[$host]."\"...");
                if(!strcmp($ip=@gethostbyname($domain),$domain))
                {
                    if($this->debug)
                        $this->OutputDebug("Could not resolve host name \"".$hosts[$host]."\".");
                    continue;
                }
            }
            if(strlen($this->exclude_address)
            && !strcmp(@gethostbyname($this->exclude_address),$ip))
            {
                if($this->debug)
                    $this->OutputDebug("Host address of \"".$hosts[$host]."\" is the exclude address");
                continue;
            }
            if($this->debug)
                $this->OutputDebug("Connecting to host address \"".$ip."\"...");
            if(($connection=($this->timeout ? @fsockopen($ip,25,$errno,$error,$this->timeout) : @fsockopen($ip,25))))
            {
                $timeout=($this->data_timeout ? $this->data_timeout : $this->timeout);
                if($timeout
                && function_exists("socket_set_timeout"))
                    socket_set_timeout($connection,$timeout,0);
                if($this->debug)
                    $this->OutputDebug("Connected.");
                if($this->VerifyResultLines($connection,"220")>0
                && $this->PutLine($connection,"HELO $localhost")
                && $this->VerifyResultLines($connection,"250")>0
                && $this->PutLine($connection,"MAIL FROM: <$localuser@$localhost>")
                && $this->VerifyResultLines($connection,"250")>0
                && $this->PutLine($connection,"RCPT TO: <$email>")
                && ($result=$this->VerifyResultLines($connection,"250"))>=0)
                {
                    if($result
                    && $this->PutLine($connection,"DATA"))
                        $result=($this->VerifyResultLines($connection,"354")!=0);
                    if($this->debug)
                        $this->OutputDebug("This host states that the address is ".($result ? "" : "not ")."valid.");
                    fclose($connection);
                    if($this->debug)
                        $this->OutputDebug("Disconnected.");
                    return($result);
                }
                if($this->debug)
                    $this->OutputDebug("Unable to validate the address with this host.");
                fclose($connection);
                if($this->debug)
                    $this->OutputDebug("Disconnected.");
            }
            else
            {
                if($this->debug)
                    $this->OutputDebug("Failed.");
            }
        }
        return(-1);
    }
};

?>
