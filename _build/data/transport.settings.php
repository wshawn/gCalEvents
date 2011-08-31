<?php
$settingArray = array();

/* create a setting */
$setting= $modx->newObject('modSystemSetting');
$setting->set('key', 'gcalevents.userID');
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