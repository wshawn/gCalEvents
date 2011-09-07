<?php

require_once MODX_CORE_PATH.'components/gcalevents/model/gcalevents/gcalevents.class.php';

$scriptProperties['outputMode'] = 'calendar';
$scriptProperties['currentMonth'] = is_numeric($_GET['m']) ? $_GET['m']:date('m');
$scriptProperties['currentYear'] = is_numeric($_GET['y']) ? $_GET['y']:date('Y');
$gca = new gCalEvents($modx, $scriptProperties);
$gca->init();

/**
*** Scripts and styles
*** &jsPath=`` &cssPath=`` define a path to a stylesheet and a javascript file
***
**/

if($gca->c['includeJS'] == 1) {
	$modx->regClientStartupScript($gca->c['jsPath']);
}
if($gca->c['includeCSS'] == 1) {
	$modx->regClientCSS($gca->c['cssPath']);
}

/**
*** WARNING
*** If you set the debug property, all scriptProperties will be printed.
*** This includes your privateCookie if you supplied one in the snippet-call.
**/

if(!isset($gca->c['debug'])) {
	return $gca->output[$gca->c['outputType']][$gca->c['outputMode']][$gca->c['currentYear'].$gca->c['currentMonth']];
} else {
	return $gca->debug();	
}