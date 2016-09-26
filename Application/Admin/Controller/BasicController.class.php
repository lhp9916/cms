<?php
//基本配置
namespace Admin\Controller;

use \Think\Controller;

class BasicController extends CommonController
{
    public function index()
    {
        $result = D("Basic")->select();
//        echo json_encode($result);die;
        $this->assign('vo', $result);
        $this->display();
    }

    public function add()
    {
        if ($_POST) {
            if (!$_POST['title']) {
                return show(0, '站点名称不能为空');
            }
            if (!$_POST['keywords']) {
                return show(0, '站点关键词不能为空');
            }
            if (!$_POST['description']) {
                return show(0, '站点描述不能为空');
            }
            D("Basic")->save($_POST);
            return show(1, '配置成功');
        } else {
            return show(0, '没有提交数据');
        }
    }
}