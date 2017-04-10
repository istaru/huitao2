<?php
 class HtUserModel{
     /**
      * 获取app用户的信息
     */
      public function queryUsers($data = []){
          /**
           * 查询单个用户
           */
          if(isset($data['phone'])) {
              return M('uid')->where(['phone' => ['=',$data['phone']]])->select();
              /**
               * 查询全部用户
               */
          } else {
              /**
               * [$page 如果没分页参数 则默认第一页]
               */
              $page = !empty($data['page']) ? $data['page'] : 1;
              /**
               * 查询每页的用户数量
               */

              $data['data'] = M('uid')->field(['id','nickname','objectId','price','sfuid','phone','power','alipay'])->page($page,30)->select();
              /**
               * 查询用户总数量
               */
              $data['sum']  = M('uid')->field('id')->count();
              return $data;
          }
     }
     /**
      *获取后台用户的信息
     */
     public function queryBackUsers($data = []){
         /**
          * 查询单个用户
          */
         if(isset($data['id'])) {
             return M('htuser')->where(['id' => ['=',$data['id']]])->select();
             /**
              * 查询全部用户
              */
         } else {
             /**
              * [$page 如果没分页参数 则默认第一页]
              */
             $page = !empty($data['page']) ? $data['page'] : 1;
             /**
              * 查询每页的用户数量
              */

             $data['data'] = M('htuser')->page($page,10)->select();
             /**
              * 查询用户总数量
              */
             $data['sum']  = M('htuser')->field('id')->count();
             return $data;
         }
     }
     public function getLoginInfo($username,$password){
        $user=M("htuser");
        return $user->where("username=%s and password=%s",[$username,$password])->select('single');
     }
     public function upUserInfo($data)
     {
        if(is_array($data) && !empty($data)) {
            $id = $data['id'];
            unset($data['id']);
            return M('htuser')->where('id = %d',[$id])->save($data);
        }
     }
 }