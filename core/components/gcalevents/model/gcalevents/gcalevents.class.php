<?php

/********
*
*	(c) Eelke van den Bos 2011
*		package: gcalevents
*
*/

class gCalEvents {

/* objects */
public $modx;
public $eventCacheManager;

/* arrays */
public $defaults = array();
public $config = array();
public $bufferParsed = array();
public $cacheObj = array();
public $output = array();
public $scriptProperties = array();
protected $_cacheOptions = array();

/* strings */
public $url = '';
public $buffer = '';

	function __construct(&$modx, $options) {
		
		/* modx object as property and defaults */
		$this->modx =& $modx;
		$this->defaults = array(
			/* feed defaults */
				'orderBy' => 'starttime',
				'sortOrder' => 'a',
				'singleEvents' => 'true',
				'includeAttendees' => 0,
				'useProxy' => 0,	
				'baseUrl' => 'https://www.google.com/calendar/feeds/{{+agendaID}}/private-{{+privateCookie}}/full?alt=jsonc', 
				'baseUrlPublic' => 'https://www.google.com/calendar/feeds/{{+agendaID}}/public/full?alt=jsonc',
				'baseUrlCustom' => '',
				'startDate' => date("Y-m-d\TH:i:s"), 
				'endDate' => date("Y-m-d\TH:i:s", (mktime()+1814400)),
				'currentWeek' => date('W'),
				'currentYear' => date('Y'),
			/* template defaults */
				'eventTpl' => 'gcaleventsEvent',
				'wrapTpl' => 'gcaleventsWrapper',
				'weekEventTpl' => 'gcaleventsWeekEvent',
				'weekEventAlldayTpl' => 'gcalEventsWeekAlldayEvent',
				'weekDayTpl' => 'gcaleventsWeekDay',
				'weekDayHeaderTpl' => 'gcaleventsWeekDayHeader',
				'weekWrapperTpl' => 'gcaleventsWeekWrapper',
				'weekScaleTpl' => 'gcaleventsWeekTimescale',
				'weekBlankscaleTpl' => 'gcaleventsWeekTimescaleBlank',
				'weekScaleBorder' => 1,
				'weekEventPadding' => 2,
				'hourHeight' => 22,
				'includeJS' => 1,
				'includeCSS' => 1,
				'includeHeader' => 1,
				'jsPath' => './assets/components/gcalevents/js/gcalevents.default.js',
				'cssPath' => './assets/components/gcalevents/css/gcalevents.default.css',
			/* general defaults */
				'decay' => 3600,
				'limit' => 25,
				'useSettings' => 0,
				'cached' => 0,
				'cacheSlot' => 0,
				'outputType' => 'string',
				'outputMode' => 'agenda'
		);
		
		$this->scriptProperties = is_array($options) ? $options:array();
		$this->config = array_merge($this->defaults, $options);
		$this->c =& $this->config;
		
		/* load lexicons */
		$this->modx->getService('lexicon','modLexicon');
		$this->modx->lexicon->load('gcalevents:default');
		
		/* check for settings */
		if($this->c['useSettings'] == 1) {
			$this->settingsToConfig();
		}
		
		/* set datetimecriteria */
		$this->setDatetimeCriteria();
	}
	
	function init() {
		
		/* check for cacheflag */
		if($this->c['cached'] == 1) {

			$this->cacheInitCheck();
			
			/* cache still valid */
			if($this->cacheObj !== null) {
				
				if($this->c['outputMode'] == 'week') {
					$this->getWeekviewCache();
				} elseif($this->c['outputMode'] == 'calendar') {
					$this->getCalendarviewCache();
				}
				
				if($this->cacheResult !== false) {
					/* cache valid, wrap up */
					return $this->wrapUp(true);
				}
				
			} else {
				
				/* cache outdated */
				$this->cacheResult = false;
			}
		}
		
		if($this->cacheResult === false || $this->c['cached'] == 0) {
			
			/* curl request */
			if($this->doCurl() === true) {
				
				/* json decode */
				if($this->jDecode() === true) {
					
					/* check feed */
					if($this->feedCheck() === true) {
						
						/* parsing and wrap up */
						$this->parseEvents();
						
						if($this->wrapUp(false) === true) {
							
							/* success! */
							return true;
						}
					} else {
						
						/* error found in feed */
						$this->parseFeedError();
					}
				} else {
					
					/* error decoding json */
					$this->errors[] = 'Error in gCalEvents::jDecode(), json_decode did not return array.';
				}
			} else {
				
				/* error curl request */
				$this->errors[] = 'Error in gCalEvents::doCurl(), curl_exec failed.';
			}
		}
		
		return false;	
	}

