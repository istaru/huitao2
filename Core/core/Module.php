<?php

    Class Module{

        public $sub_module;

        public $date;
        //载入过的文件路径
        static public $loaded_dir_module_com_path = array();

        public function __construct(){

          
            
            //$this->load_module($module);

        }

        //载入模块
        //@param:载入相应模块及其依赖
        public function load_module($module){

            $this->sub_module = $module;
            //载入模块common文件
            $this->_load_module_dir(DIR_MODULE_COM);

            $module_path = DIR_MODULE.DS.strtolower($module).DS;

            if(is_dir($module_path)){
                //载入模块类
                $instance = $this->_load_module_instance($module_path,$module);
                //该模块下的common
                $common_module_path = $module_path."common".DS;

                $this->_load_module_dir($common_module_path);
                //model类
                $models_module_path = $module_path."model".DS;

                $this->_load_module_dir($models_module_path);

            }


            return $instance ? $instance : null;


        }

            //print_r(self::$loaded_dir_module_com_path);
            /*
            new Mmodel();
            new Mpdo();
            new GoodsModule();
            *//*
            if(is_file(DIR_MODULE.'gwcore/module'.EXT)){

                require_once DIR_COMMON.'gwcore/GoodsModule'.EXT;
            }*/

        
        //载入文件夹
        function _load_module_dir($module_path){


            if(!in_array($module_path,self::$loaded_dir_module_com_path)){
                 
                if(is_dir($module_path)){

                    $r = $this->fetchDir($module_path);

                    if($r)self::$loaded_dir_module_com_path[] = $module_path; 

                } else {

                    echo $module_path." path not found.";exit();
                }
            
            }

        }

         //载入文件夹
        function _load_module_instance($module_path,$module){

            //载入模块父类文件
            $parent_module_path = $module_path.ucfirst($module)."Module".EXT;
            //未载入过
            if(!in_array($parent_module_path,self::$loaded_dir_module_com_path)){

                if(is_file($parent_module_path)){

                    include_once($parent_module_path);

                    $cls = ucfirst($module)."Module";

                    $o_cls = new $cls();

                    self::$loaded_dir_module_com_path[] = $parent_module_path;

                    return $o_cls;

                }else{

                    echo $parent_module_path." Class not found.";exit();
                }
            }

        }





        function fetchDir($dir) {

            foreach(glob($dir.DIRECTORY_SEPARATOR.'*') as $file) {
                echo $file."<br>";
                include_once $file;
                
                if(is_dir($file)) {

                    return fetchDir($file);
                }
            }

            return 1;
        } 

        
        function __autoload($class){echo 234234234;
            //DIR_MODULE.DS.strtolower($module).DS."model".DS;
            $path = DIR_MODULE.DS.strtolower($module).DS."model".DS;
            $file = $path.$class . '.php';
            if (is_file($file)) {echo $file;
                require_once($file);
            }
        }
    }
?>