<?php
namespace Qwadmin\Controller;

class HruController extends ComController
{
    public function init()
    {
        $model = M("subasin_info");
        $files = glob(C('modelpath') . '0*.sub');
        // print_r($files);
        foreach ($files as $file) {
            $lines = file($file);
            foreach ($lines as $line) {
                if (preg_match("/SUB_KM/", $line) == 1) {
                    $tokens = explode("|", $line);
                    $data["area"] = trim($tokens[0]);
                }

                if (preg_match("/HRUTOT/", $line) == 1) {
                    $tokens = explode("|", $line);
                    $data["hru_number"] = trim($tokens[0]);
                }
            }

            $data["id"] = ltrim(substr(basename($file, ".sub"), 0, 5), '0');
            $model->add($data);

        }


    }

    public function init2()
    {
        set_time_limit(0);
        $model = M("subasin_info");
        $model2 = M("hru");
        $files = glob(C('modelpath') . '0*.hru');
        // print_r($files);
        foreach ($files as $file) {
            $lines = file($file);
            $rs = array();
            $pattern = "/(HRU:)(\d+)(\s{1})(Subbasin:)(\d+)(\s{1})(HRU:)(\d+)(\s{1})(Luse:)(\w+)(\s{1})(Soil: )(\w+)(\s{1})(Slope )(\S+)/";
            $t = preg_match($pattern, $lines[0], $rs);
            $tokens = explode("|", $lines[1]);
            $data["id"] = $rs[2];
            $data["sub_id"] = $rs[5];
            $data["fid"] = $rs[8];
            $data["soil"] = $rs[14];
            $data["landuse"] = $rs[11];
            $data["slope"] = $rs[17];
            $data["name"] = $data["landuse"] . "_" . $data["soil"] . "_" . $data["slope"];
            $data["fraction"] = trim($tokens[0]);
            $data["desc"] = "";
            $k = $model->where("id=" . $data["sub_id"])->find();
            $data["area"] = $data["fraction"] * $k["area"];
            $model2->add($data);
        }
    }

    public function init3()
    {
        $line = " .hru file Watershed HRU:836 Subbasin:93 HRU:9 Luse:URMD Soil: CST Slope 0-2 2013-7-14 00:00:00 ArcSWAT 2009.93.7";
        $rs = array();
        $pattern = "/(HRU:)(\d+)(\s{1})(Subbasin:)(\d+)(\s{1})(HRU:)(\d+)(\s{1})(Luse:)(\w+)(\s{1})(Soil: )(\w+)(\s{1})(Slope )(\S+)/";
        $t = preg_match($pattern, $line, $rs);

        print_r($rs);
        print($t);
    }

    public function index()
    {
        $this->display();
    }

    public function getlist()
    {

        $page = I("page");
        $rows = I("rows");
        $model = M('landuse');
        $scens = $model->field("id,exec_type,sub_name,name,shru_name,thru_name,sf,sa,tf,ta")->order("id desc")->page($page, $rows)->select();
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

    public function add()
    {
        $guage = M('subbasin');
        $t = $guage->field("id,name")->select();
        $this->assign("subbasins", $t);

        $model = M('hru');
        $k = $model->where("sub_id=1")->field("id,name")->select();
        $this->assign("hru", $k);


        $this->display();
    }

    public function insert()
    {
        $data['name'] = I("title");
        $model = M('landuse');
        $data['sub_id'] = I("subsn");
        $data['shru_id'] = I("source");
        $data['thru_id'] = I("target");
        $data['sf'] = I("sf");
        $data['sa'] = I("sa");
        $data['tf'] = I("tf");
        $data['ta'] = I("ta");
        $data['exec_type'] = 2;

        $bsn = M('subbasin');
        $rs = $bsn->field("name")->where("id=" . $data['shru_id'])->find();
        $data["sub_name"] = $rs["name"];




        $hru = M('hru');
        $rs = $hru->field("name")->where("id=" . $data['sub_id'])->find();
        $data["shru_name"] = $rs["name"];

        $rs = $hru->field("name")->where("id=" . $data['thru_id'])->find();
        $data["thru_name"] = $rs["name"];

        $model->add($data);
        $this->success('操作成功！', U('hru/index'));
    }

    public function select()
    {
        $sub_id = I('sub_id');
        $model2 = M("hru");
        $rs = $model2->field("id,name")->where("sub_id=" . $sub_id)->select();
        $this->ajaxReturn($rs, "json");
    }

    public function hruinfo()
    {
        $id1 = I('v1');
        $id2 = I('v2');
        $model2 = M("hru");
        $rs = $model2->field("fraction,area")->where("id=" . $id1)->find();

        $final["sf"] = $rs["fraction"];
        $final["sa"] = $rs["area"];
        $rs = $model2->field("fraction,area")->where("id=" . $id2)->find();

        $final["tf"] = $rs["fraction"];
        $final["ta"] = $rs["area"];
        $this->ajaxReturn($final, "json");
    }

}
