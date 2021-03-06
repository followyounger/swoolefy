<?php
namespace Swoolefy\Core;

use Swoolefy\Core\Swfy;
use Swoolefy\Core\BaseServer;

trait ServiceTrait {
	/**
	 * getMasterId 获取当前服务器主进程的PID
	 * @return   int
	 */
	public function getMasterPid() {
		return Swfy::$server->master_pid;
	}

	/**
	 * getManagerId 当前服务器管理进程的PID
	 * @return   int
	 */
	public function getManagerPid() {
		return Swfy::$server->manager_pid;
	}

	/**
	 * getCurrentWorkerPid 获取当前worker的进程PID 
	 * @return int  
	 */
	public function getCurrentWorkerPid() {
		$workerPid = Swfy::$server->worker_pid;
		if($workerPid) {
			return $workerPid;
		}else {
			return posix_getpid();
		}
	}

	/**
	 * getCurrentWorkerId 获取当前处理的worker_id
	 * @return   int
	 */
	public function getCurrentWorkerId() {
		$workerId = Swfy::$server->worker_id;
		return $workerId;
	}

	/**
	 * getConnections 服务器当前所有的连接
	 * @return  object 
	 */
	public function getConnections() {
		return Swfy::$server->connections;
	}

	/**
	 * getWorkersPid 获取当前所有worker_pid与worker的映射
	 * @return   array
	 */
	public function getWorkersPid() {
		return BaseServer::getWorkersPid();
	}

	/**
	 * getLastError 返回最近一次的错误代码
	 * @return   int 
	 */
	public function getLastError() {
		return Swfy::$server->getLastError();
	}

	/**
	 * getStats 获取swoole的状态
	 * @return   array
	 */
	public function getSwooleStats() {
		return Swfy::$server->stats();
	}

	/**
	 * getLocalIp 获取ip,不包括端口
	 * @return   array
	 */
	public function getLocalIp() {
		return swoole_get_local_ip();
	}

	/**
	 * getIncludeFiles 获取swoole启动时,worker启动前已经include内存的文件
	 * @return   array|boolean
	 */
	public function getInitIncludeFiles($dir='http') {
		// 获取当前的处理的worker_id
		$workerId = $this->getCurrentWorkerId();
		if(isset(Swfy::$config['setting']['log_file'])) {
			$path = pathinfo(Swfy::$config['setting']['log_file'], PATHINFO_DIRNAME);
			$dir = strtolower($dir);
			$filePath = $path.'/includes.json';
		}else {
			$dir = ucfirst($dir);
			$filePath = __DIR__.'/../'.$dir.'/includes.json';
		}
		
		if(is_file($filePath)) {
			$includes_string = file_get_contents($filePath);
			if($includes_string) {
				return [
					'current_worker_id' => $workerId,
					'include_init_files' => json_decode($includes_string,true),
				];
			}else {
				return false;
			}
		}

		return false;
		
	}

	/**
	 * getMomeryIncludeFiles 获取执行到目前action为止，swoole server中的该worker中内存中已经加载的class文件
	 * @return  array 
	 */
	public function getMomeryIncludeFiles() {
		$includeFiles = get_included_files();
		$workerId = $this->getCurrentWorkerId();
		return [
			'current_worker_id' => $workerId,
			'include_momery_files' => $includeFiles,
		];
	}

	/**
	 * getConf 获取协议层对应的配置
	 * @param    $protocol
	 * @return   array
	 */
	public function getConf($protocol='http') {
		$protocol = strtolower($protocol);
		switch($protocol) {
			case 'http':
				return \Swoolefy\Http\HttpServer::getConf();
			break;
			case 'websocket':
				return \Swoolefy\Websocket\WebsocketServer::getConf();
			break;
			case 'rpc':
				return \Swoolefy\Rpc\RpcServer::getConf();
			break;
			default:return \Swoolefy\Http\HttpServer::getConf();
			break;
		}	
	}

	/**
	 * isWorkerProcess 进程是否是worker进程
	 * @param    $worker_id
	 * @return   boolean
	 */
	public static function isWorkerProcess() {
		$worker_id = self::getCurrentWorkerId();
		if($worker_id < Swfy::$config['setting']['worker_num']) {
			return true;
		}
		return false;
	}

	/**
	 * isTaskProcess 进程是否是task进程
	 * @param    $worker_id
	 * @return   boolean
	 */
	public static function isTaskProcess() {
		$worker_id = self::getCurrentWorkerId();
		return self::isWorkerProcess($worker_id) ? false : true;
	}

}