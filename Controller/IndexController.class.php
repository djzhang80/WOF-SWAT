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

class IndexController extends ComController
{
    public function index()
    {

        $model = new \Think\Model();
        $guages = $model->query("select guage_id,guage_name from qw_guage where guage_type=0");
        $pguages = $model->query("select guage_id,guage_name from qw_guage where guage_type=1");
        $tguages = $model->query("select guage_id,guage_name from qw_guage where guage_type=2");

        $pm_scen=M("meteorology_scen");
        $tm_scen=M("temp_scen");
        $scen = M('scenario');
        $scens = $scen->field('scen_id,scen_name')->select();
        $pscens = $pm_scen->field('scen_id,scen_name')->where("idata=1")->select();
        $tscens = $tm_scen->field('scen_id,scen_name')->where("idata=1")->select();
        $this->assign('scens', $scens);
        $this->assign('pscens', $pscens);
        $this->assign('tscens', $tscens);
        $this->assign('guages', $guages);
        $this->assign('pguages', $pguages);
        $this->assign('tguages', $tguages);

        $this->assign('s1', $scens[0]["scen_id"]);
        $this->assign('s2', $pscens[0]["scen_id"]);
        $this->assign('s3', $tscens[0]["scen_id"]);
        $this->assign('g1', $guages[0]["guage_id"]);
        $this->assign('g2', $pguages[0]["guage_id"]);
        $this->assign('g3', $tguages[0]["guage_id"]);


        $this->assign('nav', array('', '', ''));//导航
        $this->display();
    }

    public function getdatas()
    {

//                        name: '流量' + i,
//                        type: 'bar',
//                        animation: false,
//                        lineStyle: {                         width: 1,                         color:'rgba(255, 0, 255, 0.5)'},
//                        data: data
        $scens = explode(",", $_GET["scens"]);
        $guage = $_GET["guage"];
        $pvar = $_GET["pvar"];
        $rs = array();

        if ($pvar < 7) {

            $scen = M('scenario');
            $legend = array();
            $names = $scen->field("scen_id", "scen_name")->where("scen_id in(" . I("scens") . ")")->select();
            foreach ($names as $row) {
                $legend[] = $row["scen_name"];
            }

            $nindex = 0;


            foreach ($scens as $scen) {
                $bit = 5;
                $num_len = strlen($scen);
                $zero = '';
                for ($i = $num_len; $i < $bit; $i++) {
                    $zero .= "0";
                }
                $real_num = $zero . $scen;



                $filename = $real_num . "_" . $guage . "_" . $pvar . ".json";
                // $r["name"] = $filename;
                $filename =
                    ('modeloutput') . $filename;

                $r["type"] = "line";
                $r["name"] = $legend[$nindex];
                $nindex++;
//            $sub["width"] = "1";
//            $sub["color"] = "rgb(" . 120 . "," . 120 . "," . 120 . ",0.5)";
//            $r["lineStyle"] = $sub;

                $filedata = file_get_contents("" . $filename);
                $filedata = str_replace("\r\n", "", $filedata);
                $filedata = str_replace(" ", "", $filedata);
                $r["data"] = json_decode($filedata);
                array_push($rs, $r);
            }
            $series["series"] = $rs;
            $series["legend"] = $legend;
            $this->ajaxReturn($series, "json");
        } elseif ($pvar == 7) {

            $pm_scen=M("meteorology_scen");
            $legend = array();
            $names = $pm_scen->field("scen_id", "scen_name")->where("scen_id in(" . I("scens") . ")")->select();
            foreach ($names as $row) {
                $legend[] = $row["scen_name"];
            }

            $nindex = 0;
            foreach ($scens as $scen) {
                $filename = $scen . "_" . $guage . "_" . $pvar . ".json";
                // $r["name"] = $filename;
                $filename = C('modeloutput') . $filename;

                $r["type"] = "line";
                $r["name"] = $legend[$nindex];
                $nindex++;
                $filedata = file_get_contents("" . $filename);
                $filedata = str_replace("\r\n", "", $filedata);
                $filedata = str_replace(" ", "", $filedata);
                $r["data"] = json_decode($filedata);
                $err= json_last_error(); // 4 (JSON_ERROR_SYNTAX)
                $msg= json_last_error_msg(); // unexpected character
                array_push($rs, $r);
            }
            $series["series"] = $rs;
            $series["legend"] = $legend;
            $this->ajaxReturn($series, "json");
        }
        elseif ($pvar == 8){
            $tm_scen=M("temp_scen");
            $legend = array();
            $names = $tm_scen->field("scen_id", "scen_name")->where("scen_id in(" . I("scens") . ")")->select();
            foreach ($names as $row) {
                $legend[] = $row["scen_name"]."_最大";
                $legend[] = $row["scen_name"]."_最小";
            }

            $nindex = 0;
            foreach ($scens as $scen) {
                $filename = "max".$scen . "_" . $guage . "_" . $pvar . ".json";
                // $r["name"] = $filename;
                $filename = C('modeloutput') . $filename;

                $r["type"] = "line";
                $r["name"] = $legend[$nindex];
                $nindex++;
                $filedata = file_get_contents("" . $filename);
                $filedata = str_replace("\r\n", "", $filedata);
                $filedata = str_replace(" ", "", $filedata);
                $r["data"] = json_decode($filedata);
                $err= json_last_error(); // 4 (JSON_ERROR_SYNTAX)
                $msg= json_last_error_msg(); // unexpected character
                array_push($rs, $r);

                $filename ="min". $scen . "_" . $guage . "_" . $pvar . ".json";
                $filename = C('modeloutput') . $filename;
                $r["type"] = "line";
                $r["name"] = $legend[$nindex];
                $nindex++;
                $filedata = file_get_contents("" . $filename);
                $filedata = str_replace("\r\n", "", $filedata);
                $filedata = str_replace(" ", "", $filedata);
                $r["data"] = json_decode($filedata);
                $err= json_last_error(); // 4 (JSON_ERROR_SYNTAX)
                $msg= json_last_error_msg(); // unexpected character
                array_push($rs, $r);

            }
            $series["series"] = $rs;
            $series["legend"] = $legend;
            $this->ajaxReturn($series, "json");

        }
    }
}