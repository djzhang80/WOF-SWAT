<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：寒川<hanchuan@qiawei.com>
 * 日    期：2016-01-20
 * 版    本：1.0.0
 * 功能说明：用户控制器。
 *
 **/
/*http://localhost/wms/index.php/Qwadmin/Watershed/readconfig3.html*/
namespace Qwadmin\Controller;

class WatershedController extends ComController
{
    public function index()
    {

        $this->display();
    }

    public function tree()
    {

        $this->display();
    }

    public function readconfig()
    {
        $nodes = array();
        $links = array();
        $lines = file(C('modelpath') . "fig.fig");
        foreach ($lines as $line_num => $line) {
            /*            if ($this->startsWith($line, "subbasin")) {
                            $tokens = preg_split("/[\s]+/", $line);
                            $entity["name"] = "subbasin" . $tokens[2];
                            array_push($nodes, $entity);
                        }*/

            if ($this->startsWith($line, "route")) {
                $tokens = preg_split("/[\s]+/", $line);
                $entity["name"] = "r" . $tokens[3];
                array_push($nodes, $entity);
            }

            if ($this->startsWith($line, "routres")) {
                $tokens = preg_split("/[\s]+/", $line);
                $entity["name"] = "re" . $tokens[3];
                array_push($nodes, $entity);
            }
            if ($this->startsWith($line, "rec")) {
                $tokens = preg_split("/[\s]+/", $line);
                $entity["name"] = "p" . $tokens[3];
                array_push($nodes, $entity);
            }

            if ($this->startsWith($line, "route") || $this->startsWith($line, "routres")) {
                $tokens = preg_split("/[\s]+/", $line);
                $src = $tokens[4];
                $rs = array();
                $this->findSource($lines, $tokens[4], $rs);

                foreach ($rs as $r_num => $row) {
                    $entity2["source"] = $row;
                    if ($this->startsWith($line, "route")) {
                        $entity2["target"] = "r" . $tokens[3];

                    } else {
                        $entity2["target"] = "re" . $tokens[3];

                    }
                    $entity2["value"] = 1;
                    array_push($links, $entity2);
                }
            }
        }
        $final["nodes"] = $nodes;
        $final["links"] = $links;
        $model = M("serialize");
        $data["iname"] = "link";
        $data["data"] = serialize($final);
        $sid = $model->add($data);
        $this->ajaxReturn($final, "json");
    }

    public function readconfig2()
    {
        $model = M("serialize");
        $data = $model->where("iname='link'")->select();
        $final = unserialize($data[0]["data"]);
        $this->ajaxReturn($final, "json");
    }

    public function readconfig3()
    {
        /*http://localhost/wms/index.php/Qwadmin/Watershed/readconfig3.html?root=r88*/
        $root=I('root');
        $model = M("serialize");
        $data = $model->where("iname='link'")->select();
        $final = unserialize($data[0]["data"]);
        $result["name"] = "";
        $this->createTree($final["links"], $result, true,$root);
        $this->ajaxReturn($result, "json");
    }

    function createTree($lines, &$rs, $isroot,$root="r88")
    {
        if ($isroot) {
            $rs["name"] = $root;
            $rs["value"] = 1;
            $this->createTree($lines, $rs, false);
        } else {

            foreach ($lines as $line) {

                if ($line["target"] == $rs["name"]) {
                    if ($rs["children"] == null) {

                        $rs["children"] = array();
                    }
                    $k=null;
                    $k["name"] = $line["source"];
                    $k["value"] = 1;
                    $this->createTree($lines, $k, false);
                    array_push($rs["children"], $k);
                }

            }

        }


    }


