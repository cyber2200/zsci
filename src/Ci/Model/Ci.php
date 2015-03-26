<?php
namespace Ci\Model;

class Ci {
	protected $dbFile = '/usr/local/zend/gui/vendor/Ci/var/db/ci.db';
	protected $apiKey = '';
	
	public function getDbCon() {
		$file_db = new \PDO('sqlite:' . $this->dbFile);
		$file_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return $file_db;
	}
	
	public function getAllProjects() {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->query('SELECT * FROM jobs');
		return $stmt;
	}
	
	public function getActionsByProjectId($projectId) {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("SELECT * FROM actions WHERE jobid = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		return $stmt;
	}
	
	public function getProjectDataByProjectId($projectId) {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("SELECT * FROM jobs WHERE jobid = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
		$row = $stmt->fetch();
		return $row;
	}
	
	public function updateProject($projectId, $projectData) {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("UPDATE jobs set jobname = :jobName, 
						jobdesc = :jobDesc, 
						enabled = :enabled,
						releasescript = :releaseScript,
						repo = :repo,
						jobruleid = :jobRuleId
						WHERE jobid = :jobId");
		
		$stmt->bindParam(':jobName', $projectData['jobName']);
		$stmt->bindParam(':jobDesc', $projectData['jobDesc']);
		$stmt->bindParam(':enabled', $projectData['enabled']);
		$stmt->bindParam(':releaseScript', $projectData['releasescript']);
		$stmt->bindParam(':repo', $projectData['repo'] );
		$stmt->bindParam(':jobRuleId', $projectData['jobruleid']);
		$stmt->bindParam(':jobId', $projectId);
		$stmt->execute();
		
		$stmt = $dbCon->prepare("DELETE FROM actions where jobid = :jobid;");
		$stmt->bindValue(':jobid', $projectId);
		$stmt->execute();
		
		foreach($projectData['actions'] as $act) {
			$stmt = $dbCon->prepare("INSERT INTO actions(jobid, actionname, action) values(:jobId, :actName, :act);");
			$stmt->bindParam(':jobId', $projectId);
			$stmt->bindParam(':actName', $act['actName']);
			$stmt->bindParam(':act', $act['actAct']);
			$stmt->execute();
		}
	}
	
	public function createProject($projectData) {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("INSERT INTO jobs(jobname, jobdesc, enabled, releasescript, repo, jobruleid) values(:jobName, 
				:jobDesc, 
				:enabled,
				:releaseScript,				
				:repo,
				:jobRuleId);");
		$stmt->bindParam(':jobName', $projectData['jobName']);
		$stmt->bindParam(':jobDesc', $projectData['jobDesc']);
		$stmt->bindParam(':enabled', $projectData['enabled']);
		$stmt->bindParam(':releaseScript', $projectData['releasescript']);
		$stmt->bindParam(':repo', $projectData['repo'] );
		$stmt->bindParam(':jobRuleId', $projectData['jobruleid']);
		$stmt->execute();
		$projectId = $dbCon->lastInsertId();
		
		$stmt = $dbCon->prepare("DELETE FROM actions where jobid = :jobid;");
		$stmt->bindValue(':jobid', $projectId);
		$stmt->execute();
		
		foreach($projectData['actions'] as $act) {
			$stmt = $dbCon->prepare("INSERT INTO actions(jobid, actionname, action) values(:jobId, :actName, :act);");
			$stmt->bindParam(':jobId', $projectId);
			$stmt->bindParam(':actName', $act['actName']);
			$stmt->bindParam(':act', $act['actAct']);
			$stmt->execute();
		}
		return $projectId;
	}
	
	public function deleteProject($projectId) {
		$dbCon = $this->getDbCon();
		
		$stmt = $dbCon->prepare("DELETE FROM jobs WHERE `jobid` = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
		
		$stmt = $dbCon->prepare("DELETE FROM actions WHERE `jobid` = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
		
		$stmt = $dbCon->prepare("DELETE FROM actionstats WHERE `projectid` = :projectid");
		$stmt->bindParam(":projectid", $projectId);
		$stmt->execute();	

		$stmt = $dbCon->prepare("DELETE FROM `builds` WHERE `jobid` = :jobid");
		$stmt->bindParam(":jobid", $projectId);
		$stmt->execute();		
		
		$stmt = $dbCon->prepare("DELETE FROM `releases` WHERE `jobid` = :jobid");
		$stmt->bindParam(":jobid", $projectId);
		$stmt->execute();		
	}
	
	public function disableProject($projectId) {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("UPDATE jobs SET enabled = 0 WHERE `jobid` = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
	}
	
	public function enableProject($projectId) {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("UPDATE jobs SET enabled = 1 WHERE `jobid` = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
	}
	
	public function executeReleaseScript($projectId, $releaseData) {	
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("SELECT * FROM jobs WHERE jobid = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
		$res = $stmt->fetchAll();
		$releaseScript = $res[0]['releasescript'];
		
		$ret = '';
		$ret .= 'Executing ' . $releaseScript . "\n";
		$ret .= shell_exec($releaseScript . ' 2>&1; echo $?') . "\n";
		
		$stmt = $dbCon->prepare("INSERT INTO releases(jobid, releasemanager, releasepurpose, version, datetimestr, rawoutput) values(:jobId,
		:releaseManager, 
		:releasePurpose, 
		:version, 
		:datetimestr,
		:ret);");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->bindParam(":releaseManager", $releaseData['release-manager']);
		$stmt->bindParam(":releasePurpose", $releaseData['release-purpose']);
		$stmt->bindParam(":version", $releaseData['version']);
		$stmt->bindParam(":datetimestr", $releaseData['datetimestr']);
		$stmt->bindParam(":ret", $ret);
		$stmt->execute();
		return $ret;
	}
	
	public function getReleases($projectId) {
		$dbCon = $this->getDbCon();
		$stmt = $dbCon->prepare("SELECT * FROM releases WHERE jobid=:jobId ORDER BY releaseid desc;");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
		return $stmt->fetchAll();
	}
	
	public function isProjectNameAvailable($projectName, $projectId = '') {
		$dbCon = $this->getDbCon();
		if ($projectId == '') {
			$stmt = $dbCon->prepare("SELECT count(*) as c FROM jobs WHERE jobname = :jobName");
			$stmt->bindParam(":jobName", $projectName);
			$stmt->execute();
			$row = $stmt->fetch();
			if ($row['c'] == '0') {
				return true;
			} else {
				return false;
			}
		} else {
			$stmt = $dbCon->prepare("SELECT * FROM jobs WHERE jobname = :jobName");
			$stmt->bindParam(":jobName", $projectName);	
			$stmt->execute();
			$rows = $stmt->fetchAll();
			if (count($rows) == 0) {
				return true;
			} else {
				if ($rows[0]['jobid'] == $projectId) {
					return true;
				} else {
					return false;
				}
			}
		}
	}
	
	public function runProjectActions($projectId) {
		$buildStatus = 'SUCCESS';
		$this->apiKey = trim(file_get_contents("/usr/local/zend/gui/vendor/Ci/apikey"));
		
		$dbCon = $this->getDbCon();
		
		$stmt = $dbCon->prepare("INSERT INTO `builds`(`jobid`, `buildtime`, `builddate`, `buildstatus`) VALUES(:projectId, :buildTime, :buildDate, :buildStatus);");
		$stmt->bindParam(":projectId", $projectId);
		$stmt->bindValue(":buildTime", '0');
		$stmt->bindValue(":buildDate", date("d-m-Y H:i:s"));
		$stmt->bindValue(":buildStatus", 'FAILED');
		$stmt->execute();
		$buildId = $dbCon->lastInsertId();
		
		$stmt = $dbCon->prepare("SELECT * FROM jobs WHERE jobid = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
		$jobInfo = $stmt->fetchAll();
		$retFull = "JobID: {$projectId}, Job Name: {$jobInfo[0]['jobname']}\n\n";
		if ($jobInfo[0]['enabled'] != 1) {
			$retFull .= "Job is disabled, exiting\n\n";
			return $retFull;	
		}
		
		$stmt = $dbCon->prepare("SELECT * FROM actions WHERE jobid = :jobId");
		$stmt->bindParam(":jobId", $projectId);
		$stmt->execute();
		$i = 0;
		$buildStart = microtime(true);
		foreach ($stmt as $action) {
			$i++;
			$ret = '';
			$timeStart = microtime(true);
			$t = shell_exec($action['action'] . ' 2>&1; echo $?');
			$timeEnd = microtime(true);
			$time = $timeEnd - $timeStart;
			
			$stmt = $dbCon->prepare("INSERT INTO actionstats(projectid, actionname, actiontime) VALUES(:projectId, :actionName, :actionTime)");
			$stmt->bindParam(":projectId", $projectId);
			$stmt->bindParam(":actionName", $action['actionname']);
			$stmt->bindParam(":actionTime", $time);
			$stmt->execute();
			
			if ($t == '') {
				$ret .= 'The action got no output';
			} else {
				$ret .= $t;
			}
			$now = date("Y-m-d H:i:s");  
			$retFull .= "<h1>Run at: " . $now . "</h1>\n";
			$retFull .= "<h1>Action Name: " . $action['actionname'] . "</h1>\n";
			$retFull .= "<h1>Start of output</h1>\n";
			$retFull .= $ret;
			$retFull .= "<h1>End of output</h1>\n\n";		
			if (trim(substr($t, -2)) != 0) {
				$buildStatus = 'FAILED';
				$retFull .= "\n\n <h1>ACTION FAILED</h1> \n\n";
				$date = gmdate('D, d M Y H:i:s') . ' GMT';
				$host = 'admin';
				$useragent = 'Zend_Http_Client/1.10';
				$accept = 'application/vnd.zend.serverapi+xml;version=1.9';
				$apiKey = $this->apiKey;
				$serverUrl = 'http://localhost:10081';
				$path = '/ZendServer/Api/sendNotification';

				$sign =  $host . '; ' . hash_hmac('sha256', $host . ":" . $path . ":" . $useragent . ":" . $date, $apiKey);

				$ch = curl_init($serverUrl . $path);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch,CURLOPT_HTTPHEADER,
					array(
						'Host: ' . $host,
						'Date: ' . $date,
						'User-agent: ' . $useragent,
						'Accept: ' . $accept,
						'X-Zend-Signature: ' . $sign
					)
				);
				curl_setopt($ch,CURLOPT_POSTFIELDS, array('type' => 'BUILD FAILED!!!', 'ip' => '127.0.0.1'));
				$output = curl_exec($ch);
				curl_close($ch);
				$retFull .= htmlspecialchars($output);

				break;
			}
		}
		if ($i == 0) {
			$retFull .= 'No actions found';
		}
		$buildEnd = microtime(true);
		
		$stmt = $dbCon->prepare("UPDATE `builds` SET buildtime = :buildTime, buildstatus = :buildStatus WHERE buildid = :buildId");
		$stmt->bindValue(":buildTime", ($buildEnd - $buildStart));
		$stmt->bindValue(":buildStatus", $buildStatus);
		$stmt->bindValue(":buildId", $buildId);
		$stmt->execute();
		
		$stmt = $dbCon->prepare("UPDATE `jobs` SET lastbuildstatus = :lastBuildStatus WHERE jobid = :projectId");
		$stmt->bindValue(":lastBuildStatus", $buildStatus);
		$stmt->bindValue(":projectId", $projectId);
		$stmt->execute();
		
		return $retFull;
	}
	
	public function updateJobRule($jobId, $ruleId) {
		$dbCon = $this->getDbcon();
		$stmt = $dbCon->prepare("UPDATE jobs SET jobruleid = :jobRuleId WHERE jobid = :jobId");
		$stmt->bindParam(":jobId", $jobId);	
		$stmt->bindParam(":jobRuleId", $ruleId);
		$stmt->execute();
	}
	
	public function getActionsStats($projectId) {
		$actionStats = array();
		$dbCon = $this->getDbCon();
		
		$stmt = $dbCon->prepare("SELECT actionname, sum(actiontime) as s FROM `actionstats` WHERE projectid = :projectId GROUP BY actionname");
		$stmt->bindParam(":projectId", $projectId);
		$stmt->execute();
		foreach ($stmt as $action) {
			$actionStats[] = array('name' => $action['actionname'], 'time' => $action['s']);
		}
		return $actionStats;
	}
	
	public function getBuildStats($projectId ='') {
		$buildStats = array();
		$dbCon = $this->getDbCon();
		
		if ($projectId != '') {
			$stmt = $dbCon->prepare("SELECT * FROM `builds` JOIN `jobs` ON `builds`.`jobid` = `jobs`.`jobid` WHERE `builds`.jobid = :jobId AND `buildtime` <> '0' ORDER BY buildid desc LIMIT 0, 10");
			$stmt->bindParam(":jobId", $projectId);
		} else {
			$stmt = $dbCon->prepare("SELECT * FROM `builds` JOIN `jobs` ON `builds`.`jobid` = `jobs`.`jobid` WHERE `buildtime` <> '0'ORDER BY `buildid` DESC LIMIT 0, 10;");
		}
		$stmt->execute();
		foreach ($stmt as $build) {
			$buildStats[] = array('buildTime' => $build['buildtime'], 'buildDate' => $build['builddate'], 'jobName' => $build['jobname']);
		}
		return $buildStats;
	}
}