	function doCurl() {
		
		/* prepare the url */
		$this->prepareUrl();
	
		/* curl init + opt */
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('GData-Version: 2'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		
		/* proxy options */
		if($this->c['useProxy'] == 1) {
		
			/* check if credentials are needed */
			$proxyUser = $this->modx->getOption('proxy_user', null, false);
			$proxyPass = $this->modx->getOption('proxy_password', null, '');
			if($proxyUser !== false) {
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUser.':'.$proxyPass);
			}
			
			curl_setopt($ch, CURLOPT_PROXY, $this->modx->getOption('proxy_host', null, ''));
			curl_setopt($ch, CURLOPT_PROXYAUTH, $this->modx->getOption('proxy_auth_type', null, CURLAUTH_BASIC));
			curl_setopt($ch, CURLOPT_PROXYPORT, $this->modx->getOption('proxy_port', null, 8080));
		}
		
		/* fill buffer */
		$this->buffer = curl_exec($ch);
		
		/* error handling */
		if(curl_error($ch)) {
			$this->errors[] = 'Error in doCurl(): '.curl_error($ch);
		}
		
		return $this->buffer != '' && count($this->errors) == 0 ? true:false;
	}

	function prepareUrl() {
		
		/* check url in config */
		if(!isset($this->c['staticUrl'])) {
			
			/* baseUrl selection */
			if(!isset($this->c['privateCookie'])) {
				$this->url = $this->c['baseUrlPublic'];
			} else {
				
				/* baseUrl selection step 2 */
				if(!empty($this->c['baseUrlCustom'])) {
					$this->url = $this->c['baseUrlCustom'];
				} else {
					$this->url = $this->c['baseUrl'];
				}
				
				/* privateCookie */
				$this->url = str_replace('{{+privateCookie}}', $this->c['privateCookie'], $this->url);
			}
			
			/* agendaID */
			$this->url = str_replace('{{+agendaID}}', $this->c['agendaID'], $this->url);
		
			/* startdate, enddate & sorting */
			$this->url .= '&start-min='.$this->c['startDate'].'&start-max='.$this->c['endDate'];
			$this->url .= '&orderby='.$this->c['orderBy'].'&sortorder='.$this->c['sortOrder'];
			
			/* output as single events */
			$this->url .= '&singleevents='.$this->c['singleEvents'];
			
			/* fulltext search */
			if($this->c['query']) {
				$this->url .= '&q='.$this->c['query'];
			}
		} else {
			$this->url = $this->c['staticUrl'];
		}
	}
	
	function parseEvents() {
		
		/* iterate events */
		for($i = 0;$i < count($this->bufferParsed['data']['items']);$i++) {
			$e[$i] = $this->bufferParsed['data']['items'][$i];
			$tmpa = array();
			
			/* creator */
			$tmpa['creator.displayName'] = $e[$i]['creator'][0]['displayName'];
			$tmpa['creator.email'] = $e[$i]['creator'][0]['email'];
							
			/* attendees */
			if($this->c['includeAttendees'] == 1) {
				foreach($e[$i]['attendees'] as $k => $a) {
					$ia['rel'][$k] = $e[$i]['rel'];
					$ia['displayName'][$k] = $e[$i]['displayName'];
					$ia['email'][$k] = $e[$i]['email'];
				}
				$tmpa['attendees.rel'] = isset($ia['rel']) ? implode(', ', $ia['rel']):'';
				$tmpa['attendees.displayName'] = isset($ia['displayName']) ? implode(', ', $ia['displayName']):'';
				$tmpa['attendees.email'] = isset($ia['email']) ? implode(', ', $ia['email']):'';
			}
			
			/* when */
			$tmpa['when.start'] = strtotime($e[$i]['when'][0]['start']);
			$tmpa['when.end'] = strtotime($e[$i]['when'][0]['end']);
			$tmpa['when.duration'] = $tmpa['when.end'] - $tmpa['when.start'];
			
			/* unset nested arrays */
			unset($e[$i]['attendees']);
			unset($e[$i]['creator']);
			unset($e[$i]['when']);
			
			$tmpa = array_merge($e[$i], $tmpa);
			
			/* proceed to chunk parsing */
			if($i < $this->c['limit']) {
				
				if($this->c['outputType'] == 'string' || $this->c['outputType'] == 'default') {
					
					/* advanced sorting if non-agenda */
					$this->handleOutputMode($tmpa);
				} 
				
				if($this->c['outputType'] == 'array' || $this->c['outputType'] == 'default') {
					$this->output['array'][] = $this->bufferParsed['data']['items'][$i];
					$this->sortEvent($tmpa);
				}
			}
		}
		
		$this->setAgendaMeta();
		unset($tmpa);
	}
	
