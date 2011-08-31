<?php
/**
 * Resolves setup-options settings by setting email options.
 *
 * @package gcalevents
 * @subpackage build
 */
$success= false;
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        /* emailsTo */
        $setting = $object->xpdo->getObject('modSystemSetting',array('key' => 'gcalevents.userID'));
        if ($setting != null) {
            $setting->set('value',$options['userID']);
            $setting->save();
        } else {
            $object->xpdo->log(xPDO::LOG_LEVEL_ERROR,'[gCalEvents] userID setting could not be found, so the setting could not be changed.');
        }
 
        /* emailsFrom */
        $setting = $object->xpdo->getObject('modSystemSetting',array('key' => 'gcalevents.privateCookie'));
        if ($setting != null) {
            $setting->set('value',$options['privateCookie']);
            $setting->save();
        } else {
            $object->xpdo->log(xPDO::LOG_LEVEL_ERROR,'[gCalEvents] privateCookie setting could not be found, so the setting could not be changed.');
        }
 
        $success= true;
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        $success= true;
        break;
}
return $success;