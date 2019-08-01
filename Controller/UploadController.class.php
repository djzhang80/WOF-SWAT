<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：小马哥<hanchuan@qiawei.com>
 * 日    期：2015-09-17
 * 版    本：1.0.3
 * 功能说明：文件上传控制器。
 *
 **/

namespace Qwadmin\Controller;

use Think\Upload;

class UploadController extends ComController
{
    public function index($type = null)
    {

    }

    public function uploadpic()
    {
        $Img = I('Img');
        $Path = null;
        if ($_FILES['img']) {
            $Img = $this->saveimg($_FILES);
        }
        $BackCall = I('BackCall');
        $Width = I('Width');
        $Height = I('Height');
        if (!$BackCall) {
            $Width = $_POST['BackCall'];
        }
        if (!$Width) {
            $Width = $_POST['Width'];
        }
        if (!$Height) {
            $Width = $_POST['Height'];
        }
        $this->assign('Width', $Width);
        $this->assign('BackCall', $BackCall);
        $this->assign('Img', $Img);
        $this->assign('Height', $Height);
        $this->display('Uploadpic');
    }

    private function saveimg($files)
    {
        $mimes = array(
            'image/jpeg',
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/pjpeg',
            'image/gif',
            'image/bmp',
            'image/x-png'
        );
        $exts = array(
            'jpeg',
            'jpg',
            'jpeg',
            'png',
            'pjpeg',
            'gif',
            'bmp',
            'x-png'
        );
        $upload = new Upload(array(
            'mimes' => $mimes,
            'exts' => $exts,
            'rootPath' => './Public/',
            'savePath' => 'attached/' . date('Y') . "/" . date('m') . "/",
            'subName' => array('date', 'd'),
        ));
        $info = $upload->upload($files);
        if (!$info) {// 上传错误提示错误信息
            $error = $upload->getError();
            echo "<script>alert('{$error}')</script>";
        } else {// 上传成功
            foreach ($info as $item) {
                $filePath[] = __ROOT__ . "/Public/" . $item['savepath'] . $item['savename'];
            }
            $ImgStr = implode("|", $filePath);
            return $ImgStr;
        }
    }

    public function batchpic()
    {
        $ImgStr = I('Img');
        $ImgStr = trim($ImgStr, '|');
        $Img = array();
        if (strlen($ImgStr) > 1) {
            $Img = explode('|', $ImgStr);
        }
        $Path = null;
        $newImg = array();
        $newImgStr = null;
        if ($_FILES) {
            $newImgStr = $this->saveimg($_FILES);
            if ($newImgStr) {
                $newImg = explode('|', $newImgStr);
            }

        }
        $Img = array_merge($Img, $newImg);
        $ImgStr = implode("|", $Img);
        $BackCall = I('BackCall');
        $Width = I('u');
        $Height = I('Height');
        if (!$BackCall) {
            $Width = $_POST['BackCall'];
        }
        if (!$Width) {
            $Width = $_POST['Width'];
        }
        if (!$Height) {
            $Width = $_POST['Height'];
        }
        $this->assign('Width', $Width);
        $this->assign('BackCall', $BackCall);
        $this->assign('ImgStr', $ImgStr);
        $this->assign('Img', $Img);
        $this->assign('Height', $Height);
        $this->display('Batchpic');
    }


    public function showform()
    {

        $this->display();
    }

    public function upload_data()
    {


        $uploaddir = C('filedir');
        $uploadfile = $uploaddir . basename($_FILES['file']['name']);

        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
            $data['file_name'] = basename($_FILES['file']['name']);
            $data['createdate'] = date("Y-m-d", time());
            $data['owner_id'] = $_SESSION["uid"];
            $model = M('files');
            $sid = $model->add($data);
            $ttt = $model->getLastSql();

        } else {
            echo "Possible file upload attack!\n";
        }


    }

    public function list2()
    {
        $this->display("list2");
    }


    public function getlist()
    {
        $model = M("files");
        $page = I("page");
        $rows = I("rows");
        $scens = $model->alias('a')->join("qw_member b on b.uid=a.owner_id")->field("a.id,a.createdate,b.user,a.file_name")->page($page, $rows)->select();


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


    public function list3()
    {
        $this->display("list3");
    }


    public function gettemplate()
    {
        $model = M("file_templates");
        $page = I("page");
        $rows = I("rows");
        $scens = $model->field("id,category_one,category_two,file_name,file_desc")->page($page, $rows)->select();


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

    public function download()
    {
        $filename=I('f');
        $url=C('ftdir').$filename;
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"".$filename."\"");
        echo readfile($url);
    }

}