	function handleOutputMode($event) {
		switch($this->c['outputMode']) {
			case 'agenda':
				$this->output['string']['agenda'] .= $this->parseEventChunk($event);
			break;
			default:
				$this->sortEvent($event);
		}
	}
	
	function sortEvent($event) {
		/* shorts */
		$ws = $event['when.start']; 
		$we = $event['when.end']; 
		$dd = ($we-$ws) % 86400; 
		
		/* defaults */
		$event['top'] = $this->hoursToPixels($ws);

		if($dd == 0 && $this->Hour($ws) == 0 && $this->Hour($we) == 0) {
			$event['height'] = $this->c['hourHeight']-$this->c['weekEventPadding'];
			$event['allday'] = 1;
			while($ws < $we) {
				$this->setSortedArray($ws, $event);
				$ws += 86400;
			}
		} elseif(date('d', $ws) != date('d', $we-1)) {
			$event['when.duration'] = (24-($this->Hour($ws)+(date('i', $ws)/60)))*3600;
			while($ws < $we) {
				$this->setSortedArray($ws, $event);
				$ws += $event['when.duration'];
				$event['top'] = $this->hoursToPixels($ws);
				$event['when.duration'] = ($we - $ws) < 86400 ? ($we - $ws):86400;
			}
		} else {
			$this->setSortedArray($ws, $event);
		}
			
	}
	
	function parseMultipass() {
		if($this->c['outputMode'] == 'week') {
			
			$this->parseWeekView();
		} elseif($this->c['outputMode'] == 'calendar') {
		
			$this->parseCalendarView();	
		} else {
			
			$this->errors[] = 'Error in gCalEvents::parseMultipass(), unable to determine outputMode.';	
		}
	}
	
	function parseCalendarView() {
		//TODO	
	}
	
	function parseWeekView() {
		
		/* define scale properties */
		$scaleProps = array('height' => $this->c['hourHeight']-$this->c['weekScaleBorder']);
		/* create weekday list 1 - 7 */
		$weekDays = array(1, 2, 3, 4, 5 ,6 ,7);
		/* determine first week in feed */
		$targetWeek = $this->c['currentWeek'];
		/* create container */
		$secondPass = array(
			'wrapper' => '',
			'includeHeader' => $this->c['includeHeader'], 
			'scale' => $this->modx->getChunk($this->c['weekScaleTpl'], $scaleProps), 
			'startDate' => strtotime($this->c['startDate']),
			'endDate' => strtotime($this->c['endDate'])-86400,
			'curWeek' => $targetWeek,
			'nextWeek' => $targetWeek >= 52 ? 1:$targetWeek+1,
			'prevWeek' => $targetWeek > 1 ? $targetWeek-1:52, 
			'curYear' => $this->c['currentYear'],
			'nextYear' => $targetWeek == 52 ? $this->c['currentYear']+1:$this->c['currentYear'],
			'prevYear' => $targetWeek == 1 ? $this->c['currentYear']-1:$this->c['currentYear']
		);
		
		foreach($weekDays as $w) {
			$daystamp = strtotime($this->c['currentYear'].'W'.$this->c['currentWeek'].$w);
			$secondPass['headers'] .= $this->modx->getChunk($this->c['weekDayHeaderTpl'], array('day' => $daystamp));
			
			/* gather items for this day */
			$dayitems = $this->output['sortedArray'][$targetWeek][$w];
			
			/* create container */
			$firstPass['normal'] = array('events' => $this->modx->getChunk($this->c['weekBlankscaleTpl'], $scaleProps), 'odd' => ($w % 2));
			$firstPass['allday'] = array('events' => '');
			
			/* iterate trough items of this week */
			foreach($dayitems as $v) {
				
				if($v['allday'] == 1) {
					$firstPass['allday']['events'] .= $this->modx->getChunk($this->c['weekEventAlldayTpl'], $v);	
				} else {
					$firstPass['normal']['events'] .= $this->modx->getChunk($this->c['weekEventTpl'], $v);
				}
			}
			
			/* fill week container */
			$secondPass['wrapper'] .= $this->modx->getChunk($this->c['weekDayTpl'], $firstPass['normal']);
			$secondPass['alldays'] .= $this->modx->getChunk($this->c['weekDayTpl'], $firstPass['allday']);
		}
		
		/* fill wrapper */
		$this->output['string']['week'][$this->c['currentYear'].$targetWeek] = $this->modx->getChunk($this->c['weekWrapperTpl'], $secondPass);
	}
	
