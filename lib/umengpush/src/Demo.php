<?php
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidBroadcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidListcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSBroadcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSListcast.php');

class Demo {
	protected $appkey           = NULL;
	protected $appMasterSecret     = NULL;
	protected $timestamp        = NULL;
	protected $validation_token = NULL;
	protected $model			= "false";

	function __construct($key, $secret) {
		$this->appkey = $key;
		$this->appMasterSecret = $secret;
		$this->timestamp = strval(time());
	}
	/**
	 * [sendAndroidBroadcast 广播]
	 */
	function sendAndroidBroadcast($param=[]) {

		try {
			$brocast = new AndroidBroadcast();
			$brocast->setAppMasterSecret($this->appMasterSecret);
			$brocast->setPredefinedKeyValue("appkey",           $this->appkey);
			$brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			$brocast->setPredefinedKeyValue("ticker",           $param['ticker']);
			$brocast->setPredefinedKeyValue("title",            $param['title']);
			$brocast->setPredefinedKeyValue("text",             $param['text']);
			$brocast->setPredefinedKeyValue("after_open",       "go_custom");
			$brocast->setPredefinedKeyValue("custom",       	$param['custom']);
			// Set 'production_mode' to 'false' if it's a test device.
			// For how to register a test device, please see the developer doc.
			$brocast->setPredefinedKeyValue("production_mode", $this->model);
			if(!empty($param['time'])){
				$brocast->setPredefinedKeyValue("start_time", $param['time']);
			}
			// print("Sending broadcast notification, please wait...\r\n");
			$brocast->send();
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

	/**
	 * [sendAndroidListcast 列播]
	 */
	function sendAndroidListcast($param=[]) {
		try {
			$listcast = new AndroidListcast();
			$listcast->setAppMasterSecret($this->appMasterSecret);
			$listcast->setPredefinedKeyValue("appkey",           $this->appkey);
			$listcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set your device tokens here
			$listcast->setPredefinedKeyValue("device_tokens",    $param['device_tokens']);
			$listcast->setPredefinedKeyValue("ticker",           $param['ticker']);
			$listcast->setPredefinedKeyValue("title",            $param['title']);
			$listcast->setPredefinedKeyValue("text",             $param['text']);
			$listcast->setPredefinedKeyValue("after_open",       "go_custom");
			$listcast->setPredefinedKeyValue("custom",       	$param['custom']);

			$listcast->setPredefinedKeyValue("production_mode", $this->model);
			if(!empty($param['time'])){
				$listcast->setPredefinedKeyValue("start_time", $param['time']);
			}
			// print("Sending unicast notification, please wait...\r\n");
			$listcast->send();
			// print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}


	/**
	 * [sendAndroidUnicast 单播]
	 */
	function sendAndroidUnicast($param=[]) {
		try {
			$unicast = new AndroidUnicast();
			$unicast->setAppMasterSecret($this->appMasterSecret);
			$unicast->setPredefinedKeyValue("appkey",           $this->appkey);
			$unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set your device tokens here
			$unicast->setPredefinedKeyValue("device_tokens",    $param['device_tokens']);
			$unicast->setPredefinedKeyValue("ticker",           $param['ticker']);
			$unicast->setPredefinedKeyValue("title",            $param['title']);
			$unicast->setPredefinedKeyValue("text",             $param['text']);
			$unicast->setPredefinedKeyValue("after_open",       "go_custom");
			$unicast->setPredefinedKeyValue("custom",       	$param['custom']);
			// Set 'production_mode' to 'false' if it's a test device.
			// For how to register a test device, please see the developer doc.
			if(!empty($param['time'])){
				$unicast->setPredefinedKeyValue("start_time", $param['time']);
			}
			$unicast->setPredefinedKeyValue("production_mode", $this->model);
			// print("Sending unicast notification, please wait...\r\n");
			$unicast->send();
			// print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}



	function sendIOSBroadcast($param=[]) {
		try {
			$brocast = new IOSBroadcast();
			$brocast->setAppMasterSecret($this->appMasterSecret);
			$brocast->setPredefinedKeyValue("appkey",           $this->appkey);
			$brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);

			$brocast->setPredefinedKeyValue("alert", $param['title']);
			$brocast->setPredefinedKeyValue("badge", 0);
			$brocast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$brocast->setPredefinedKeyValue("production_mode", $this->model);
			if(!empty($param['custom'])){
				$custom = json_decode($param['custom']);
				// D($custom);die;
				foreach ($custom as $k => $v) {
					$brocast->setPredefinedKeyValue($k, $v);
				}
			}
			$brocast->setPredefinedKeyValue("description", $param['title']);
			// Set customized fields
			if(!empty($param['time'])){
				$brocast->setPredefinedKeyValue("start_time", $param['time']);
			}

			$brocast->send();
			// print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendIOSListcast($param) {

		try {
			$listcast = new IOSListcast();
			$listcast->setAppMasterSecret($this->appMasterSecret);
			$listcast->setPredefinedKeyValue("appkey",           $this->appkey);
			$listcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set your device tokens here
			$listcast->setPredefinedKeyValue("device_tokens",    $param['device_tokens']);
			$listcast->setPredefinedKeyValue("alert", $param['title']);
			$listcast->setPredefinedKeyValue("badge", 0);
			$listcast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$listcast->setPredefinedKeyValue("production_mode", $this->model);

			if(!empty($param['custom'])){
				$custom = json_decode($param['custom']);
				foreach ($custom as $k => $v) {
					$listcast->setPredefinedKeyValue($k, $v);
				}
			}
			// Set customized fields
			if(!empty($param['time'])){
				$listcast->setPredefinedKeyValue("start_time", $param['time']);
			}
			$listcast->send();
			// print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

	function sendIOSUnicast($param) {
		try {
			$unicast = new IOSUnicast();
			$unicast->setAppMasterSecret($this->appMasterSecret);
			$unicast->setPredefinedKeyValue("appkey",           $this->appkey);
			$unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
			// Set your device tokens here
			$unicast->setPredefinedKeyValue("device_tokens",    $param['device_tokens']);
			$unicast->setPredefinedKeyValue("alert", $param['title']);
			$unicast->setPredefinedKeyValue("badge", 0);
			$unicast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$unicast->setPredefinedKeyValue("production_mode", $this->model);

			if(!empty($param['custom'])){
				$custom = json_decode($param['custom']);
				foreach ($custom as $k => $v) {
					$unicast->setPredefinedKeyValue($k, $v);
				}
			}

			// Set customized fields
			if(!empty($param['time'])){
				$unicast->setPredefinedKeyValue("start_time", $param['time']);
			}
			$unicast->send();
			// print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}
}

