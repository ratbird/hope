<?php
namespace RESTAPI;
use SemType, SemClass;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 */
class StudipRoute extends RouteMap
{
    /**
     * Grundlegende Systemeinstellungen
     *
     * @get /studip/settings
     */
    public function getSettings()
    {
        $sem_types = array_map(function ($item) {
            return array(
                'name'  => $item['name'],
                'class' => $item['class'],
            );
        }, SemType::getTypes());

        $sem_classes = array_map(function ($item) {
            $item = (array)$item;
            return reset($item);
        }, SemClass::getClasses());

        return array(
            'ALLOW_CHANGE_USERNAME' => $GLOBALS['ALLOW_CHANGE_USERNAME'],
            'ALLOW_CHANGE_EMAIL'    => $GLOBALS['ALLOW_CHANGE_EMAIL'],
            'ALLOW_CHANGE_NAME'     => $GLOBALS['ALLOW_CHANGE_NAME'],
            'ALLOW_CHANGE_TITLE'    => $GLOBALS['ALLOW_CHANGE_TITLE'],
            'INST_TYPE'             => $GLOBALS['INST_TYPE'],
            'SEM_TYPE'              => $sem_types,
            'SEM_CLASS'             => $sem_classes,
            'TERMIN_TYP'            => $GLOBALS['TERMIN_TYP'],
            'PERS_TERMIN_KAT'       => $GLOBALS['PERS_TERMIN_KAT'],
            'SUPPORT_EMAIL'         => $GLOBALS['UNI_CONTACT'],
            'TITLES'                => $GLOBALS['DEFAULT_TITLE_FOR_STATUS'],
            'UNI_NAME_CLEAN'        => $GLOBALS['UNI_NAME_CLEAN'],
        );
    }

    /**
     * Farbeinstellungen
     *
     * @get /studip/colors
     */
    public function getColors() {
        // TODO: Move these definitions somewhere else (but where!?)
        return array(
            'background' => '#e1e4e9',
            'dark'       => '#34578c',
            'light'      => '#899ab9',
        );
    }
}
