<?php
namespace Admin\Controller;

use \Think\Controller;
use Think\Exception;
use Think\Think;

class ContentController extends CommonController
{
    public function index()
    {
        $condition = [];
        $title = $_GET['title'];
        if ($title) {
            $condition['title'] = $title;
        }
        if ($_GET['catid']) {
            $condition['catid'] = intval($_GET['catid']);
        }

        $page = $_REQUEST['p'] ? $_REQUEST['p'] : 1;
        $pageSize = 5;

        $news = D('News')->getNews($condition, $page, $pageSize);
        $conut = D('News')->getNewsCount($condition);
        $res = new \Think\Page($conut, $pageSize);
        $pagetRes = $res->show();
        $this->assign('pageRes', $pagetRes);
        $this->assign('news', $news);
        $this->assign('webSiteMenu', D('Menu')->getBarMenus());
        //推荐位
        $positions = D('Position')->getNormalPositions();
        $this->assign('Positions', $positions);
        $this->display();
    }

    public function add()
    {
        if ($_POST) {
            if (!isset($_POST['title']) || !$_POST['title']) {
                return show(0, '标题不存在');
            }
            if (!isset($_POST['small_title']) || !$_POST['small_title']) {
                return show(0, '短标题不存在');
            }
            if (!isset($_POST['catid']) || !$_POST['catid']) {
                return show(0, '文章栏目不存在');
            }
            if (!isset($_POST['keywords']) || !$_POST['keywords']) {
                return show(0, '关键字不存在');
            }
            if (!isset($_POST['content']) || !$_POST['content']) {
                return show(0, '内容不存在');
            }

            if ($_POST['news_id']) {
                //update
                return $this->save($_POST);
            }

            $newsId = D("News")->insert($_POST);
            if ($newsId) {
                $newsContent['news_id'] = $newsId;
                $newsContent['content'] = $_POST['content'];
                $rs = D('NewsContent')->insert($newsContent);
                if ($rs) {
                    return show(1, '新增成功');
                } else {
                    return show(0, '内容插入失败');
                }
            } else {
                return show(0, '新增失败');
            }

        } else {
            $webSiteMenu = D('Menu')->getBarMenus();
            $titleFontColor = C('TITLE_FONT_COLOR');
            $copyFrom = C('COPY_FROM');//C 读取配置文件
            $this->assign('websiteMenu', $webSiteMenu);
            $this->assign('titleFontColor', $titleFontColor);
            $this->assign('copyFrom', $copyFrom);
            $this->display();
        }
    }

    public function edit()
    {
        $newsId = $_GET['id'];
        if (!$newsId) {
            $this->redirect('/admin.php?c=content');
        }
        $news = D('News')->find($newsId);
        if (!$news) {
            $this->redirect('/admin.php?c=content');
        }
        $newsContent = D('NewsContent')->find($newsId);
        if ($newsContent) {
            $news['content'] = $newsContent['content'];
        }
        $webSiteMenu = D("Menu")->getBarMenus();
        $this->assign('webSiteMenu', $webSiteMenu);
        $this->assign('titleFontColor', C('TITLE_FONT_COLOR'));
        $this->assign('copyFrom', C('COPY_FROM'));
        $this->assign('news', $news);
        $this->display();
    }

    public function save($data)
    {
        $newsId = $data['news_id'];
        unset($data['news_id']);
        try {
            $id = D('News')->updateById($newsId, $data);
            $newsContentData['content'] = $data['content'];
            $condId = D("NewsContent")->updateNewsById($newsId, $newsContentData);
            if ($id === false || $condId === false) {
                return show(0, '更新失败');
            }
            return show(1, '更新成功');
        } catch (Exception $e) {
            return show(0, $e->getMessage());
        }
    }

    public function setStatus()
    {
        if ($_POST) {
            $id = $_POST['id'];
            $status = $_POST['status'];
            if (!$id) {
                return show(0, 'ID不存在');
            }
            try {
                $res = D('News')->updateStatusById($id, $status);
                if ($res) {
                    return show(1, '操作成功');
                }
                return show(0, '操作失败');
            } catch (Exception $e) {
                return show(0, '系统发生异常');
            }
        } else {
            return show(0, '没有提交内容');
        }
    }

    public function listorder()
    {
        $errors = [];
        $listorder = $_POST['listorder'];
        $jumpUrl = $_SERVER['HTTP_REFERER'];
        try {
            if ($listorder) {
                foreach ($listorder as $newsid => $v) {
                    //update
                    $id = D('News')->updateNewsListorder($newsid, $v);
                    if ($id === false) {
                        $errors[] = $newsid;
                    }
                }
                if ($errors) {
                    return show(0, '排序失败-' . implode(',', $errors), ['jump_url' => $jumpUrl]);
                } else {
                    return show(1, '排序成功', ['jump_url' => $jumpUrl]);
                }
            }
        } catch (Exception $e) {
            return show(0, $e->getMessage());
        }
        return show(0, '排序失败', ['jump_url' => $jumpUrl]);
    }

    //推送
    public function push()
    {
        $jumpUrl = $_SERVER['HTTP_REFERER'];
        $positionId = intval($_POST['position_id']);
        $newsId = $_POST['push'];
        if (!$newsId || !is_array($newsId)) {
            return show(0, '请选择推荐的文章进行推荐');
        }
        if (!$positionId) {
            return show(0, '没有选择推荐位');
        }
        try {
            $news = D("News")->getNewsByNewsIdIn($newsId);
            if (!$news) {
                return show(0, '没有相关内容');
            }
            foreach ($news as $new) {
                $data = [
                    'position_id' => $positionId,
                    'title' => $new['title'],
                    'thumb' => $new['thumb'],
                    'news_id' => $new['news_id'],
                    'status' => 1,
                    'create_time' => $new['create_time'],
                ];
                $position = D('PositionContent')->insert($data);
            }
        } catch (Exception $e) {
            show(0, $e->getMessage());
        }
        return show(1, '推送成功！', ['jump_url' => $jumpUrl]);
    }
}