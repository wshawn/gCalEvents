<?php

require_once MODX_CORE_PATH.'components/gcalevents/model/gcalevents/gcalevents.class.php';

$gca = new gCalEvents($modx, $scriptProperties);
$gca->init();

/**
*** Scripts and styles
*** &jsPath=`` &cssPath=`` define a path to a stylesheet and a javascript file
***
**/

$modx->regClientStartupScript($gca->c['jsPath']);
$modx->regClientCSS($gca->c['cssPath']);

/**
*** WARNING
*** If you set the debug property, all scriptProperties will be printed.
*** This includes your privateCookie if you supplied one in the snippet-call.
**/

if(!isset($scriptProperties['debug'])) {
	return $gca->output['string'];
} else {
	return $gca->debug();	
}