    function findSource($lines, $source, &$result)
    {
        foreach ($lines as $line_num => $line) {

            if ($this->startsWith($line, "route") || $this->startsWith($line, "add") || $this->startsWith($line, "routres")
                || $this->startsWith($line, "rec")
            ) {
                $tokens = preg_split("/[\s]+/", $line);
                if ($tokens[2] == $source) {
                    $rs = $line;

                    if ($this->startsWith($rs, "add")) {
                        $tokens = preg_split("/[\s]+/", $rs);
                        $this->findSource($lines, $tokens[3], $result);
                        $this->findSource($lines, $tokens[4], $result);
                    } elseif ($this->startsWith($rs, "route")) {
                        $tokens = preg_split("/[\s]+/", $rs);
                        array_push($result, "r" . $tokens[3]);

                    } elseif ($this->startsWith($rs, "routres")) {
                        $tokens = preg_split("/[\s]+/", $rs);
                        array_push($result, "re" . $tokens[3]);
                    } elseif ($this->startsWith($rs, "rec")) {
                        $tokens = preg_split("/[\s]+/", $rs);
                        array_push($result, "p" . $tokens[3]);
                    }
                }
            }

        }

    }


    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 ||
            (substr($haystack, -$length) === $needle);
    }


    public
    function ldata()
    {
        $page = I("page");
        $rows = I("rows");

        $model = M("meteorology_scen");
        $scens = $model->field("scen_id id,scen_name,scen_type,scen_method,scen_value,scen_file,scen_desc,scen_createdate,scen_modifieddate,scen_id_fk,scen_target")->page($page, $rows)->select();
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


    public function adddata()
    {
        $oper = I("oper");
        $scen_id = I("id");
        $data['scen_desc'] = I("scen_desc");
        $data['scen_method'] = I("scen_method");
        $data['scen_name'] = I("scen_name");
        $data['scen_target'] = I("scen_target");
        $data['scen_value'] = I("scen_value");
        $data['scen_type'] = I("scen_type");
        $data['scen_modifieddate'] = date("Y-m-d", time());
        $model = M("meteorology_scen");
        if ($oper == "edit") {
            $data['scen_type'] = 1;
            $data['scen_modifieddate'] = date("Y-m-d", time());
            $model->where('scen_id=' . $scen_id)->save($data);
            $sid = $scen_id;
            $modelin = "p_" . $sid . "_model_in.txt";
            $tflag = "";
            $mflag = "";
            if ($data["scen_method"] == 1) {
                $mflag = "r";
            } elseif ($data["scen_method"] == 2) {
                $mflag = "a";
            } else {
                $mflag = "v";
            }
            if ($data["scen_target"] == "全部") {
                $tflag = "";
            } else {
                $guage = M("guage");
                $t = $guage->field("guage_cid")->where("guage_name='" . $data["scen_target"] . "' and guage_type=1")->select();
                $ttt = $guage->getLastSql();
                $tflag = $t[0]["guage_cid"];
            }
            $ts = $mflag . "__precipitation(" . $tflag . "){2001001-2010365}.pcp1.pcp " . $data['scen_value'];
            file_put_contents(C('modelpath') . "template\\" . $modelin, $ts);

            $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
            exec($command, $rs, $st);
            ScenController::genprec($sid);
        } elseif ($oper == "add") {
            $data['scen_type'] = 1;
            $data['scen_createdate'] = date("Y-m-d", time());
            $data['scen_modifieddate'] = date("Y-m-d", time());
            $sid = $model->add($data);
            $modelin = "p_" . $sid . "_model_in.txt";
            $tflag = "";
            $mflag = "";
            if ($data["scen_method"] == 1) {
                $mflag = "r";
            } elseif ($data["scen_method"] == 2) {
                $mflag = "a";
            } else {
                $mflag = "v";
            }
            if ($data["scen_target"] == "全部") {
                $tflag = "";
            } else {
                $guage = M("guage");
                $t = $guage->field("guage_cid")->where("guage_name='" . $data["scen_target"] . "' and guage_type=1")->select();
                $ttt = $guage->getLastSql();
                $tflag = $t[0]["guage_cid"];
            }
            $ts = $mflag . "__precipitation(" . $tflag . "){2001001-2010365}.pcp1.pcp " . $data['scen_value'];
            file_put_contents(C('modelpath') . "template\\" . $modelin, $ts);

            $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
            exec($command, $rs, $st);
            ScenController::genprec($sid);

        } elseif ($oper == "del") {
            $model->delete($scen_id);

        }


    }

    public
    function select()
    {
        $model = M("guage");
        $r = $model->field("guage_name,guage_name")->where("guage_type=1")->select();
        $rs = " <select><option value='全部'> 全部 </option>";
        $i = 0;
        foreach ($r as $item) {
            $rs = $rs . "<option value='" . $item["guage_name"] . "'>" . $item["guage_name"] . "</option>";
        }
        $rs = $rs . "</select>";
        echo $rs;

    }


    public
    static function genprec($scen)
    {
        //http://localhost/wms/qwadmin/SWAT/genprec.html?scen=3
        //http://localhost/wms/qwadmin/SWAT/genprec.html?scen=4
        // $scen = $_GET["scen"];
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
    function tdata()
    {
        $page = I("page");
        $rows = I("rows");

        $model = M("temp_scen");
        $scens = $model->field("scen_id id,scen_name,scen_type,scen_method,scen_value,scen_file,scen_desc,scen_createdate,scen_modifieddate,scen_id_fk,scen_target")->page($page, $rows)->select();
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

    public
    function tadddata()
    {
        $oper = I("oper");
        $scen_id = I("id");
        $data['scen_desc'] = I("scen_desc");
        $data['scen_method'] = I("scen_method");
        $data['scen_name'] = I("scen_name");
        $data['scen_target'] = I("scen_target");
        $data['scen_value'] = I("scen_value");
        $data['scen_type'] = I("scen_type");
        $data['scen_modifieddate'] = date("Y-m-d", time());
        $model = M("temp_scen");
        if ($oper == "edit") {
            $data['scen_type'] = 1;
            $data['scen_modifieddate'] = date("Y-m-d", time());
            $model->where('scen_id=' . $scen_id)->save($data);
            $sid = $scen_id;
            $modelin = "t_" . $sid . "_model_in.txt";
            $tflag = "";
            $mflag = "";
            if ($data["scen_method"] == 1) {
                $mflag = "r";
            } elseif ($data["scen_method"] == 2) {
                $mflag = "a";
            } else {
                $mflag = "v";
            }
            if ($data["scen_target"] == "全部") {
                $tflag = "";
            } else {
                $guage = M("guage");
                $t = $guage->field("guage_cid")->where("guage_name='" . $data["scen_target"] . "' and guage_type=2")->select();
                $ttt = $guage->getLastSql();
                $tflag = $t[0]["guage_cid"];
            }
            $ts = $mflag . "__maxtemp(" . $tflag . "){2001001-2010365}.tmp1.tmp " . $data['scen_value'];
            $ts = $ts . "\r\n" . $mflag . "__mintemp(" . $tflag . "){2001001-2010365}.tmp1.tmp " . $data['scen_value'];
            file_put_contents(C('modelpath') . "template\\" . $modelin, $ts);

            $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
            exec($command, $rs, $st);
            ScenController::gentempture($sid);
        } elseif ($oper == "add") {
            $data['scen_type'] = 1;
            $data['scen_createdate'] = date("Y-m-d", time());
            $data['scen_modifieddate'] = date("Y-m-d", time());
            $sid = $model->add($data);
            $modelin = "t_" . $sid . "_model_in.txt";
            $tflag = "";
            $mflag = "";
            if ($data["scen_method"] == 1) {
                $mflag = "r";
            } elseif ($data["scen_method"] == 2) {
                $mflag = "a";
            } else {
                $mflag = "v";
            }
            if ($data["scen_target"] == "全部") {
                $tflag = "";
            } else {
                $guage = M("guage");
                $t = $guage->field("guage_cid")->where("guage_name='" . $data["scen_target"] . "' and guage_type=2")->select();
                $ttt = $guage->getLastSql();
                $tflag = $t[0]["guage_cid"];
            }
            $ts = $mflag . "__maxtemp(" . $tflag . "){2001001-2010365}.tmp1.tmp " . $data['scen_value'];
            $ts = $ts . "\r\n" . $mflag . "__mintemp(" . $tflag . "){2001001-2010365}.tmp1.tmp " . $data['scen_value'];
            file_put_contents(C('modelpath') . "template\\" . $modelin, $ts);

            $command = "start /B  " . C('modelpath') . "run_se.bat " . C('modelpath') . " " . $modelin;
            exec($command, $rs, $st);
            ScenController::gentempture($sid);

        } elseif ($oper == "del") {
            $model->delete($scen_id);

        }
        echo "success";

    }

    public
    function tselect()
    {
        $model = M("guage");
        $r = $model->field("guage_name,guage_name")->where("guage_type=2")->select();
        $rs = " <select><option value='全部'> 全部 </option>";
        $i = 0;
        foreach ($r as $item) {
            $rs = $rs . "<option value='" . $item["guage_name"] . "'>" . $item["guage_name"] . "</option>";
        }
        $rs = $rs . "</select>";
        echo $rs;

    }

    public
    function gentempture($scen)
    {
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
