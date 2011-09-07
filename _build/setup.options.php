<?php
/**
 * Build the setup options form.
 *
 * @package gcalevents
 * @subpackage build
 */
/* set some default values */
$values = array(
    'agendaID' => 'user@domain.tld',
    'privateCookie' => '',
);
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $setting = $modx->getObject('modSystemSetting',array('key' => 'gcalevents.agendaID'));
        if ($setting != null) { $values['agendaID'] = $setting->get('value'); }
        unset($setting);
 
        $setting = $modx->getObject('modSystemSetting',array('key' => 'gcalevents.privateCookie'));
        if ($setting != null) { $values['privateCookie'] = $setting->get('value'); }
        unset($setting);
    break;
    case xPDOTransport::ACTION_UNINSTALL: break;
}
 
$output = '
<label for="gcalevents-agendaID">Agenda-ID:</label>
<input type="text" name="agendaID" id="gcalevents-agendaID" width="300" value="'.$values['agendaID'].'" />
<br /><br />
 
<label for="gcalevents-privateCookie">Calendar Private Cookie:</label>
<input type="text" name="privateCookie" id="gcalevents-privateCookie" width="300" value="'.$values['privateCookie'].'" />
<br /><br />
<p>These values will only be used if snippet is set to use them and thus are not required.</p>';
 
return $output;