<?php
  
   
    //渠道用户专用对象
    class Right{
        //身份权限
        public $level;

        protected $isDebug = 0;

        //存放各个权限的行为
        public $level_action = array(
            //"common"内的动作是全局共享的
            //数字代表身份  level_deny表示该角色不能执行某个共享动作
            "1"=>array(

                    array("act1","act2"),

                    "level_deny"=>array("act3")
            ),

            "2" =>array(

                    array("act4")

            ),

            "common"=>array("act3")

        );


        public function __construct($level_actions=array()){

            $this->level_action = $level_actions;

            foreach ($this->level_action as $key => $value) {

                if($key!="common"){
                    //保证禁止权限数组必须存在，初始化数组
                    if(!isset($value["level_deny"]))$this->level_action[$key]["level_deny"] = array();
                }
            }

            //print_r($this->level_action);
        }

        //添加权限身份下的行为
        public function addAction($act,$level=null,$isDeny=0){
            //是数字
            if(is_numeric($level)){

                if($isDeny!==0)

                    $this->level_action[$level][0][] = $act;

                else 

                    $this->level_action[$level]["level_deny"][] = $act;


                
            }else $this->level_action["common"][] = $act;

        }
        
        //删除权限身份下的行为
        public function delAction($act,$level=null,$isDeny=0){

            //是数字
            if(is_numeric($level)){

                if($isDeny!==0)

                    unset($this->level_action[$level][0][array_search($act, $this->level_action[$level][0])]);

                else  

                    unset($this->level_action[$level]["level_deny"][array_search($act, $this->level_action[$level]["level_deny"])]);
                     
            }else {

                unset($this->level_action["common"][array_search($act, $this->level_action["common"])]);

            }

        }
        //验证行为身份是否可以访问
        public function vaild($level,$act){
            
            if(count($this->level_action)&&isset($this->level_action[$level])&&count($this->level_action[$level])){
                //直接拒绝
                if(array_search($act, $this->level_action[$level]["level_deny"])!==false){
                    if($this->isDebug)echo "deny,level $level in deny action:".$act."<br>";
                    return false;
                }//*是全允许的动作
                //print_r($this->level_action[$level][0]);
                if(array_search("*",$this->level_action[$level][0])!==false){
                    if($this->isDebug)echo "pass,admit all action.";
                    return true;
                }//找全局,没找到
                if(array_search($act, $this->level_action["common"])===false){
    
                    //找对应
                    if(array_search($act, $this->level_action[$level][0])===false){

                        if($this->isDebug)echo "deny,find no in $level action:".$act."<br>";

                        return false;

                    }else if($this->isDebug)echo "pass,level $level in action.".$act."<br>";

                }else if($this->isDebug)echo "pass,level $level in common action:".$act."<br>";


                return true;
            }
            if($this->isDebug)echo "actions checked error.<br>";

            return false;

        }



    }


    
    /*
    $r = new Right();

   echo $r->vaild("1","act1")."<br>";
    echo $r->vaild("1","act3")."<br>";
     echo $r->vaild("2","act3")."<br>";
     echo $r->vaild("2","act4")."<br>";

     */
?>