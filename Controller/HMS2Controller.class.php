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

class HMS2Controller extends ComController
{

    public function generate_current_pcp()
    {
        $project = "forcast";
        $model2 = M("hec_pcpguages");
        $names = $model2->field("id as myid", "name")->select();
        $results = array();
        foreach ($names as $row) {
            $ttt = trim($row["name"], "/");
            $tokens = explode('/', $ttt);
            $filename = C('modeloutput') . "hec/forcast/" . $project . "_pcp_" . $row["id"] . "_" . "realtime" . ".txt";
            $filedata = file_get_contents($filename);
            $filedata = str_replace("]", "", $filedata);
            $filedata = str_replace("[", "", $filedata);
            $values = explode(',', $filedata);
            $time = $values[sizeof($values) - 2];
            $currentvalue = $values[sizeof($values) - 1];
            $addvalue = $values[sizeof($values) - 1] - $values[sizeof($values) - 3];
            $rrow["gagename"] = $tokens[1];
            $rrow["time"] = $time;
            $rrow["currentvalue"] = $currentvalue;
            $rrow["addvalue"] = $addvalue;

            $results[] = $rrow;

        }
        $tempr = json_encode($results);
        $outputfile = C('modeloutput') . "hec/forcast/" . $project . "_pcp_" . "currentvalue" . "_" . "realtime" . ".txt";
        file_put_contents($outputfile, $tempr);
    }

//每分钟产生一个降水数据，通过操作系的计划任务来实现
    public function realtimeinputs()
    {
        $filename = "forcast_realtime";
        $gages = file(C('hecmodel') . "fakeinput/gages.txt");
        $pcps = file(C('hecmodel') . "fakeinput/input.txt");
        $ti = file(C('hecmodel') . "fakeinput/ti.txt");
        //写入到DSS文件当中
        //$type, , $patharray, $idarray, $timewindow,
        $startTime = $ti[0];
        $startTime = trim($startTime);
        $index = $ti[1];
        $dstart_time = date_create_from_format("dMY Hi", $startTime);
        $project = "forcast";
        $file = "input_" . $filename . ".dss";

        $body = file_get_contents(C('hecmodel') . "template/geninputs_body.py");
        $repeat = "";
        $file_content = file_get_contents(C('hecmodel') . "template/geninputs_index.py");
        $hours = 1;
        $cnt = sizeof($gages);
        for ($i = 0; $i < $cnt; $i++) {
            $pcp = "[" . trim($pcps[$index]) . "]";
            $content = $body;
            $content = str_replace("t_part1", "xixi", $content);
            $content = str_replace("t_part2", trim($gages[$i]), $content);
            //echo trim($gages[$i]);
            $content = str_replace("t_part6", "forcast", $content);
            $content = str_replace("t_startTime", $startTime, $content);
            $content = str_replace("t_values", trim($pcp), $content);
            $content = str_replace("\\", "/", $content);
            $repeat = $repeat . $content . "\r\n";
        }
        $dend_time = $dstart_time->add(new DateInterval("PT" . $hours . "H"));
        $startTime = $dend_time->format("dMY Hi");
        $index++;
        $index = $index % sizeof($pcps);
        file_put_contents(C('hecmodel') . "fakeinput/ti.txt", $startTime . "\r\n" . $index);

        $inputfile = C('hecmodel') . $project . "/" . $file;
        $file_content = str_replace("t_pcpfile", $inputfile, $file_content);
        $file_content = str_replace("t_repeat", $repeat, $file_content);
        $str = C('hecsoftroot') . "\r\n";
        $str = $str . "cd " . C('dsssoft') . "\r\n";
        $str = $str . "HEC-DSSVue.cmd " . C('hecmodel') . "/runtime/" . $project . "_realtime_input.py\r\n";
        file_put_contents(C('hecmodel') . "/runtime/" . $project . "_realtime_innput.bat", $str);


        file_put_contents(C('hecmodel') . "/runtime/" . $project . "_realtime_input.py", $file_content);
        $command = C('hecmodel') . "/runtime/" . $project . "_realtime_innput.bat" . " >>E:/php/Apache24/htdocs/wms/Public/hecmodel/runtime/" . $project . "_realtime_input.log.txt";
        // echo $command;
        exec($command, $rs, $st);
        // $this->realtimeeditmodel();
        $this->ajaxReturn("success", "json");
    }


