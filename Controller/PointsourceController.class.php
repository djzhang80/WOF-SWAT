<?php
namespace Qwadmin\Controller;

class PointsourceController extends ComController
{
    public function index()
    {

        $this->display();
    }

    public function getpointsources()
    {

        $page = I("page");
        $rows = I("rows");

        $model = M("pointsource");
        $scens = $model->field("id, exec_type,subbasin_name,name,flocnst ,sedcnst ,orgncnst , orgpcnst, no3cnst ,nh3cnst ,no2cnst ,minpcnst , cbodcnst , disoxcnst ,chlacnst , solpstcnst ,srbpstcnst, bactpcnst ,bactlpcnst , cmtl1cnst ,cmtl2cnst ,cmtl3cnst")->where("ps_type=0")->order("id desc")->page($page, $rows)->select();


        $count = $model->where("ps_type=0")->count();
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
        $this->display();
    }

    public function insert()
    {
        $data['subbasin_id'] = I("sbid");
        $data["name"]=I("title");
        $guage = M('subbasin');
        $t = $guage->field("id,name")->where("id=" . $data['subbasin_id'])->find();
        $data['subbasin_name'] = $t["name"];
        $data['flocnst'] = I("flocnst");
        $data['sedcnst'] = I("sedcnst");
        $data['orgncnst'] = I("orgncnst");
        $data['orgpcnst'] = I("orgpcnst");
        $data['no3cnst'] = I("no3cnst");
        $data['nh3cnst'] = I("nh3cnst");
        $data['no2cnst'] = I("no2cnst");
        $data['minpcnst'] = I("minpcnst");
        $data['ps_type'] = 0;
        $data['exec_type'] = 2;
        $model = M("pointsource");
        $result = $model->add($data);
      $row=  $model->where("id=".$result)->find();


        $str = $row['flocnst'] . " " . $row['sedcnst'] . " " . $row['orgncnst'] . " " . $row['orgpcnst'] . " " . $row['no3cnst'] . " " . $row['nh3cnst'] . " " . $row['no2cnst'] . " " . $row['minpcnst'] . " " . "0 0 0 0 0 0 0 0 0 0";
        $filepath = C("modelpath") . "ps_template\\pst.dat";
        $genfilepath = C("modelpath") . "ps_template\\ps_" . $result . '.dat';
        $str1 = file_get_contents($filepath);
        file_put_contents($genfilepath, $str1 . "\r\n" . $str);
        $data['file_name'] = "ps_" . $result . '.dat';
        $model->where("id=" . $result)->save($data);
        $this->success('操作成功！', U('Pointsource/index'));
    }

    public
    function index2()
    {

        $this->display("importlist");
    }

    public
    function getpointsources2()
    {

        $page = I("page");
        $rows = I("rows");

        $model = M("pointsource");
        $scens = $model->alias("a")->join("qw_files b on a.file_id=b.id")->join("qw_member c on c.uid=b.owner_id")->field("a.id, a.subbasin_name,a.name,a.ps_type,b.createdate,c.user,b.file_name,a.exec_type")->order("id desc")->where("ps_type>0")->page($page, $rows)->select();


        $count = $model->where("ps_type>0")->count();
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
    function import()
    {
        $sb = M('subbasin');
        $t = $sb->field("id,name")->select();
        $this->assign("subbasins", $t);


        $tsm = M('ts_map');
        $t2 = $tsm->field("id,name")->select();
        $this->assign("timescale", $t2);


        $fm = M('files');
        $t3 = $fm->field("id,file_name")->select();
        $this->assign("filelist", $t3);

        $this->display();
    }


    public
    function insert2()
    {
        $data["name"]=I("title");
        $data['subbasin_id'] = I("sbid");
        $guage = M('subbasin');
        $t = $guage->field("id,name")->where("id=" . $data['subbasin_id'])->find();
        $data['subbasin_name'] = $t["name"];
        $data['file_id'] = I("fileid");
        $data['ps_type'] = I("timescale");
        $data['exec_type'] = 2;
        $model2 = M("files");
        $rs = $model2->where("id=" . $data['file_id'])->find();

        $model = M("pointsource");
        $id = $model->add($data);
        $uploaddir = C('filedir');
        $uploadfile = $uploaddir . $rs["file_name"];
        $genfilepath = C("modelpath") . "ps_template\\ps_" . $id . '.dat';
        copy($uploadfile, $genfilepath);

        $data['file_name'] = "ps_" . $id . '.dat';
        $model->where("id=". $id)->save($data);


        $this->success('操作成功！', U('Pointsource/importlist'));
    }

}
