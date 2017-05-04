<?php
include DIR_LIB.'umengpush/src/Demo.php';
class HtpushController extends HtController
{
	const SHARE_URL = 'http://terui.net.cn/shopping_new/Applications/Views/bg_gw/share/share.html?num_iid=';
	protected $type = null;
	protected $appKey  = null;
	protected $masterSecret = 'mddms8qjyrd6qnvqwvih0hzsmnw8lljj';
	protected $method = [
							//android
							1 =>
							[
								1 => 'sendAndroidBroadcast',	//广播
								2 => 'sendAndroidListcast',	//列播
								3 => 'sendAndroidUnicast',	//单播
							],
							//ios
							2 =>
							[
								1 => 'sendIOSBroadcast',
								2 => 'sendIOSListcast',	//列播
								3 => 'sendIOSUnicast',
							]
						];
	protected $param = [];

	public function __construct()
	{
        parent::__construct();
		$this->type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : 1;
		$this->push_type = !empty($_REQUEST['push_type']) ? $_REQUEST['push_type'] : 1;

		switch ($this->type) {
			case 1:	//android
				$this->appKey = '584a25cda325114535000dec';
				$this->masterSecret = 'mddms8qjyrd6qnvqwvih0hzsmnw8lljj';
				$this->param = $_REQUEST;
				break;
			case 2:	//ios
				$this->appKey = '5875cd7582b6356ce90018c1';
				$this->masterSecret = 'eotf7287zuj4usebsintvzphfmf1joho';
				$this->param = $_REQUEST;
				break;
		}
	}


	public function uMengPush()
	{
		// echo $_REQUEST['uids'];die;
		if(!empty($_REQUEST['uids']) && $_REQUEST['push_type'] !='1'){
			$limit = $_REQUEST['push_type'] == '2' ? '' : 'LIMIT 1';
			$tokens = M()->query("SELECT device_token FROM gw_push_assoc WHERE uid in ({$_REQUEST['uids']}) $limit",'all');
			$tokens = implode(',',array_column($tokens,'device_token'));
			$this->param['device_tokens'] = $tokens;
		}
		$cla = new Demo($this->appKey,$this->masterSecret);
		if(!empty($this->param['custom']['goods_id'])){
			$sql = "SELECT store_type,num_iid goods_id,url vocher_url,title,pict_url share_img,concat('".self::SHARE_URL."',num_iid) share_url  FROM ngw_goods_online WHERE num_iid = 520382432915";
			$info = M()->query($sql);
			$this->param['custom'] = $info;
		}
		$model = !empty($this->param['model']) ? $this->param['model'] : 'false';
		call_user_func_array([$cla,$this->method[$this->type][$this->push_type]],[$this->param,$model]);
	}


}
