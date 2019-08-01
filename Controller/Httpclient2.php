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
        'ColType' => 'Sql',
        'CrossCol' => 'STCD',
        'CrossRow' => 'TM',
        'CrossRowFormat' => 'yyyy-MM-dd HH时',
        'CrossValue' => 'SumDRP',
        'CurTime' => '',
        'EndTime' => '2017-12-21 23:00:00',
        'IntervalUnit' => 'hour',
        'RP_CODE' => 'BS_RainHourAll',
        'ReSetStartTimeFormat' => 'yyyy年MM月dd日HH时',
        'RowType' => 'Date',
        'STCD' => '2136',
        'STNM' => '',
        'StInfo' => '[{"NAME":"合计","METHOD":"SUM","TYPE":1,"EXPECTED_COL":"TM","FORMAT":"F1"}]',
        'StartTime' => '2015-1-1 00:00:00',
        'Step' => '1',
        'StepUnit' => 'h',
        'TMFormat' => 'yyyy-MM-dd HH时',
        'TimeOut' => '180',
        'timestamp' => '1513838908450'
    );

    $rs = send_post('http://58.22.3.131:9003/report//ReportPage/BSRp/AjaxHandle/RainComplexHandler.ashx?r=0.7159919059810965', $post_data);
    $reg = '/"xmst126":"[\d]+[\.]*[\d]*"/i';


    $matches = array();


    if (preg_match($reg, $rs, $matches)) {

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

    file_put_contents("E:/data/data2.txt", $result);

    // return $result;
}


testxx();



 
