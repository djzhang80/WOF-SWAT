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

namespace Qwadmin\Controller;

class ScenController extends ComController
{
    public function index()
    {

        $this->display();
    }

    public function temp()
    {

        $this->display("temp");
    }

    public function ldata()
    {
        $page = I("page");
        $rows = I("rows");

        $model = M("meteorology_scen");
        $scens = $model->field("scen_id id,scen_name,scen_type,scen_method,scen_value,scen_file,scen_desc,scen_createdate,scen_modifieddate,scen_id_fk,scen_target,exec_type")->where("is_base=0")->page($page, $rows)->select();
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
        $data['exec_type'] = 2;
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



        } elseif ($oper == "del") {
            $model->delete($scen_id);
        }


    }

    public function select()
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




    public function tdata()
    {
        $page = I("page");
        $rows = I("rows");

        $model = M("temp_scen");
        $scens = $model->field("scen_id id,scen_name,scen_type,scen_method,scen_value,scen_file,scen_desc,scen_createdate,scen_modifieddate,scen_id_fk,scen_target,exec_type")->where("is_base=0")->page($page, $rows)->select();
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

    public function tadddata()
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
        $data['exec_type'] = 2;
        $model = M("temp_scen");
        if ($oper == "edit") {
            $data['scen_type'] = 2;
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
        } elseif ($oper == "add") {
            $data['scen_type'] = 2;
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


        } elseif ($oper == "del") {
            $model->delete($scen_id);

        }
        echo "success";

    }

    public function tselect()
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



}