	function wrapUp($cacheFlag) {
		
		if($cacheFlag === true) {
			
			/* wrap up cached */
			$this->output = $this->cacheObj;
		} else {
			
			/* wrap up uncached */
			if($this->c['outputType'] != 'array') {
				
				/* do some iterations */				
				if($this->c['outputMode'] != 'agenda') {
					
					/* multipass needed for advanced view */
					$this->parseMultipass();
				} else {
					
					/* single pass is enough */
					$this->parseWrapperChunk();
				}
			}
			
			/* modify cache when needed */
			if($this->c['cached'] == 1) {
				$this->modifyCache();
			}
		}
		
		/* free some memory */
		$this->freeMem();		
		return true;
	}
	
	function setDatetimeCriteria() {
		
		if($this->c['outputMode'] == 'week') {
			
			/* calculate first day of the week */
			$fDoW = strtotime($this->c['currentYear'].'W'.$this->c['currentWeek'].'1');
			/* startdate is fDoW, enddate is + 1 week */
			$startDate = $fDoW; $endDate = strtotime('+1 Week', $fDoW);

		} elseif($this->c['outputMode'] == 'calendar') {
			
			/* first day of month */
			$fDoM = mktime(0,0,0,$this->c['currentMonth'],1,$this->c['currentYear']);
			/* what day of the week is the first of the month */
			$N = $this->DoW($fDoM);
			/* correct to monday if needed */
			$fMoM = $N == 1 ? $fDoM:strtotime('-'.($N-1).'day', $fDoM);
			/* startdate is fMoM, enddate is + 1 Month */
			$startDate = $fMoM; $endDate = strtotime('+1 Month', $fMoM);
		}
		
		$this->c['startDate'] = !empty($startDate) ? date("Y-m-d\TH:i:s", $startDate):$this->c['startDate'];
		$this->c['endDate'] = !empty($endDate) ? date("Y-m-d\TH:i:s",$endDate):$this->c['endDate'];
	}
	
	function setAgendaMeta() {
		/* copy parsed buffer and unset the items */
		$a = $this->bufferParsed['data']; unset($a['items']);
		$this->agendaMeta = $a;
		
		/* if needed, push to array output */
		if($this->c['outputType'] != 'string') {
			$this->output['array']['meta'] = $this->agendaMeta;	
		}
	}
	
	function jDecode() {
		$this->bufferParsed = json_decode($this->buffer, true);
		return is_array($this->bufferParsed) ? true:false;
	}
	
	function feedCheck() {
		return isset($this->bufferParsed['error']) ? false:true;
	}
	
	function parseFeedError() {
		$this->errors[] = 'Error in gCalEvents::feedCheck(), feed returned error: '.$this->bufferParsed['error']['code'];
		$this->errors[] = 'Error msg from feed: '.$this->bufferParsed['error']['message'];	
	}
	
	/* PARSING CHUNKS */
	
	function parseEventChunk($event = array()) {
		return $this->modx->getChunk($this->c['eventTpl'], $event);
	}
	
	function parseWrapperChunk() {
		$tmp = array_merge($this->agendaMeta, array('events' => $this->output['string'][$this->c['outputMode']]));
		$this->output['string'][$this->c['outputMode']] = $this->modx->getChunk($this->c['wrapTpl'], $tmp);
		unset($tmp);
	}
	
	/* CACHING */
	
