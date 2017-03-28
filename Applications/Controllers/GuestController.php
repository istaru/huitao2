<?php
class GuestController {
    public function query($type = true) {
        $params = $_REQUEST;
        $start_time = $params['start_time'];
        $end_time   = $params['end_time'];
        //查出特邀用户人数
        $sql = "SELECT count(0) sum FROM gw_uid WHERE power = 2 and createdAt BETWEEN '{$start_time}' AND '{$end_time}'";
        $sum = M('uid')->query($sql,'single');
        $sum['sum'] or info('还没有邀请人呢');
        $gather['sumGuest'] = $sum['sum'];
        /**
         * 查询出特邀用户邀请的用户人
         */
        $sql = 'SELECT a.phone uid , c.nickname , c.phone,c.createdAt FROM(
            SELECT * FROM gw_uid WHERE power = 2 '.(empty($params['uid']) ? '' : "AND phone = '{$params['uid']}'")."AND createdAt BETWEEN '{$start_time}' AND '{$end_time}'".') a
            LEFT JOIN( SELECT uid , score_source FROM gw_uid_log WHERE score_type = 2) b ON b.uid = a.objectId LEFT JOIN( SELECT objectId , nickname , createdAt,phone FROM gw_uid) c ON c.objectId = b.score_source';
        $data = M()->query($sql, 'all');
        if(!$type)
            return $data;
        $data or info('还没有邀请过好友呢',-1);
        //获取被邀请人总人数
        $gather['sumInviter'] = count(array_filter(array_column($data, 'phone')));
        $data = array_splice($data, ($params['page_no']-1) * $params['page_size'], $params['page_size']);
        $data = [
            'msg'    => 'ok',
            'status' => 1,
            'data'   => [
                'list'   => $data,
                'params' => $gather
            ],
        ];
        info($data);
    }

    public function export() {
        $phpExcell = new PhpExcelController;
        $phpExcel = $phpExcell->phpExcel;
        $phpExcel->getProperties()->setTitle('test');
        $phpExcel->setActiveSheetIndex(0);
        $activeSheet = $phpExcel->getActiveSheet();
        $activeSheet->setCellValue('A1', '特邀用户')->setCellValue('B1', '我的好友')->setCellValue('C1', '好友手机号')->setCellValue('D1', '好友注册时间');
        //循环源数据 进行导出处理
        $i = 2;
        foreach($this->query(false) as $k => $v) {
            $activeSheet->setCellValue('A'.$i, $v['uid'])->getStyle('A'.$i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $activeSheet->setCellValue('B'.$i, $v['nickname']);
            $activeSheet->setCellValue('C'.$i, $v['phone']);
            $activeSheet->setCellValue('D'.$i, $v['createdAt']);
            $i++;
        }
        //设置header头
        $phpExcell->setHeader('我的好友');
        $excel = new PHPExcel_Writer_Excel2007($phpExcel);
        $excel->save('php://output');
    }
}

