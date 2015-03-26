<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Avast for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Ci\Controller;

use ZendServer\Mvc\Controller\WebAPIActionController;

class CiWebAPIController extends WebAPIActionController
{	
	public function runJobCliAction() {
		set_time_limit(0);
		$params = $this->getRequest()->getPost();
		$jobId = $params['jobId'];
		
		$ciModel = new \Ci\Model\Ci();
		
		$retFull = $ciModel->runProjectActions($jobId);
		
		$response = $this->getResponse();
		$response->setStatusCode(200);
		$response->setContent($retFull);
		return $response;	
	}
		
	public function runJobCli1Action() {
		sleep(300);
		$retFull = '1';
		$response = $this->getResponse();
		$response->setStatusCode(200);
		$response->setContent($retFull);
		return $response;	
	}
}