	function initCache() {
		$this->_cacheOptions = array(
			'cache_key' => $this->modx->getOption('cache_resource_key',null, 'resource'),
			'cache_handler' => $this->modx->getOption('cache_resource_handler', null, 'xPDOFileCache'),
			'cache_expires' => (int)$this->c['decay'],
		);
		$this->eventCacheManager =& $this->modx->cacheManager;
		$this->cacheObj = $this->eventCacheManager->get($this->modx->resource->getCacheKey().'/'.md5('events'.$this->c['cacheSlot']), $this->_cacheOptions);
		$this->cacheInit = true;
	}
	
	function cacheInitCheck() {
		/* if not initiated, do so */
		if($this->cacheInit !== true) {
			$this->initCache();
		}
	}
	
	function cleanCache() {
		unset($this->cacheObj['sortedArray']);
	}
	
	function settingsToConfig() {
		/* agendaID from settings */
		$this->c['agendaID']	= $this->modx->getOption('gcalevents.agendaID', null, false);
		
		/* privateCookie from settings if not empty */
		if($this->modx->getOption('gcalevents.privateCookie', null, '') != '') {
			
			$this->c['privateCookie'] = $this->modx->getOption('gcalevents.privateCookie');
		}
	}

	function modifyCache() {
		$this->cacheObj = $this->output;
		$this->cleanCache();
		$cacheKey = $this->modx->resource->getCacheKey().'/'.md5('events'.$this->c['cacheSlot']);
		$this->eventCacheManager->set($cacheKey, $this->cacheObj, $this->c['decay'], $this->_cacheOptions);
	}
	
	function getWeekviewCache() {
		$this->cacheInitCheck();
		if(!isset($this->cacheObj['string']['week'][$this->c['currentYear'].$this->c['currentWeek']])) {
			$this->output = $this->cacheObj;
			$this->cacheResult = false;	
		}
	}
	
	function getCalendarviewCache() {
		$this->cacheInitCheck();
		if(!isset($this->cacheObj['string']['month'][$this->c['currentYear'].$this->c['currentMonth']])) {
			$this->cacheResult = false;	
		}	
	}
	
	/* SORTING */

	function setSortedArray($ws, $event) {
		$height = $this->convertComma(($event['when.duration'] / 3600*$this->c['hourHeight'])-$this->c['weekEventPadding']);
		$event['height'] = isset($event['height']) ? $event['height']:$height;
		$this->output['sortedArray'][$this->WeekN($ws)][$this->DoW($ws)][$event['id']] = $event;	
	}

	function DoW($timestamp) {
		/* starting monday as 1, sunday as 7 */
		return date("N", $timestamp);	
	}
	
	function DoM($timestamp) {
		/* 0 - 31*/
		return date("d", $timestamp);	
	}
	
	function DoY($timestamp) {
		/* 0 - 365 */
		return date("z", $timestamp);	
	}
	
	function WeekN($timestamp) {
		/* 0 - 52 */
		return date('W', $timestamp);	
	}
	
	function Hour($timestamp) {
		/* 00 - 23 */
		return date('H', $timestamp);	
	}
	
	function Minute($timestamp) {
		/* 00 - 60 */
		return date('i', $timestamp);	
	}
	
	function hoursToPixels($timestamp, $includeMinutes = true) {
		/* get hour */
		$output = $this->Hour($timestamp);
		/* sum with minutes to hour */
		$output += $includeMinutes === true ? $this->Minute($timestamp)/60:0;
		/* multiply with pixels per hour */
		$output = $output*$this->c['hourHeight'];
		/* replace the , for css compatibility */
		return str_replace(',','.',(string)$output).'px';	
	}
	
	/* UTILITIES */
	
	function convertComma($input, $replacement = '.', $precision = 1) {
		$tmpc = explode(',',$input);
		$tmpc[1] = substr($tmpc[1], 0, $precision);
		if($tmpc[1] == '') {
			unset($tmpc[1]);	
		}
		return implode($replacement, $tmpc);
	}
	
	function debug() {
		return '<pre>Errors: '.implode("\n\n", $this->errors)."\n\n Script Properties:".json_encode($this->scriptProperties).'</pre>';	
	}
	
	function freeMem() {
		unset($this->defaults);	
		unset($this->buffer);
		unset($this->bufferParsed);
		unset($this->url);
	}
}