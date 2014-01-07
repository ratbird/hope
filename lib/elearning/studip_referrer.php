<?php
/**
* redirect script for studip-users
* copy to Ilias webroot
*
* @author Arne Schr�der <schroeder@data-quest.de>
*
*/

/* ILIAS Version 4.0.x, 4.1.x, 4.2.x */

if(file_exists("./ilias.ini.php")){
    require_once("classes/class.ilIniFile.php");
    $ilIliasIniFile = new ilIniFile("./ilias.ini.php");
    $ilIliasIniFile->read();
    $serverSettings = $ilIliasIniFile->readGroup("server");
    if ($serverSettings["studip"] != 1)
    {
        echo 'Option "studip" in ilias.ini.php is not enabled. You need to add studip = "1" to the server section.';
        exit();
    }

    if (isset($_GET['sess_id']))
    {
        setcookie('PHPSESSID',$_GET['sess_id']);
        $_COOKIE['PHPSESSID'] = $_GET['sess_id'];
    }

    if (isset($_GET['client_id']))
    {
        setcookie('ilClientId',$_GET['client_id']);
        $_COOKIE['ilClientId'] = $_GET['client_id'];
    }

    require_once "./include/inc.header.php";

    $jump_to = 'index.php';

    // redirect to specified page
    $redirect = false;
    switch($_GET['target'])
    {
        case 'start':
            switch($_GET['type'])
            {
                case 'cat':
                    $_GET['cmd'] = 'frameset';
                    $jump_to = 'repository.php';
                break;
                case 'crs':
                    $_GET['cmd'] = 'frameset';
                    $jump_to = 'repository.php';
                break;
                case 'lm':
                    $_GET['baseClass'] = 'ilLMPresentationGUI';
                    $jump_to = 'ilias.php';
                break;
                case 'tst':
                    $_GET['cmd'] = 'infoScreen';
                    $_GET['baseClass'] = 'ilObjTestGUI';
                    $jump_to = 'ilias.php';
                break;
                case 'sahs':
                    $jump_to = 'ilias.php?baseClass=ilSAHSPresentationGUI&ref_id='.$_GET['ref_id'];
                    $redirect = true;
                break;
                case 'htlm':
                    $_GET['baseClass'] = 'ilHTLMPresentationGUI';
                    $jump_to = 'ilias.php';
                    break;
                case 'glo':
                    $_GET['baseClass'] = 'ilGlossaryPresentationGUI';
                    $jump_to = 'ilias.php';
                break;
                case 'svy':
                    $_GET['baseClass'] = 'ilObjSurveyGUI';
                    $_GET['cmd'] = 'infoScreen';
                    $jump_to = 'ilias.php';
                break;
                case 'exc':
                    $_GET['baseClass'] = 'ilExerciseHandlerGUI';
                    $_GET['cmd'] = 'infoScreen';
                    $jump_to = 'ilias.php';
                break;
                case 'dbk':
                    $_GET['baseClass'] = 'ilLMPresentationGUI';
                    $jump_to = 'ilias.php';
                break;
                case 'webr':
                    $_GET['baseClass'] = 'ilLinkResourceHandlerGUI';
                    $_GET['cmd'] = 'calldirectlink';
                    $jump_to = 'ilias.php';
                break;
                default:
                    unset($jump_to);
            }
        break;
        case 'new':
            $_POST['new_type'] =
            $_GET['new_type'] =
            $_REQUEST['new_type'] = $_GET['type'];
            $_POST['cmd']['create'] = '1';
            $_GET['cmd'] = 'post';
            $_GET[ilCtrl::IL_RTOKEN_NAME] = $ilCtrl->getRequestToken();
            $jump_to = 'repository.php';
        break;
        case 'edit':
            switch($_GET['type'])
                {
                    case 'cat':
                        $_GET['cmd'] = 'edit';
                        $jump_to = 'repository.php';
                    break;
                    case 'crs':
                        $_GET['cmd'] = 'edit';
                        $jump_to = 'repository.php';
                    break;
                    case 'lm':
                        $_GET['baseClass'] = 'ilLMEditorGUI';
                        $jump_to = 'ilias.php';
                    break;
                    case 'tst':
                        $_GET['cmd'] = '';
                        $_GET['baseClass'] = 'ilObjTestGUI';
                        $jump_to = 'ilias.php';
                    break;
                    case 'sahs':
                        $_GET['baseClass'] = 'ilSAHSEditGUI';
                        $jump_to = 'ilias.php';
                    break;
                    case 'htlm':
                        $_GET['baseClass'] = 'ilHTLMEditorGUI';
                        $jump_to = 'ilias.php';
                    break;
                    case 'glo':
                        $_GET['baseClass'] = 'ilGlossaryEditorGUI';
                        $jump_to = 'ilias.php';
                    break;
                    case 'svy':
                        $_GET['baseClass'] = 'ilObjSurveyGUI';
                        $_GET['cmd'] = 'properties';
                        $jump_to = 'ilias.php';
                    break;
                    case 'exc':
                        $_GET['baseClass'] = 'ilExerciseHandlerGUI';
                        $_GET['cmd'] = 'edit';
                        $jump_to = 'ilias.php';
                    break;
                    case 'dbk':
                        $_GET['baseClass'] = 'ilLMEditorGUI';
                        $jump_to = 'ilias.php';
                    break;
                    case 'webr':
                        $_GET['baseClass'] = 'ilLinkResourceHandlerGUI';
                        $_GET['cmd'] = 'editLinks';
                        $jump_to = 'ilias.php';
                    break;
                    default:
                        unset($jump_to);
                }
        break;
        case 'login':
        break;
        default:
        unset($jump_to);
    }
    if ($redirect)
    {
        header("Location: ".$jump_to);
        exit();
    }
    elseif(isset($jump_to))
    {
        include($jump_to);
    }
}
?>