    public function realtimeeditmodel()
    {
        $sheetid = "realtime";
        $project = "forcast";
        $lines = file(C('hecmodel') . "fakeinput/te.txt");
        $start_date = trim($lines[0]);
        $start_time = trim($lines[1]);

        $tempdate = date_create_from_format("d F Y H:i", $start_date . " " . $start_time);
        $tempdate = $tempdate->add(new DateInterval("PT" . trim($lines[2]) . "H"));
        $end_date = $tempdate->format("d F Y");
        $end_time = $tempdate->format("H:i");


        //edit control file
        $controlfile = C('hecmodel') . $project . "/" . "Control_1.control";
        $controlcontent = file_get_contents($controlfile);

        $controlcontent = preg_replace("/(Start Date)(.*)/m", "Start Date: " . $start_date, $controlcontent);
        $controlcontent = preg_replace("/(Start Time)(.*)/m", "Start Time: " . $start_time, $controlcontent);
        $controlcontent = preg_replace("/(End Date)(.*)/m", "End Date: " . $end_date, $controlcontent);
        $controlcontent = preg_replace("/(End Time)(.*)/m", "End Time: " . $end_time, $controlcontent);
        file_put_contents($controlfile, $controlcontent);
        //echo $controlcontent;

        $sdate = date_create_from_format("d F Y", $start_date);

        $edate = date_create_from_format("d F Y", $end_date);

        $gagefile = C('hecmodel') . $project . "/" . "780828.gage";
        $gagecontent = file_get_contents($gagefile);
        $gagecontent = preg_replace("/(DSS File)(.*)/m", "DSS File: " . "input_forcast_" . $sheetid . ".dss", $gagecontent);
        $gagecontent = preg_replace("/(\d{2})(\w{3})(\d{4})( - )(\d{2})(\w{3})(\d{4})/m", $sdate->format("dMY") . " - " . $edate->format("dMY"), $gagecontent);
        $gagecontent = preg_replace("#/780828/#m", "/forcast/", $gagecontent);
        $gagecontent = preg_replace("#(/{1})(DX)(/{1})#m", "/XIXI/", $gagecontent);
        file_put_contents($gagefile, $gagecontent);
        // echo $controlcontent;
        $runfile = C('hecmodel') . $project . "/" . "780828.run";
        $runcontent = file_get_contents($runfile);
        $runcontent = preg_replace("/(DSS File)(.*)/m", "DSS File: " . "output_" . $sheetid . ".dss", $runcontent);
        file_put_contents($runfile, $runcontent);
        $path = C('hecmodel') . $project;
        $this->simulateHMSDetail("780828", $path, "Run 1");

        $this->genallforcastdata($sheetid);
        $this->generate_current_pcp();

        $start_date = trim($lines[0]);
        $start_time = trim($lines[1]);

        $tempdate = date_create_from_format("d F Y H:i", $start_date . " " . $start_time);
        $tempdate = $tempdate->add(new DateInterval("PT" . "1" . "H"));
        $start_date = $tempdate->format("d F Y");
        $start_time = $tempdate->format("H:i");
        file_put_contents(C('hecmodel') . "fakeinput/te.txt", $start_date . "\r\n" . $start_time . "\r\n" . $lines[2]);
    }


    public function index()
    {

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
        $this->assign("s3", $scens[0]["id"]);

        $this->display();
    }

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

        /*        $fc = M('forcast');
                $rs3 = $fc->select();*/
        $this->assign("s3", "realtime");

