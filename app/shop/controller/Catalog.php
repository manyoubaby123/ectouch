<?php

namespace app\shop\controller;

class Catalog extends Init
{
    public function index()
    {
        if (!$GLOBALS['smarty']->is_cached('catalog.dwt')) {
            /* 取出所有分类 */
            $cat_list = cat_list(0, 0, false);

            foreach ($cat_list as $key => $val) {
                if ($val['is_show'] == 0) {
                    unset($cat_list[$key]);
                }
            }

            $this->assign_template();
            assign_dynamic('catalog');
            $position = assign_ur_here(0, $GLOBALS['_LANG']['catalog']);
            $this->assign('page_title', $position['title']);   // 页面标题
            $this->assign('ur_here', $position['ur_here']); // 当前位置

            $this->assign('helps', get_shop_help()); // 网店帮助
            $this->assign('cat_list', $cat_list);       // 分类列表
            $this->assign('brand_list', get_brands());    // 所以品牌赋值
            $this->assign('promotion_info', get_promotion_info());
        }

        return $GLOBALS['smarty']->display('catalog.dwt');
    }

    /**
     * 计算指定分类的商品数量
     *
     * @access public
     * @param   integer $cat_id
     *
     * @return void
     */
    private function calculate_goods_num($cat_list, $cat_id)
    {
        $goods_num = 0;

        foreach ($cat_list as $cat) {
            if ($cat['parent_id'] == $cat_id && !empty($cat['goods_num'])) {
                $goods_num += $cat['goods_num'];
            }
        }

        return $goods_num;
    }
}
