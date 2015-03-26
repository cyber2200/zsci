<?php

ini_set('memory_limit', -1);

require_once('Zend/Http/Client.php');
require_once('Zend/Uri.php');

class webapi_request_exec {
	
	protected $host;	
	protected $action;
	protected $method;
	protected $pskName;
	protected $psk;
	protected $client;
	protected $response;
	
	public function __construct($host, $action, $pskName, $psk) {
		$this->host = $host;
		$this->action = $action;			
		$this->pskName = $pskName;		
		$this->psk = $psk;		

	}
	
	public function prep_client($method='GET', $params=array()) {	
		$this->method = $method;	
			
		$userAgent = "ZSWebApi{$this->action}/1.0";
		$date = gmdate('D, d M Y H:i:s') . ' GMT';
		
		$uri = Zend_Uri::factory($this->host);
		
		$concatString = $uri->getHost() . ':' . $uri->getPort() . ':' . $uri->getPath() . "/Api/{$this->action}:" . $userAgent . ':' . $date;
		
		$signature = hash_hmac('sha256', $concatString, $this->psk);
				
		$this->client = new Zend_Http_Client("{$this->host}/Api/{$this->action}", array('timeout' => 9999999));
		$this->client->setMethod($this->method);
		$this->client->setHeaders(array(
		    'Date'             	=> $date,
		    'User-Agent'       	=> $userAgent,
		    'X-Zend-Signature' 	=> $this->pskName . ';' . $signature,
			'Accept'			=> 'application/vnd.zend.serverapi+xml;version=1.9', // replace if working against older versions 
		));
		//->setFileUpload('Y:\qa-stuff\apps\sanity\mtrig.zpk', 'fileupload');
		
		if ($params) {
			$this->is_get() ? $this->client->setParameterGet($params) : $this->client->setParameterPost($params);
		}				
	}
	
	public function get_client() {
		return $this->client;
	}
	
	public function exec_request() {
		$this->response = $this->client->request();
		return $this->response->getBody();
	}
	
	public function get_request_headers() {
		return $this->response->getHeaders();
	}	
	
	public function get_request_status() {
		return $this->response->getStatus();
	}
		
	protected function is_get() {
		return $this->method === 'GET';
	}
}