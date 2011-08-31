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
			'baseUrl' => 'https://www.google.com/calendar/feeds/{{+userID}}/{{+privateCookie}}/full?alt=jsonc', 
			'baseUrlPublic' => 'https://www.google.com/calendar/feeds/{{+userID}}/public/full?alt=jsonc',
			'baseUrlCustom' => '',
			'startDate' => date("Y-m-d\TH:i:s"), 
			'endDate' => date("Y-m-d\TH:i:s", (mktime()+1814400)),
			'eventTpl' => 'gcaleventsEventDefault',
			'wrapTpl' => 'gcaleventsWrapperDefault',
			'orderBy' => 'starttime',
			'sortOrder' => 'a',
			'decay' => 3600,
			'limit' => 10,
			'useSettings' => 0,
			'includeAttendees' => 0,
			'useProxy' => 0,
			'cached' => 0,
			'cacheSlot' => 0,
			'output' => 'string',
			'singleEvents' => 'true',
			'jsPath' => './assets/components/gcalevents/js/gcalevents.default.js',
			'cssPath' => './assets/components/gcalevents/css/gcalevents.default.css'
		);
		
		$this->scriptProperties = is_array($options) ? $options:array();
		$this->config = array_merge($this->defaults, $options);
		$this->c =& $this->config;
		$this->modx->getService('lexicon','modLexicon');
		$this->modx->lexicon->load('gcalevents:default');
		
		/* check for settings */
		if($this->c['useSettings'] == 1) {
			
			/* userID from settings */
			$this->c['userID']	= $this->modx->getOption('gcalevents.userID', null, false);
			
			/* privateCookie from settings if not empty */
			if($this->modx->getOption('gcalevents.privateCookie', null, '') != '') {
				
				$this->c['privateCookie'] = $this->modx->getOption('gcalevents.privateCookie');
			}
		}
	}
	
	function init() {
		
		/* check for cacheflag */
		if($this->c['cached'] == 1) {

			$this->initCache();
			
			/* cache still valid */
			if($this->cacheObj !== null) {
				
				/* cache valid, wrap up */
				return $this->wrapUp(true);
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
						return $this->wrapUp(false);
					} else {
						
						/* error found in feed */
						$this->errors[] = 'Error in gCalEvents::feedCheck(), feed contains errors.';	
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
			
			/* userID */
			$this->url = str_replace('{{+userID}}', $this->c['userID'], $this->url);
		
			/* startdate, enddate & sorting */
			$this->url .= '&start-min='.$this->c['startDate'].'&start-max='.$this->c['endDate'];
			$this->url .= '&orderby='.$this->c['orderBy'].'&sortorder='.$this->c['sortOrder'];
			
			/* fulltext search */
			if($this->c['query']) {
				$this->url .= '&q='.$this->c['query'];
			}
			
			$this->url .= '&singleevents='.$this->c['singleEvents'];
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
					$ia['rel'][$k] = isset($a['rel']) ? $e[$i]['rel']:false;
					$ia['displayName'][$k] = isset($a['displayName']) ? $e[$i]['displayName']:false;
					$ia['email'][$k] = isset($a['email']) ? $e[$i]['email']:false;
				}
				
				$tmpa['attendees.rel'] = isset($ia['rel']) ? implode(', ', $ia['rel']):'';
				$tmpa['attendees.displayName'] = isset($ia['displayName']) ? implode(', ', $ia['displayName']):'';
				$tmpa['attendees.email'] = isset($ia['email']) ? implode(', ', $ia['email']):'';
			}
			
			/* start & end datetime */
			$tmpa['when.start'] = strtotime($e[$i]['when'][0]['start']);
			$tmpa['when.end'] = strtotime($e[$i]['when'][0]['end']);
			
			unset($e[$i]['attendees']);
			unset($e[$i]['creator']);
			unset($e[$i]['when']);
			
			$tmpa = array_merge($e[$i], $tmpa);
			
			/* proceed to chunk parsing */
			if($i < $this->c['limit']) {
				if($this->c['output'] == 'string') {
					$this->output['string'] .= $this->parseEventChunk($tmpa);
				} elseif($this->c['output'] == 'array') {
					$this->output['array'][] = $this->bufferParsed['data']['items'][$i];
				} else {
					$this->output['string'] .= $this->parseEventChunk($tmpa);
					$this->output['array'][] = $this->bufferParsed['data']['items'][$i];
				}
			}
		}
		
		
		$this->setAgendaMeta();
		unset($tmpa);
	}
	
	function wrapUp($cacheFlag) {
		
		if($cacheFlag === true) {
			/* wrap up cached */
			$this->output = $this->cacheObj;
		} else {
			/* wrap up uncached */
			if($this->c['output'] != 'array') {
				$this->parseWrapperChunk();
			}
			if(isset($this->c['cached'])) {
				$this->modifyCache();
			}
		}
		$this->freeMem();		
		return true;
	}
	
	function setAgendaMeta() {
		$a = $this->bufferParsed['data']; unset($a['items']);
		$this->agendaMeta = $a;
		
		if($this->c['output'] != 'string') {
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
	
	/* PARSING CHUNKS */
	
	function parseEventChunk($event = array()) {
		return $this->modx->getChunk($this->c['eventTpl'], $event);
	}
	
	function parseWrapperChunk() {
		$tmp = array_merge($this->agendaMeta, array('events' => $this->output['string']));
		$this->output['string'] = $this->modx->getChunk($this->c['wrapTpl'], $tmp);
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
	}

	function modifyCache() {
		$this->cacheObj = array('string' => $this->output['string'], 'array' => $this->output['array']);
		$this->eventCacheManager->set($this->modx->resource->getCacheKey().'/'.md5('events'.$this->c['cacheSlot']), $this->cacheObj, $this->c['decay'], $this->_cacheOptions);
	}
	
	/* UTILITIES */
	
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