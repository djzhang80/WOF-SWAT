<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：寒川<hanchuan@qiawei.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：后台首页控制器。
 *
 **/

namespace Qwadmin\Controller;

class TestController extends ComController
{
    public function index()
    {
        $this->assign('fordate', '2002-01-01');//导航
        $data="[000.0,1.0]";
        $r= json_decode($data);
        $this->display();
    }

    public function testxx()
    {
        $post_data = array(
            'ColType' => 'Distinct',
            'CrossCol' => 'STCD',
            'CrossColText' => 'STNM',
            'CrossRow' => 'TM',
            'CrossRowFormat' => 'MM月dd日HH时',
            'CrossValue' => 'Value',
            'EndTime' => '2017-12-21 23:00:00',
            'RP_CODE' => 'FuJian_WaterHour',
            'RowType' => 'Date',
            'STCD' => 'xmst126',
            'STNM' => '',
            'StartTime' => '2015-1-1 00:00:00',
            'Step' => '1',
            'StepUnit' => 'h',
            'timestamp' => '1513838908450',
            'type' => 'RR'
        );

        $rs=TestController::send_post('http://58.22.3.131:9003/report//FrameWork/AjaxHandler/RpHandler.ashx?OPType=GetData&DataType=1,2&r=0.27889130707637133', $post_data);
        $reg='/"xmst126":"[\d]+[\.]*[\d]*"/i';


        $matches = array();



        if(preg_match($reg, $rs, $matches)){

           var_dump($matches);

        }
        echo "<br>";
        echo $rs;

    }



    function send_post($url, $post_data)
    {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }
}