        $this->display();
    }

    public function runforcast($sheetid)
    {
        $project = "forcast";
        $this->geninputs($sheetid);
        $this->feditmodel($sheetid, $project);
        $this->ajaxReturn("success", "json");

    }

    public function geninputs($sheetid)
    {
        //写入到DSS文件当中
        //$type, , $patharray, $idarray, $timewindow,
        $startTime = date("dMY H", time()) . "00";
        $dstart_time = date_create_from_format("dMY Hi", $startTime);

        $t1 = $dstart_time->format("d F Y");
        $t2 = $dstart_time->format("H:i");

        $model = M("data");
        $project = "forcast";
        $file = "input_" . $sheetid . ".dss";

        $body = file_get_contents(C('hecmodel') . "template/geninputs_body.py");
        $repeat = "";
        $file_content = file_get_contents(C('hecmodel') . "template/geninputs_index.py");
        $hours = 0;
        for ($i = 0; $i < 30; $i++) {
            $k = $i + 1;
            $rs = $model->field("parsed")->where("sheetid=" . $sheetid . " and parsed is not null and columnid=" . $k)->order("rowid asc")->select();
            $values = array();
            $index = 0;
            $hours = max($hours, sizeof($rs) - 1);
            foreach ($rs as $row) {
                if ($index != 0) {

                    array_push($values, $row["parsed"]);
                }
                $index++;
            }

            $pcp = "[" . implode(",", $values) . "]";

            $content = $body;
            $content = str_replace("t_part1", "xixi", $content);
            $content = str_replace("t_part2", $rs[0]["parsed"], $content);
            $content = str_replace("t_part6", "forcast", $content);
            $content = str_replace("t_startTime", $startTime, $content);
            $content = str_replace("t_values", $pcp, $content);
            $content = str_replace("\\", "/", $content);
            $repeat = $repeat . $content . "\r\n";

        }

        $dend_time = $dstart_time->add(new DateInterval("PT" . $hours . "H"));
        $t3 = $dend_time->format("d F Y");
        $t4 = $dend_time->format("H:i");


        $fc = M('forcast');
        $fc_row["sheetid"] = $sheetid;
        $fc_row["start_date"] = $t1;
        $fc_row["start_time"] = $t2;
        $fc_row["end_date"] = $t3;
        $fc_row["end_time"] = $t4;

        $rs = $fc->where("sheetid=" . $sheetid)->find();

        if ($rs != null) {
            $fc->save($fc_row);
        } else {
            $fc->add($fc_row);
        }

        $inputfile = C('hecmodel') . $project . "/" . $file;
        $file_content = str_replace("t_pcpfile", $inputfile, $file_content);
        $file_content = str_replace("t_repeat", $repeat, $file_content);
        $str = C('hecsoftroot') . "\r\n";
        $str = $str . "cd " . C('dsssoft') . "\r\n";
        $str = $str . "HEC-DSSVue.cmd " . C('hecmodel') . "/runtime/" . $project . "_input.py\r\n";
        file_put_contents(C('hecmodel') . "/runtime/" . $project . "_innput.bat", $str);


        file_put_contents(C('hecmodel') . "/runtime/" . $project . "_input.py", $file_content);
        $command = C('hecmodel') . "/runtime/" . $project . "_innput.bat" . " >>E:/php/Apache24/htdocs/wms/Public/hecmodel/runtime/" . $project . "_input.log.txt";
        // echo $command;
        exec($command, $rs, $st);
    }

    public function feditmodel($sheetid, $project)
    {
        $fc = M('forcast');
        $rs = $fc->where("sheetid=" . $sheetid)->find();
        $start_date = $rs["start_date"];
        $start_time = $rs["start_time"];
        $end_date = $rs["end_date"];
        $end_time = $rs["end_time"];
        //edit control file
        $controlfile = C('hecmodel') . $project . "/" . "Control_1.control";
        $controlcontent = file_get_contents($controlfile);

        $controlcontent = preg_replace("/(Start Date)(.*)/m", "Start Date: " . $start_date, $controlcontent);
        $controlcontent = preg_replace("/(Start Time)(.*)/m", "Start Time: " . $start_time, $controlcontent);
        $controlcontent = preg_replace("/(End Date)(.*)/m", "End Date: " . $end_date, $controlcontent);
        $controlcontent = preg_replace("/(End Time)(.*)/m", "End Time: " . $end_time, $controlcontent);
        file_put_contents($controlfile, $controlcontent);
        //echo $controlcontent;

        $sdate = date_create_from_format("d F Y", $start_date);

        $edate = date_create_from_format("d F Y", $end_date);

        $gagefile = C('hecmodel') . $project . "/" . "780828.gage";
        $gagecontent = file_get_contents($gagefile);
        $gagecontent = preg_replace("/(DSS File)(.*)/m", "DSS File: " . "input_" . $sheetid . ".dss", $gagecontent);
        $gagecontent = preg_replace("/(\d{2})(\w{3})(\d{4})( - )(\d{2})(\w{3})(\d{4})/m", $sdate->format("dMY") . " - " . $edate->format("dMY"), $gagecontent);
        $gagecontent = preg_replace("#/780828/#m", "/forcast/", $gagecontent);
        $gagecontent = preg_replace("#(/{1})(DX)(/{1})#m", "/XIXI/", $gagecontent);
        file_put_contents($gagefile, $gagecontent);
        // echo $controlcontent;
        $runfile = C('hecmodel') . $project . "/" . "780828.run";
        $runcontent = file_get_contents($runfile);
        $runcontent = preg_replace("/(DSS File)(.*)/m", "DSS File: " . "output_" . $sheetid . ".dss", $runcontent);
        file_put_contents($runfile, $runcontent);
        $path = C('hecmodel') . $project;
        $this->simulateHMSDetail("780828", $path, "Run 1");

        $this->genallforcastdata($sheetid);


    }

    public function getRows()
    {


        $page = I("page");
        $rows = I("rows");

        $model = M("hecprojects");
        $scens = $model->field("startTime", "endTime", "projectname", "id")->order("id desc")->page($page, $rows)->select();


        $count = $model->count();
        if (($count % $rows) > 0) {
            $kk["total"] = intval($count / $rows) + 1;
        } else {
            $kk["total"] = intval($count / $rows);
        }

        $kk["page"] = $page;
        $kk["records"] = $count;
        $kk["rows"] = $scens;

        $this->ajaxReturn($kk, "json");
    }


    public function simulateHMS()
    {
        /*c:
        cd C:\HEC\HEC-HMS\4.0
        hec-hms.cmd -s E:\jjedss\hec\runhec.script*/

        /*from hms.model.JythonHms import *
        OpenProject("730702","E:/jjedss/hec/730702")
        Compute("Run 1")
        Exit(1)*/
        $project = "780828";
        $str = C('hecsoftroot') . "\r\n";
        $str = $str . "cd " . C('hecsoft') . "\r\n";
        $str = $str . "hec-hms.cmd -s " . C('hecmodel') . "run.py\r\n";
        file_put_contents(C('hecmodel') . "run.bat", $str);

        $str2 = "from hms.model.JythonHms import *" . "\r\n";
        $str2 = $str2 . 'OpenProject("' . $project . '","' . C('hecmodel') . 'anmax/' . $project . '")' . "\r\n";
        $str2 = $str2 . 'Compute("Run 1")' . "\r\n";
        $str2 = $str2 . 'Exit(1)' . "\r\n";

        file_put_contents(C('hecmodel') . "run.py", $str2);
        $command = C('hecmodel') . "run.bat >>log.txt";
        echo $command;
        exec($command, $rs, $st);

    }

    public function simulateHMSDetail($project, $path, $runname)
    {
        /*c:
        cd C:\HEC\HEC-HMS\4.0
        hec-hms.cmd -s E:\jjedss\hec\runhec.script*/

        /*from hms.model.JythonHms import *
        OpenProject("730702","E:/jjedss/hec/730702")
        Compute("Run 1")
        Exit(1)*/

        $str = C('hecsoftroot') . "\r\n";
        $str = $str . "cd " . C('hecsoft') . "\r\n";
        $str = $str . "hec-hms.cmd -s " . C('hecmodel') . "/runtime/" . $project . "_run.py\r\n";
        file_put_contents(C('hecmodel') . "/runtime/" . $project . "_run.bat", $str);


        $body = file_get_contents(C('hecmodel') . "template/runhec.py");
        $body = str_replace("project_name", $project, $body);
        $body = str_replace("project_path", $path, $body);
        $body = str_replace("run_name", $runname, $body);

        file_put_contents(C('hecmodel') . "/runtime/" . $project . "_run.py", $body);
        $command = C('hecmodel') . "/runtime/" . $project . "_run.bat" . " >>E:/php/Apache24/htdocs/wms/Public/hecmodel/runtime/" . $project . "_run.log.txt";

        //echo $command;
        exec($command, $rs, $st);

    }

    public function retrievepcpData()
    {
        $id = I("id");
        $type = I("type");
        $guages = explode(",", I("guages"));
        $model = M("hecprojects");
        $rs = $model->where("id=" . $id)->find();
        $project = $rs["projectname"];
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
            $filename = C('modeloutput') . "hec/" . $project . "_pcp_" . $guage . ".txt";
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
        $rss["dates"] = $this->getDates($project, $timewidow);
        $this->ajaxReturn($rss, "json");
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
        $rss["dates"] = $this->getDates($project, $timewidow);
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
            foreach ($rs3 as $item) {
                $legend[] = $tokens[1] . "_" . $item["sheetid"];
            }
        }

        $nindex = 0;

        foreach ($guages as $guage) {
            //780828_pcp_1.txt
            foreach ($rs3 as $item) {
                $filename = C('modeloutput') . "hec/forcast/" . $project . "_sim_" . $guage . "_" . $item["sheetid"] . ".txt";
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
        }
        $rss["series"] = $series;
        $rss["legend"] = $legend;
        $this->ajaxReturn($rss, "json");
    }

    public function retrieveflowData()
    {
        $id = I("id");
        $type = I("type");
        $guages = explode(",", I("guages"));
        $model = M("hecprojects");
        $rs = $model->where("id=" . $id)->find();
        $project = $rs["projectname"];
        $series = array();

        $legend = array();
        $model2 = M("hec_flowgagues");
        $names = $model2->field("id", "name")->where("id in(" . I("guages") . ")")->select();
        foreach ($names as $row) {
            $ttt = trim($row["name"], "/");
            $tokens = explode('/', $ttt);
            $legend[] = $tokens[1] . "_OBS";
            $legend[] = $tokens[1] . "_SIM";
        }

        $nindex = 0;

        foreach ($guages as $guage) {
            //780828_pcp_1.txt
            $filename = C('modeloutput') . "hec/" . $project . "_obs_" . $guage . ".txt";
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

            $filename = C('modeloutput') . "hec/" . $project . "_sim_" . $guage . ".txt";
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
    function retrieveData()
    {
        /*c:
          cd C:\HEC\HEC-DSSVue\
          HEC-DSSVue.cmd E:/php/Apache24/htdocs/wms/Public/hecmodel/retrieve.py
        */
        $id = I("id");
        $model = M("hecprojects");
        $rs = $model->where("id=" . $id)->find();
        $project = $rs["projectname"];

        if (!file_exists(C('hecmodel') . $rs["projectname"] . ".txt")) {

            $str = C('hecsoftroot') . "\r\n";
            $str = $str . "cd " . C('dsssoft') . "\r\n";
            $str = $str . "HEC-DSSVue.cmd " . C('hecmodel') . "retrieve.py\r\n";
            file_put_contents(C('hecmodel') . "retrieve.bat", $str);

            $str2 = file_get_contents(C('hecmodel') . "template.py");
            $str2 = str_replace("outputname", $rs["outputname"], $str2);
            $str2 = str_replace("outputpath1", $rs["outputpath"], $str2);
            $str2 = str_replace("outputpath2", $rs["outputpath2"], $str2);
            $str2 = str_replace("outputpath3", $rs["outputpath3"], $str2);
            $str2 = str_replace("outputtxtfile", C('hecmodel') . $rs["projectname"] . ".txt", $str2);
            $str2 = str_replace("outputjpgfile", C('hecmodel') . $rs["projectname"] . ".jpg", $str2);
            $str2 = str_replace("\\", "/", $str2);


            file_put_contents(C('hecmodel') . "retrieve.py", $str2);
            $command = C('hecmodel') . 'retrieve.bat >>E:\php\Apache24\htdocs\wms\Public\hecmodel\log2.txt';
            // echo $command;
            exec($command, $rs, $st);
        }
        $filedata = file_get_contents(C('hecmodel') . $rs["projectname"] . ".txt");
        $filedata = str_replace("'", "\"", $filedata);
        $r = json_decode($filedata);
        $r = (array)$r;
        $format = 'Y/m/d/H';
        $date = date_create_from_format($format, $r["startTime"]);
        $dates = array();
        for ($i = 0; $i < count($r["data"][1]); $i++) {

            $date->add(new DateInterval("PT1H"));
            # $dates[$i] = $date->format("Y-m-d H");
            array_push($dates, $date->format("Y-m-d H"));
        }
        # var_dump($dates);
        $series = array();
        foreach ($r["data"] as $item) {
            $ttk["type"] = "line";
            $ttk["data"] = $item;
            array_push($series, $ttk);
        }
        $rss["dates"] = $dates;
        $rss["series"] = $series;

        $this->ajaxReturn($rss, "json");
        // echo $r["series"];
    }


    public
    function retrieveData2()
    {
        /*c:
          cd C:\HEC\HEC-DSSVue\
          HEC-DSSVue.cmd E:/php/Apache24/htdocs/wms/Public/hecmodel/retrieve.py
        */
        $id = I("id");
        $model = M("hecprojects");
        $rs = $model->where("id=" . $id)->find();

        if (!file_exists(C('hecmodel') . $rs["projectname"] . "_pcp.txt")) {
            $model2 = M("hec_pcpguages");
            $rs2 = $model2->select();
            $timewindow = "";
            //$rss["dates"] = $this->getDates($rs["projectname"],$timewindow);
            $repeat = file_get_contents(C('hecmodel') . "template_repeat.py");
            $index = 0;
            $body = "";
            foreach ($rs2 as $item) {
                $pcppathname = $item["name"] . $rs["inputname"];

                if ($index == 0) {
                    $body = $body . str_replace("outputpath", $pcppathname, $repeat) . "\r\n";
                } else {
                    $body = $body . "file_object.write(\",\")\r\n" . str_replace("outputpath", $pcppathname, $repeat) . "\r\n";
                }
                if ($index > 8) {
                    break;
                }
                $index++;
            }


            $content = file_get_contents(C('hecmodel') . "template2.py");

            $inputfile = C('hecmodel') . $rs["projectname"] . "/" . $rs["inputfile"];


            $outputfile = C('hecmodel') . $rs["projectname"] . "_pcp.txt";


            $content = str_replace("pcpfile", $inputfile, $content);
            $content = str_replace("outputtxtfile", $outputfile, $content);
            $content = str_replace("timewindow", $timewindow, $content);
            $content = str_replace("repeatpart", $body, $content);

            $content = str_replace("\\", "/", $content);

            $project = $rs["projectname"];
            $str = C('hecsoftroot') . "\r\n";
            $str = $str . "cd " . C('dsssoft') . "\r\n";
            $str = $str . "HEC-DSSVue.cmd " . C('hecmodel') . "getpcp.py\r\n";
            file_put_contents(C('hecmodel') . "retrieve_pcp.bat", $str);


            file_put_contents(C('hecmodel') . "getpcp.py", $content);
            $command = C('hecmodel') . 'retrieve_pcp.bat >>E:\php\Apache24\htdocs\wms\Public\hecmodel\log2.txt';
            // echo $command;
            exec($command, $rs, $st);
        }
        $filedata = file_get_contents(C('hecmodel') . $rs["projectname"] . "_pcp.txt");
        $filedata = str_replace("'", "\"", $filedata);
        $r = json_decode($filedata);
        $r = (array)$r;


        $rss["series"] = $r["series"];

        $this->ajaxReturn($rss, "json");
        // echo $r["series"];
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

    public
    function getTW($projectname)
    {

        $lines = file(C('hecmodel') . $projectname . "/Control_1.control");

        $strstartdate = trim(preg_split("/:/", $lines[6])[1]);

        $strstarttime = trim(preg_split("/:/", $lines[7])[1]) . ":" . trim(preg_split("/:/", $lines[7])[2]);

        $strenddate = trim(preg_split("/:/", $lines[8])[1]);

        $strendtime = trim(preg_split("/:/", $lines[9])[1]) . ":" . trim(preg_split("/:/", $lines[9])[2]);

        $format = 'Y-m-d H';
        $timewindow = date("dMY Hi", strtotime($strstartdate . " " . $strstarttime)) . " " . date("dMY Hi", strtotime($strenddate . " " . $strendtime));

        //var_dump($dates);
        //04MAR2003 1400 06APR2004 0900

        return $timewindow;
    }

    function genallforcastdata($sheetid)
    {
        $model2 = M("hec_pcpguages");
        $rs2 = $model2->select();
        $project = "forcast";
        $timewindow = $this->getTW($project);

        $tokens = preg_split("/[\s]+/", $timewindow);


        $type = "pcp";
        $file = "input_" . $project . "_" . $sheetid . ".dss";
        $patharray = array();
        $idarray = array();
        foreach ($rs2 as $item) {
            $nameparts = preg_split("#/{1}#", $item["name"]);
            $pcppathname = "/XIXI/" . $nameparts[2] . "/PRECIP-INC/" . $tokens[0] . " - " . $tokens[2] . "/1HOUR/FORCAST/";
            $patharray[] = $pcppathname;
            $idarray[] = $item["id"];
        }

        $this->commongendataf($type, $file, $patharray, $idarray, $timewindow, $project, $sheetid);

        $model3 = M("hec_flowgagues");
        $rs3 = $model3->select();
        $file = "output_" . $sheetid . ".dss";
        $type = "sim";
        $patharray = array();
        $idarray = array();
        foreach ($rs3 as $item) {
            $pcppathname = $item["oname"] . "FLOW/" . $tokens[0] . " - " . $tokens[2] . "/1HOUR/RUN:RUN 1/";
            $patharray[] = $pcppathname;
            $idarray[] = $item["id"];
        }
        $this->commongendataf($type, $file, $patharray, $idarray, $timewindow, $project, $sheetid);
    }



//type类型：pcp降水，sim模拟洪水
//file dss文件路径和名称
//Patharray dss 文件中数据存放了路径（6个部分构成的路径）的数组
//Patharray 站点ID数组
//获取时间窗口
    function commongendataf($type, $file, $patharray, $idarray, $timewindow, $project, $sheetid)
    {

        // $this->getDates($project, $timewindow);
        $repeat = file_get_contents(C('hecmodel') . "template/genalldata_body.py");
        $index = 0;
        $body = "";
        foreach ($patharray as $item) {
            $tempstr = $repeat;
            $pcppathname = $item;
            $filename = $project . "_" . $type . "_" . $idarray[$index] . "_" . $sheetid . ".txt";
            $tempstr = str_replace("outputpath", $pcppathname, $tempstr) . "\r\n";
            $outputfile = C('modeloutput') . "hec/forcast/" . $filename;
            $body = $body . str_replace("outputtxtfile", $outputfile, $tempstr) . "\r\n";
            $index++;
        }


        $content = file_get_contents(C('hecmodel') . "template/genalldata_index.py");
        $inputfile = C('hecmodel') . $project . "/" . $file;

        $content = str_replace("pcpfile", $inputfile, $content);
        $content = str_replace("timewindow", $timewindow, $content);
        $content = str_replace("repeatpart", $body, $content);

        $content = str_replace("\\", "/", $content);


        $str = C('hecsoftroot') . "\r\n";
        $str = $str . "cd " . C('dsssoft') . "\r\n";
        $str = $str . "HEC-DSSVue.cmd " . C('hecmodel') . "/runtime/" . $project . ".py\r\n";
        file_put_contents(C('hecmodel') . "/runtime/" . $project . "_output.bat", $str);


        file_put_contents(C('hecmodel') . "/runtime/" . $project . ".py", $content);
        $command = C('hecmodel') . "/runtime/" . $project . "_output.bat" . " >>E:/php/Apache24/htdocs/wms/Public/hecmodel/runtime/" . $project . "_output.log.txt";
        // echo $command;
        exec($command, $rs, $st);

    }

}