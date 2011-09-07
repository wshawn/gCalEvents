<?php
$settingArray = array();

/* create a setting */
$setting= $modx->newObject('modSystemSetting');
$setting->set('key', 'gcalevents.agendaID');
$setting->set('value', '');
$setting->set('xtype', 'textfield');
$setting->set('namespace','gcalevents');

$settingArray[] = $setting;

/* create another setting */
$setting= $modx->newObject('modSystemSetting');
$setting->set('key', 'gcalevents.privateCookie');
$setting->set('value', '');
$setting->set('xtype', 'textfield');
$setting->set('namespace','gcalevents');

$settingArray[] = $setting;

return $settingArray;