<?php
/**
 * Created by PhpStorm.
 * User: zzz
 * Date: 2017/12/21
 * Time: 15:13
 */

function testxx()
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

    $rs=send_post('http://58.22.3.131:9003/report//FrameWork/AjaxHandler/RpHandler.ashx?OPType=GetData&DataType=1,2&r=0.27889130707637133', $post_data);
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

    file_put_contents("E:/data/data.txt",$result);

   // return $result;
}


testxx();



 
