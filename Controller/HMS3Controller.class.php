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

use DateTime;
use DatePeriod;
use DateInterval;

class HMS3Controller extends ComController
{

    public function forcast()
    {

        //SELECT * FROM excel.dhx_data where sheetid=1 and columnid=1 order by rowid;

        $model = M('hec_pcpguages');
        $rs = $model->select();

        $model2 = M('hec_flowgagues');
        $rs2 = $model2->select();
        $this->assign("pcps", $rs);
        $this->assign("flows", $rs2);


        $model = M("hecprojects");
        $scens = $model->field("startTime", "endTime", "projectname", "id")->order("id desc")->page($page, $rows)->select();

        $this->assign("s1", $rs[0]["id"]);
        $this->assign("s2", $rs2[0]["id"]);

        //$fc = M('forcast');
        //$rs3 = $fc->select();
        $this->assign("s3", "realtime");

        $this->display();
    }

    public function retrieve_current_pcpDataf()
    {
        $project = "forcast";
        $outputfile = C('modeloutput') . "hec/forcast/" . $project . "_pcp_" . "currentvalue" . "_" . "realtime" . ".txt";
        $temstr = file_get_contents($outputfile);
        $temstr = str_replace("'", "", $temstr);
        $results = json_decode($temstr);
        $rj["total"] = 30;
        $rj["page"] = 1;
        $rj["records"] = 30;
        $rj["rows"] = $results;

       // $temstr = json_decode($rj);



        $this->ajaxReturn($rj, "json");


    }

    //获取预测的降水数据
    public function retrievepcpDataf()
    {
        $id = I("id");
        $type = I("type");
        $guages = explode(",", I("guages"));
        $project = "forcast";
        $series = array();

        $legend = array();
        $model2 = M("hec_pcpguages");
        $names = $model2->field("id", "name")->where("id in(" . I("guages") . ")")->select();
        foreach ($names as $row) {
            $ttt = trim($row["name"], "/");
            $tokens = explode('/', $ttt);
            $legend[] = $tokens[1];
        }

        $nindex = 0;
        foreach ($guages as $guage) {
            //780828_pcp_1.txt
            $filename = C('modeloutput') . "hec/forcast/" . $project . "_pcp_" . $guage . "_" . $id . ".txt";
            $filedata = file_get_contents($filename);
            $filedata = str_replace("'", "\"", $filedata);
            $r = json_decode($filedata);
            if ($type == 1) {
                $ttk["type"] = "line";
            } else {
                $ttk["type"] = "scatter";
            }


            $ttk["name"] = $legend[$nindex];
            $nindex++;
            $ttk["data"] = $r;
            array_push($series, $ttk);
        }
        $rss["series"] = $series;
        $rss["legend"] = $legend;
        $timewidow = "";
        //$rss["dates"] = $this->getDates($project, $timewidow);
        $this->ajaxReturn($rss, "json");
    }


    //获取预测的径流数据
    public function retrieveflowDataf()
    {
        $id = I("id");
        $type = I("type");
        $guages = explode(",", I("guages"));
        $project = "forcast";
        $series = array();

        $legend = array();
        $model2 = M("hec_flowgagues");
        $names = $model2->field("id", "name")->where("id in(" . I("guages") . ")")->select();


        $fc = M('forcast');
        $rs3 = $fc->select();

        foreach ($names as $row) {
            $ttt = trim($row["name"], "/");
            $tokens = explode('/', $ttt);
            $legend[] = $tokens[1];

        }

        $nindex = 0;

        foreach ($guages as $guage) {
            //780828_pcp_1.txt

            $filename = C('modeloutput') . "hec/forcast/" . $project . "_sim_" . $guage . "_" . $id . ".txt";
            $filedata = file_get_contents($filename);
            $filedata = str_replace("'", "\"", $filedata);
            $r = json_decode($filedata);
            if ($type == 1) {
                $ttk["type"] = "line";
            } else {
                $ttk["type"] = "scatter";
            }
            $ttk["data"] = $r;
            $ttk["name"] = $legend[$nindex];
            $nindex++;
            array_push($series, $ttk);


        }
        $rss["series"] = $series;
        $rss["legend"] = $legend;
        $this->ajaxReturn($rss, "json");
    }

    public
    function getDates($projectname, &$timewindow)
    {

        $lines = file(C('hecmodel') . $projectname . "/Control_1.control");

        $strstartdate = trim(preg_split("/:/", $lines[6])[1]);

        $strstarttime = trim(preg_split("/:/", $lines[7])[1]) . ":" . trim(preg_split("/:/", $lines[7])[2]);

        $strenddate = trim(preg_split("/:/", $lines[8])[1]);

        $strendtime = trim(preg_split("/:/", $lines[9])[1]) . ":" . trim(preg_split("/:/", $lines[9])[2]);

        $format = 'Y-m-d H';
        $date = date_create_from_format($format, date("Y-m-d H", strtotime($strstartdate . " " . $strstarttime)));
        $enddate = date_create_from_format($format, date("Y-m-d H", strtotime($strenddate . " " . $strendtime)));
        $timewindow = date("dMY Hi", strtotime($strstartdate . " " . $strstarttime)) . " " . date("dMY Hi", strtotime($strenddate . " " . $strendtime));
        $dates = array();
        array_push($dates, $date->format("Y-m-d H") . ":00");
        while (true) {
            if ($date < $enddate) {
                $date->add(new DateInterval("PT1H"));
                array_push($dates, $date->format("Y-m-d H") . ":00");

            } else {
                break;
            }

        }
        //var_dump($dates);
        //04MAR2003 1400 06APR2004 0900

        return $dates;
    }


}