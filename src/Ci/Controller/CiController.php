<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Avast for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Ci\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class CiController extends AbstractActionController
{
	protected $dbFile = '/usr/local/zend/gui/vendor/Ci/var/db/ci.db';
	
    public function indexAction() {
		$ciModel = new \Ci\Model\Ci();
		$buildStats = $ciModel->getBuildStats();
        return array('results' => $ciModel->getAllProjects(), 'buildStats' => $buildStats);
    }
	
	public function projectAction() {
		$tArr = $this->getRequest()->getQuery();
		$jobId = $tArr['jobId'];
		$ruleId = isset($tArr['ruleId']) ? $tArr['ruleId'] : '';
		if ($jobId != '') {
			$ciModel = new \Ci\Model\Ci();	
			if ($ruleId != '') {
				$ciModel->updateJobRule($jobId, $ruleId);
			}
			$actions = $ciModel->getActionsByProjectId($jobId);
			$projectData = $ciModel->getProjectDataByProjectId($jobId);
			$actionStats = $ciModel->getActionsStats($jobId);
			$buildStats = $ciModel->getBuildStats($jobId);
			return array('data' => $projectData, 'actions' => $actions, 'actionsStats' => $actionStats, 'buildStats' => $buildStats);
		} else {
			return array('new' => true);
		}	
	}
	
	public function saveAction() {
		$ciModel = new \Ci\Model\Ci();	
		$params = $this->getRequest()->getPost();
		$enabled = $params['data']['jobEnabled'] == 'false' ? 0 : 1;
		
		if ($params['data']['jobName'] == '') {
			$response = $this->getResponse();
			$response->setStatusCode(200);
			$ret = 'Please select project name';
			$response->setContent($ret);
			return $response;								
		}
		
		if (isset($params['data']['jobId']) && $params['data']['jobId'] != '') {
			if (! $ciModel->isProjectNameAvailable($params['data']['jobName'], $params['data']['jobId'])) {
				$response = $this->getResponse();
				$response->setStatusCode(200);
				$ret = 'Please select another project name';
				$response->setContent($ret);
				return $response;					
			}
			$jobId = $params['data']['jobId'];
			$projectData['jobName'] = $params['data']['jobName'];
			$projectData['jobDesc'] = $params['data']['jobDesc'];
			$projectData['enabled'] = $enabled;
			$projectData['releasescript'] = $params['data']['releasescript'];
			$projectData['repo'] = $params['data']['repo'];
			$projectData['jobruleid'] = $params['data']['jobruleid'];
			$projectData['actions'] = $params['act'];
			file_put_contents("/tmp/test1", print_r($params, true));
			$ciModel->updateProject($jobId, $projectData);
			
			$response = $this->getResponse();
			$response->setStatusCode(200);
			$ret = 'Job saved';
			$response->setContent($ret);
			return $response;
		} else {
			if (! $ciModel->isProjectNameAvailable($params['data']['jobName'])) {
				$response = $this->getResponse();
				$response->setStatusCode(200);
				$ret = 'Please select another project name';
				$response->setContent($ret);
				return $response;					
			}
			$projectData['jobName'] = $params['data']['jobName'];
			$projectData['jobDesc'] = $params['data']['jobDesc'];
			$projectData['enabled'] = $enabled;
			$projectData['releasescript'] = $params['data']['releasescript'];
			$projectData['repo'] = $params['data']['repo'];
			$projectData['jobruleid'] = $params['data']['jobruleid'];
			$projectData['actions'] = isset($params['act']) ? $params['act'] : '';
			
			$response = $this->getResponse();
			$response->setStatusCode(200);
			$projectId = $ciModel->createProject($projectData);
			$ret = '';
			$ret .= 'New project has been created, project id: ' . $projectId;
			$response->setContent($ret);
			return $response;
		}
	}
	
	public function deleteAction() {
		$params = $this->getRequest()->getPost();
		
		$ciModel = new \Ci\Model\Ci();
		$ciModel->deleteProject($params['job-id']);
		
		$response = $this->getResponse();
		$response->setStatusCode(200);
		$ret = 'OK';
		$response->setContent($ret);
		return $response;		
	}
	
	public function disableAction() {
		$params = $this->getRequest()->getPost();
		$projectId = $params['job-id'];
		$ciModel = new \Ci\Model\Ci();
		$ciModel->disableProject($projectId);
		
		$response = $this->getResponse();
		$response->setStatusCode(200);
		$ret = 'OK';
		$response->setContent($ret);
		return $response;		
	}

	public function enableAction() {
		$params = $this->getRequest()->getPost();
		$projectId = $params['job-id'];
		$ciModel = new \Ci\Model\Ci();
		$ciModel->enableProject($projectId);
		
		$response = $this->getResponse();
		$response->setStatusCode(200);
		$ret = 'OK';
		$response->setContent($ret);
		return $response;	
	}
	
	public function releaseAction() {
		$tArr = $this->getRequest()->getQuery();
		$jobId = $tArr['jobId'];
		$ciModel = new \Ci\Model\Ci();
		$ret = $ciModel->getProjectDataByProjectId($jobId);
		return array('ret' => $ret);
	}
	
	public function executeReleaseAction() {
		$params = $this->getRequest()->getPost();
		
		if ($params['release-manager'] == '' || $params['release-purpose'] == '' || $params['version'] == '') {
			$response = $this->getResponse();
			$response->setStatusCode(200);
			$response->setContent("Please fill the form");
			return $response;		
		}
		
		$jobId = $params['jobId'];	
		
		$releaseData['release-manager'] = $params['release-manager'];
		$releaseData['release-purpose'] = $params['release-purpose'];
		$releaseData['version'] = $params['version'];
		$releaseData['release-manager'] = $params['release-manager'];
		$releaseData['datetimestr'] = $params['datetimestr'];
		
		$ciModel = new \Ci\Model\Ci();
		$response = $this->getResponse();
		$response->setStatusCode(200);
		$response->setContent($ciModel->executeReleaseScript($jobId, $releaseData));
		return $response;	
	}
	
	public function getReleasesAction() {
		$params = $this->getRequest()->getPost();
		$response = $this->getResponse();
		$response->setStatusCode(200);
		header('Content-Type: application/json');
		$ciModel = new \Ci\Model\Ci();
		$response->setContent(json_encode($ciModel->getReleases($params['jobId'])));
		return $response;	
	}
	
	public function scheduleAction() {
		$tArr = $this->getRequest()->getQuery();
		$jobId = $tArr['jobId'];
		
		$ciModel = new \Ci\Model\Ci();	

		$projectData = $ciModel->getProjectDataByProjectId($jobId);
		
		$jobTrigger = "http://{$_SERVER['SERVER_NAME']}/run-ci/run-ci-job.php?jobId={$jobId}";
		$jobName = "CI Project #" . $jobId;
		return array('jobId' => $jobId, 'jobTrigger' => $jobTrigger, 'projectData' => $projectData);
	}
	
	public function projectFromTemplateAction() {
		
	}
	
	public function createFromTemplateAction() {
		$ciModel = new \Ci\Model\Ci();	
		$tArr = $this->getRequest()->getQuery();
		$template = $tArr['template'];	
		$projectName = $tArr['projectName'];		
		$err = '';
		if (! $ciModel->isProjectNameAvailable($projectName)) {
			$err = 'Please select another project name';
		} else {
			switch($template) {
				default:
					$projectData['jobName'] = $projectName;
					$projectData['jobDesc'] = $projectName;
					$projectData['enabled'] = 1;
					$projectData['releasescript'] = '';
					$projectData['repo'] = '';
					$projectData['jobruleid'] = '';
					$projectData['actions'] = array(
													array('actName' => 'Prepare', 'actAct' => '/usr/local/zend/bin/php /usr/local/zend/gui/vendor/Ci/var/ci-scripts/prepare.php'), 
													array('actName' => 'Test', 'actAct' => '/usr/local/zend/bin/php /usr/local/zend/gui/vendor/Ci/var/ci-scripts/test.php'),
													array('actName' => 'Build', 'actAct' => '/usr/local/zend/bin/php /usr/local/zend/gui/vendor/Ci/var/ci-scripts/build.php'),
													array('actName' => 'Provision', 'actAct' => '/usr/local/zend/bin/php /usr/local/zend/gui/vendor/Ci/var/ci-scripts/provision.php'),
													array('actName' => 'Deploy', 'actAct' => '/usr/local/zend/bin/php /usr/local/zend/gui/vendor/Ci/var/ci-scripts/deploy.php'),
												);	
				break;
			}
			
			$projectId = $ciModel->createProject($projectData);
		}
		return (array('err' => $err, 'projectId' => $projectId));
	}
	
	public function monitorAction() {
		
	}
	
	public function monitorAjaxAction() {
		$t = shell_exec("ps -eo pcpu,pid,user,args | sort -k 1 -r | head -20 2>&1");
		$tArr = explode("\n", $t);
		echo '<pre>';
		foreach ($tArr as $row) {
			if (substr($row, 0, 1) != '%') {
				echo $row . "\n";
			}
		}
        echo '</pre>';
		$t = shell_exec('grep \'cpu \' /proc/stat | awk \'{usage=($2+$4)*100/($2+$4+$5)} END {print usage "%"}\'');
		echo '<pre>Average CPU: ' . $t . '</pre>';
		$t = shell_exec('free -m');
		echo '<pre>' . $t . '</pre>';
		$t = shell_exec('tail -n 20 /usr/local/zend/var/log/php.log 2>&1');
		$tArr = explode("\n", $t);
		echo '<pre>';
		for ($i = count($tArr); $i > 0; $i--) {
			echo $tArr[$i] . "\n";
		}
		echo '</pre>';
		die();
	}
}
