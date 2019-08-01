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

class SWATController extends ComController
{

    public function index2()
    {
        $this->display("scenlist");
    }


    public function getlist()
    {
        $page = I("page");
        $rows = I("rows");
        $model = M('scen_detail');
        $scens = $model->field("id,name,ca_type,pa_type,la_type,pcp_name,tmp_name,ps_names,lu_names,exec_type")->order("id desc")->page($page, $rows)->select();
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


    public function index()
    {
        $data["s1_applied"] = 1;
        $data["s3_applied"] = 1;
        $data["s3_applied"] = 2;

        $model = M("pointsource");
        $ps = $model->field("id, name")->order("id desc")->select();

        $this->assign("duallist1", $ps);


        $model = M('landuse');
        $lucc = $model->field("id,name")->order("id desc")->select();

        $this->assign("duallist2", $lucc);


        $pm_scen = M("meteorology_scen");
        $tm_scen = M("temp_scen");
        $pscens = $pm_scen->field('scen_id,scen_name')->select();
        $tscens = $tm_scen->field('scen_id,scen_name')->select();
        $this->assign('s1_pcp', $pscens);
        $this->assign('s1_tmp', $tscens);
        $this->display();
    }


    public function insert()
    {
        $data["ca_type"] = I('s1_applied');
        $data["pcp_id"] = I('s1_pcp');
        $data["tmp_id"] = I('s1_tmp');

        if ($data["pcp_id"] != null or $data["pcp_id"] != "") {
            $pm_scen = M("meteorology_scen");
            $pscens = $pm_scen->field('scen_name')->where("scen_id=" . $data["pcp_id"])->find();
            $data["pcp_name"] = $pscens["scen_name"];
        }

        if ($data["tmp_id"] != null or $data["tmp_id"] != "") {
            $temp_scen = M("temp_scen");
            $tsens = $temp_scen->field('scen_name')->where("scen_id=" . $data["tmp_id"])->find();
            $data["tmp_name"] = $tsens["scen_name"];
        }

        $data["pa_type"] = I('s2_applied');
        $data["la_type"] = I('s3_applied');
        $data["exec_type"] = I('s4_exec_type');
        $data["exec_type"] = 2;
        $data["gendata_type"] = I('s4_gendata_type');
        $data["name"] = I('simulate_name');
        $data["ps_ids"] = implode(",", I('zdj1'));
        $data["lu_ids"] = implode(",", I('zdj2'));

        if ($data["ps_ids"] != null or $data["ps_ids"] != "") {
            $model = M("pointsource");
            $ps = $model->field("name")->where("id in (" . $data["ps_ids"] . ")")->select();
            $data["ps_names"] = implode(",", array_column($ps, "name"));
        }
        if ($data["lu_ids"] != null or $data["lu_ids"] != "") {
            $model = M('landuse');
            $lucc = $model->field("name")->where("id in (" . $data["lu_ids"] . ")")->select();
            $data["lu_names"] = implode(",", array_column($lucc, "name"));
        }
        $model = M('scen_detail');
        $model->add($data);
        $this->success('操作成功！', U('SWAT/scenlist'));
    }

    public function simulate($scen)
    {
        //http://localhost/wms/qwadmin/SWAT/simulate.html?scen=2

        $str=C('modelroot')."\r\n";
        $str=$str."cd ".C('modelpath')."\r\n";
        $str=$str."sswat.exe ".str_pad($scen, 5, "0", STR_PAD_LEFT) . " " . C('modeloutput')."\r\n";
        file_put_contents(C('modelpath') . "run_swat.bat ",$str);
        $command = C('modelpath') . "run_swat.bat >>abc.log";
        echo $command;
        exec($command, $rs, $st);

    }

    //综合模拟
    public function execscen()
    {
        $restorearray = array();
        $scen_id = I('id');
        $model = M('scen_detail');
        $scens = $model->field("id,ca_type,pa_type,la_type,pcp_id,tmp_id,ps_ids,lu_ids,name")->where("id=" . $scen_id)->find();
        //1、生成气象相关的model.in文件
        /* 两者都不应用        仅应用降水情景        仅应用气温情景        两者都应用 */

        if ($scens["ca_type"] == 2) {
            $modelin = "p_" . $scens["pcp_id"] . "_model_in.txt";
            $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
            exec($command);
            $this->genprec($scens["pcp_id"]);


        } else if ($scens["ca_type"] == 3) {
            $modelin = "t_" . $scens["tmp_id"] . "_model_in.txt";
            $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
            exec($command);
            $this->gentempture($scens["tmp_id"]);

        } else if ($scens["ca_type"] == 4) {
            //合并两个model.in
            $modelin1 = "p_" . $scens["pcp_id"] . "_model_in.txt";
            $modelin2 = "t_" . $scens["tmp_id"] . "_model_in.txt";
            $modelin = "c_" . $scens["id"] . "_model_in.txt";
            $str1 = file_get_contents(C('modelpath') . 'template\\' . $modelin1);
            $str2 = file_get_contents(C('modelpath') . 'template\\' . $modelin2);
            file_put_contents(C('modelpath') . 'template\\' . $modelin, $str1 . "\r\n" . $str2);
            $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
            exec($command);
            $this->genprec($scens["pcp_id"]);
            $this->gentempture($scens["tmp_id"]);
        }

        //2、執行點源操作
        //2.1    获取fig.fig文件中存储单元最大编号

        if ($scens["pa_type"] == 2) {
            $figfile = C('modelpath') . "Backup\\fig.fig";
            $lines = file($figfile);
            for ($i = count($lines) - 1; $i > 0; $i--) {
                if (startsWith($lines[$i], "route")) {
                    $tokens = preg_split("/[\s]+/", $lines[$i]);
                    $su = $tokens[2] * 1;
                    break;
                }
            }
            //2.1    获取fig.fig文件中最大编号
            for ($i = count($lines) - 1; $i > 0; $i--) {
                if (startsWith($lines[$i], "rec")) {
                    $tokens = preg_split("/[\s]+/", $lines[$i]);
                    $fnum = $tokens[3] * 1;
                    break;
                }
            }
            $tokens = preg_split("/[\s]+/", $scens["ps_ids"]);
            $model = M("pointsource");
            $rows = $model->where("id in(" . $scens["ps_ids"] . ")")->select();


            foreach ($rows as $row) {

                switch ($row["ps_type"]) {
                    case 0:
                        $command = "reccnst";
                        $command_code = 11;
                        break;
                    case 1:
                        $command = "reccnst";
                        $command_code = 11;
                        break;
                    case 2:
                        $command = "rechour";
                        $command_code = 6;
                        break;
                    case 3:
                        $command = "recday";
                        $command_code = 10;
                        break;
                    case 4:
                        $command = "recmon";
                        $command_code = 7;
                        break;
                    case 5:
                        $command = "recyear";
                        $command_code = 8;
                        break;


                }

                for ($i = count($lines) - 1; $i > 0; $i--) {
                    if (startsWith($lines[$i], "route")) {
                        $tokens = preg_split("/[\s]+/", $lines[$i]);
                        if ($tokens[3] == $row["subbasin_id"]) {
                            $isu = $tokens[4];
                            $pos = $i;
                            break;
                        }
                    }
                }

                $finename = $row["file_name"];
                $su++;
                $fnum++;
                $rs = $this->createps_config($isu, $su, $command, $command_code, $fnum, $finename);
                $su++;
                $lines[$pos] = substr($lines[$pos], 0, 28) . str_pad($su, 6, " ", STR_PAD_LEFT) . "\r\n";

                array_splice($lines, $pos, 0, $rs);

                copy(C('modelpath') . "ps_template\\" . $finename, C('modelpath') . $finename);
            }
            file_put_contents(C('modelpath') . "ps_template\\fig_" . $scens["id"] . ".fig", $lines);
            copy(C('modelpath') . "ps_template\\fig_" . $scens["id"] . ".fig", C('modelpath') . "fig.fig");
        }

        //3、執行土地利用情景
        if ($scens["la_type"] == 2) {
            $tokens = preg_split("/[\s]+/", $scens["lu_ids"]);
            $model2 = M("landuse");
            $hru = M("hru");
            $rows = $model2->where("id in(" . $scens["lu_ids"] . ")")->select();
            foreach ($rows as $row) {
                $sfid = $hru->field("fid")->where("id=" . $row["shru_id"])->find();
                $sfid = $sfid["fid"];

                $tfid = $hru->field("fid")->where("id=" . $row["thru_id"])->find();
                $tfid = $tfid["fid"];

                $source_hru_name = str_pad($row["sub_id"], 5, "0", STR_PAD_LEFT) . str_pad($sfid, 4, "0", STR_PAD_LEFT) . ".hru";
                $target_hru_name = str_pad($row["sub_id"], 5, "0", STR_PAD_LEFT) . str_pad($tfid, 4, "0", STR_PAD_LEFT) . ".hru";
                //HRU_FR
                $sfv = $row["sf"] / 100;
                $tfv = $row["tf"] / 100;
                $this->edithru($source_hru_name, $sfv);
                $this->edithru($target_hru_name, $tfv);

                array_push($restorearray, $source_hru_name);
                array_push($restorearray, $target_hru_name);

            }


        }

        //4、更新状态


        $genscen = M('scenario');
        $scendata["scen_name"] = $scens["name"];
        $scendata["scen_type"] = 2;
        $scen_id_total = $genscen->add($scendata);

        $model = M('scen_detail');
        $scens["exec_type"] = 4;
        $scens["id"] = $scen_id;
        $scens["scen_id_fk"] = $scen_id_total;
        $model->save($scens);
        //5、run swat

        $this->simulate($scen_id_total);


        //6、恢復model數據

        $this->restrore($restorearray);


        echo "success";

    }

    public function restrore($filearray)
    {

        $sourcepath = C('modelpath') . "Backup\\";
        $filearray[] = "pcp1.pcp";
        $filearray[] = "tmp1.tmp";
        $filearray[] = "fig.fig";

        foreach ($filearray as $item) {
            copy($sourcepath . $item, C('modelpath') . $item);
        }


    }

    public function edithru($filename, $value)
    {
        $slines = file(C("modelpath") . $filename);
        $str = substr($slines[1], 16);
        $slines[1] = str_pad($value, 16, " ", STR_PAD_LEFT) . $str;
        $str = implode("", $slines);
        file_put_contents(C("modelpath") . "hru_template\\" . $filename, $str);
        copy(C("modelpath") . "hru_template\\" . $filename, C("modelpath") . $filename);
    }

    public
    function createps_config($isu, $su, $command, $command_code, $fnum, $finename)
    {
        /*recmon         7   118     1             0.00
        Pnt.Source      10p.dat
        add            5   119    10   118*/
        $da = "0.00";
        $line = array();
        $line[] = str_pad($command, 10) .
            str_pad($command_code, 6, " ", STR_PAD_LEFT) .
            str_pad($su, 6, " ", STR_PAD_LEFT) .
            str_pad($fnum, 6, " ", STR_PAD_LEFT) .
            str_pad($da, 17, " ", STR_PAD_LEFT) . "\r\n";
        $line[] = "Pnt.Source" . str_pad($finename, 13, " ", STR_PAD_LEFT) . "\r\n";
        $line[] = str_pad("add", 10) .
            str_pad("5", 6, " ", STR_PAD_LEFT) .
            str_pad($su + 1, 6, " ", STR_PAD_LEFT) .
            str_pad($isu, 6, " ", STR_PAD_LEFT) .
            str_pad($su, 6, " ", STR_PAD_LEFT) . "\r\n";

        return $line;

    }


    public
    function genprec2()
    {
        //http://localhost/wms/qwadmin/SWAT/genprec.html?scen=3
        //http://localhost/wms/qwadmin/SWAT/genprec.html?scen=4
        $scen = $_GET["scen"];
        $filename = C("modelpath") . "pcp1.pcp";
        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        $sdate = date_create_from_format("Y-m-d", C("sdate"));

        $edate = date_create_from_format("Y-m-d", C("edate"));
        $day_count = date_diff($sdate, $edate);
        $spos = count($lines) - $day_count->days - 1;
        $guages = Array();

        for ($i = 0; $i < 34; $i++) {
            $guages[$i] = Array();
        }
        $line_count = count($lines);
        for ($v = $spos; $v < $line_count; $v++) {
            //echo $lines[$v];
            for ($j = 7; $j < strlen($lines[$v]); $j = $j + 5) {
                $idx = ($j - 7) / 5;
                $t = substr($lines[$v], $j, 5);


                array_push($guages[$idx], "," . (1.2 * floatval(ltrim($t, '0'))));
            }
        }
        for ($i = 0; $i < 34; $i++) {
            $guages[$i][0] = "[" . substr($guages[$i][0], 1);
            $size = sizeof($guages[$i]);
            $guages[$i][$size - 1] = $guages[$i][$size - 1] . "]";
            file_put_contents(C('modeloutput') . $scen . "_" . ($i + 100) . "_7.json", $guages[$i]);
        }
    }

    public
    function gentempture2()
    {
        //http://localhost/wms/qwadmin/SWAT/gentempture.html?scen=5
        //http://localhost/wms/qwadmin/SWAT/gentempture.html?scen=6
        $scen = $_GET["scen"];
        $filename = C("modelpath") . "tmp1.tmp";
        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        $sdate = date_create_from_format("Y-m-d", C("sdate"));

        $edate = date_create_from_format("Y-m-d", C("edate"));
        $day_count = date_diff($sdate, $edate);
        $spos = count($lines) - $day_count->days - 1;
        $guages = Array();

        for ($i = 0; $i < 4; $i++) {
            $guages[$i] = Array();
        }
        $line_count = count($lines);
        for ($v = $spos; $v < $line_count; $v++) {
            //echo $lines[$v];
            for ($j = 7; $j < strlen($lines[$v]); $j = $j + 5) {
                $idx = ($j - 7) / 5;
                $t = substr($lines[$v], $j, 5);


                array_push($guages[$idx], "," . (2 + floatval(ltrim($t, '0'))));
            }
        }
        for ($i = 0; $i < 4; $i++) {
            $guages[$i][0] = "[" . substr($guages[$i][0], 1);
            $size = sizeof($guages[$i]);
            $guages[$i][$size - 1] = $guages[$i][$size - 1] . "]";
            $tidx = "max";
            if ($i % 2 == 0) {
                $tidx = "max";
            } else {
                $tidx = "min";
            }
            file_put_contents(C('modeloutput') . $tidx . $scen . "_" . (intval($i / 2) + 134) . "_8.json", $guages[$i]);
        }
    }

    public function gentempture($scen)
    {

        $tmpmodel = M('temp_scen');


        $rs = $tmpmodel->where("scen_id=" . $scen)->find();

        if ($rs["idata"] == 0) {
            $data["scen_id"] = $scen;
            $data["idata"] = 1;
            $tmpmodel->save($data);

            //http://localhost/wms/qwadmin/SWAT/gentempture.html?scen=5
            //http://localhost/wms/qwadmin/SWAT/gentempture.html?scen=6
            //$scen = $_GET["scen"];
            $filename = C("modelpath") . "tmp1.tmp";
            $lines = file($filename, FILE_IGNORE_NEW_LINES);
            $sdate = date_create_from_format("Y-m-d", C("sdate"));

            $edate = date_create_from_format("Y-m-d", C("edate"));
            $day_count = date_diff($sdate, $edate);
            $spos = count($lines) - $day_count->days - 1;
            $guages = Array();

            for ($i = 0; $i < 4; $i++) {
                $guages[$i] = Array();
            }
            $line_count = count($lines);
            for ($v = $spos; $v < $line_count; $v++) {
                //echo $lines[$v];
                for ($j = 7; $j < strlen($lines[$v]); $j = $j + 5) {
                    $idx = ($j - 7) / 5;
                    $t = substr($lines[$v], $j, 5);


                    array_push($guages[$idx], "," . (2 + floatval(ltrim($t, '0'))));
                }
            }
            for ($i = 0; $i < 4; $i++) {
                $guages[$i][0] = "[" . substr($guages[$i][0], 1);
                $size = sizeof($guages[$i]);
                $guages[$i][$size - 1] = $guages[$i][$size - 1] . "]";
                $tidx = "max";
                if ($i % 2 == 0) {
                    $tidx = "max";
                } else {
                    $tidx = "min";
                }
                file_put_contents(C('modeloutput') . $tidx . $scen . "_" . (intval($i / 2) + 134) . "_8.json", $guages[$i]);
            }

        }
    }

    public static function genprec($scen)
    {
        //http://localhost/wms/qwadmin/SWAT/genprec.html?scen=3
        //http://localhost/wms/qwadmin/SWAT/genprec.html?scen=4
        // $scen = $_GET["scen"];
        $pcpmodel = M('meteorology_scen');


        $rs = $pcpmodel->where("scen_id=" . $scen)->find();

        if ($rs["idata"] == 0) {
            $data["scen_id"] = $scen;
            $data["idata"] = 1;
            $pcpmodel->save($data);
            $filename = C("modelpath") . "pcp1.pcp";
            $lines = file($filename, FILE_IGNORE_NEW_LINES);
            $sdate = date_create_from_format("Y-m-d", C("sdate"));

            $edate = date_create_from_format("Y-m-d", C("edate"));
            $day_count = date_diff($sdate, $edate);
            $spos = count($lines) - $day_count->days - 1;
            $guages = Array();

            for ($i = 0; $i < 34; $i++) {
                $guages[$i] = Array();
            }
            $line_count = count($lines);
            for ($v = $spos; $v < $line_count; $v++) {
                //echo $lines[$v];
                for ($j = 7; $j < strlen($lines[$v]); $j = $j + 5) {
                    $idx = ($j - 7) / 5;
                    $t = substr($lines[$v], $j, 5);


                    array_push($guages[$idx], "," . (1.2 * floatval(ltrim($t, '0'))));
                }
            }
            for ($i = 0; $i < 34; $i++) {
                $guages[$i][0] = "[" . substr($guages[$i][0], 1);
                $size = sizeof($guages[$i]);
                $guages[$i][$size - 1] = $guages[$i][$size - 1] . "]";
                file_put_contents(C('modeloutput') . $scen . "_" . ($i + 100) . "_7.json", $guages[$i]);
            }

        }
    }

    public function execpcp()
    {
        $pcp_id = I('id');
        $modelin = "p_" . $pcp_id . "_model_in.txt";
        $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
        exec($command);
        $this->genprec($pcp_id);
        $pcpmodel = M('meteorology_scen');
        $data["scen_id"] = $pcp_id;
        $data["exec_type"] = 4;
        $pcpmodel->save($data);
        $rs = $pcpmodel->field("scen_name")->where("scen_id=" . $pcp_id)->find();
        $genscen = M('scenario');
        $scendata["scen_name"] = $rs["scen_name"];
        $scendata["scen_type"] = 2;
        $scen_id = $genscen->add($scendata);
        $data["scen_id"] = $pcp_id;
        $data["scen_id_fk"] = $scen_id;
        $pcpmodel->save($data);

        $this->simulate($scen_id);

        $restorearray = array();

        $this->restrore($restorearray);


        echo "success";

    }



    public function exectmp($id)
    {
        $tmp_id=$id;
        $modelin = "t_" . $tmp_id . "_model_in.txt";
        $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
        exec($command);
        $this->gentempture($tmp_id);

        $tmpmodel = M('temp_scen');
        $data["scen_id"] = $tmp_id;
        $data["exec_type"] = 4;
        $tmpmodel->save($data);
        $rs = $tmpmodel->field("scen_name")->where("scen_id=" . $tmp_id)->find();
        $genscen = M('scenario');
        $scendata["scen_name"] = $rs["scen_name"];
        $scendata["scen_type"] = 2;
        $scen_id = $genscen->add($scendata);
        $data["scen_id"] = $tmp_id;
        $data["scen_id_fk"] = $scen_id;
        $tmpmodel->save($data);

        $this->simulate($scen_id);

        $restorearray = array();

        $this->restrore($restorearray);

        echo "success";

    }

    public function execlucc()
    {
        $lucc_id = I('id');

        $model = M("landuse");
        $hru = M("hru");
        $row = $model->where("id =" . $lucc_id)->find();
        $sfid = $hru->field("fid")->where("id=" . $row["shru_id"])->find();
        $sfid = $sfid["fid"];

        $tfid = $hru->field("fid")->where("id=" . $row["thru_id"])->find();
        $tfid = $tfid["fid"];

        $source_hru_name = str_pad($row["sub_id"], 5, "0", STR_PAD_LEFT) . str_pad($sfid, 4, "0", STR_PAD_LEFT) . ".hru";
        $target_hru_name = str_pad($row["sub_id"], 5, "0", STR_PAD_LEFT) . str_pad($tfid, 4, "0", STR_PAD_LEFT) . ".hru";
        //HRU_FR
        $sfv = $row["sf"] / 100;
        $tfv = $row["tf"] / 100;
        $this->edithru($source_hru_name, $sfv);
        $this->edithru($target_hru_name, $tfv);


        $data["id"] = $lucc_id;
        $data["exec_type"] = 4;
        $model->save($data);
        $rs = $model->field("name")->where("id=" . $lucc_id)->find();
        $genscen = M('scenario');
        $scendata["scen_name"] = $rs["name"];
        $scendata["scen_type"] = 2;
        $scen_id = $genscen->add($scendata);
        $data["id"] = $lucc_id;
        $data["scen_id_fk"] = $scen_id;
        $model->save($data);

        $this->simulate($scen_id);
        $restorearray = array();
        array_push($restorearray, $source_hru_name);
        array_push($restorearray, $target_hru_name);
        $this->restrore($restorearray);

        echo "success";

    }

    public function execps()
    {
        $ps_id = I('id');

        $figfile = C('modelpath') . "Backup\\fig.fig";
        $lines = file($figfile);
        for ($i = count($lines) - 1; $i > 0; $i--) {
            if (startsWith($lines[$i], "route")) {
                $tokens = preg_split("/[\s]+/", $lines[$i]);
                $su = $tokens[2] * 1;
                break;
            }
        }
        //2.1    获取fig.fig文件中最大编号
        for ($i = count($lines) - 1; $i > 0; $i--) {
            if (startsWith($lines[$i], "rec")) {
                $tokens = preg_split("/[\s]+/", $lines[$i]);
                $fnum = $tokens[3] * 1;
                break;
            }
        }
        $model = M("pointsource");
        $row = $model->where("id =" . $ps_id)->find();

        switch ($row["ps_type"]) {
            case 0:
                $command = "reccnst";
                $command_code = 11;
                break;
            case 1:
                $command = "reccnst";
                $command_code = 11;
                break;
            case 2:
                $command = "rechour";
                $command_code = 6;
                break;
            case 3:
                $command = "recday";
                $command_code = 10;
                break;
            case 4:
                $command = "recmon";
                $command_code = 7;
                break;
            case 5:
                $command = "recyear";
                $command_code = 8;
                break;


        }

        for ($i = count($lines) - 1; $i > 0; $i--) {
            if (startsWith($lines[$i], "route")) {
                $tokens = preg_split("/[\s]+/", $lines[$i]);
                if ($tokens[3] == $row["subbasin_id"]) {
                    $isu = $tokens[4];
                    $pos = $i;
                    break;
                }
            }
        }

        $finename = $row["file_name"];
        $su++;
        $fnum++;
        $rs = $this->createps_config($isu, $su, $command, $command_code, $fnum, $finename);
        $su++;
        $lines[$pos] = substr($lines[$pos], 0, 28) . str_pad($su, 6, " ", STR_PAD_LEFT) . "\r\n";

        array_splice($lines, $pos, 0, $rs);

        copy(C('modelpath') . "ps_template\\" . $finename, C('modelpath') . $finename);
        file_put_contents(C('modelpath') . "ps_template\\fig_" . $ps_id . ".fig", $lines);
        copy(C('modelpath') . "ps_template\\fig_" . $ps_id . ".fig", C('modelpath') . "fig.fig");


        $data["id"] = $ps_id;
        $data["exec_type"] = 4;
        $model->save($data);
        $rs = $model->field("name")->where("id=" . $ps_id)->find();
        $genscen = M('scenario');
        $scendata["scen_name"] = $rs["name"];
        $scendata["scen_type"] = 2;
        $scen_id = $genscen->add($scendata);
        $data["id"] = $ps_id;
        $data["scen_id_fk"] = $scen_id;
        $model->save($data);

        $this->simulate($scen_id);
        $restorearray = array();
        $this->restrore($restorearray);

        echo "success";

    }

    public function genbase()
    {
        $pcpmodel = M('meteorology_scen');
        $rs = $pcpmodel->where("is_base=1")->select();
        $pcp_id = $rs[0]["scen_id"];
        $this->genprec($pcp_id);


        $tmpmodel = M('temp_scen');
        $rs = $tmpmodel->where("is_base=1")->select();
        $tmp_id = $rs[0]["scen_id"];
        $this->gentempture($tmp_id);

        $scenmodel = M('scenario');
        $rs = $scenmodel->where("is_base=1")->select();
        $scen_id = $rs[0]["scen_id"];
        $this->simulate($scen_id);


        echo "success";

    }


    public function testcp()
    {

        $filearray = array();

        $sourcepath = C('modelpath') . "Backup\\";
        $filearray[] = "a1.txt";
        $filearray[] = "a2.txt";

        foreach ($filearray as $item) {
            copy($sourcepath . $item, C('modelpath') . $item);
        }

    }

}

