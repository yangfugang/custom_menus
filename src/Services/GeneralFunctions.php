<?php
namespace Drupal\custom_menus\Services;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTree;

/**
 * Class GeneralFunctions
 * @package Drupal\custom_menu\Services
 * 一些通用的功能
 */
class GeneralFunctions {

  /**
   * @var MenuLinkTree
   */
  private $menuTree;

  public function __construct(MenuLinkTree $menuTree)
  {
    $this->menuTree = $menuTree;
  }

  /**
   * 读取菜单
   *
   * @param $menu_name
   * 指定菜单的机读名称
   * @return array
   */
  public function getMenu($menu_name) {
    // 建立默认菜单树参数
    $parameters = new MenuTreeParameters();
    // 根据这组参数加载树
    $tree = $this->menuTree->load($menu_name, $parameters);
    // 设置回调
    $manipulators = array(
      // 仅显示当前用户可访问的链接
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      // 使用默认的菜单链接排序
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $this->menuTree->transform($tree, $manipulators);
    // 从转换后的树构建可渲染数组
    $menu = $this->menuTree->build($tree);

    return $menu;
  }

  /**
   * 获取菜单内的所有1-2级链接
   * @param $menu_name
   * @param int $level
   * @param null $menu_data
   * @return array
   */
  public function getMenuItems($menu_name, $level = 1, $menu_data = NULL) {
    if(is_null($menu_data)) {
      $menu_data = $this->getMenu($menu_name);
    }
    $menu = array();
    $items = array();
    if(isset($menu_data['#items'])) {
      $items = $menu_data['#items'];
    } elseif (isset($menu_data['below'])) {
      $items = $menu_data['below'];
    }
    $temp_menu = array();
    foreach ($items as $k => $item) {
      $temp_menu = array(
        'title' => $item['title'],
        //'url' => $item['url']->getInternalPath(),
        'url' => $item['url']->toUriString(),
        'level' => $level,
        'attributes' => array(
          'class' => array()
        )
      );

      if(isset($item['below']) && $level <= 2) {
        $temp_menu['items'] = $this->getMenuItems($menu_name, $level + 1, $item);
      }
      $menu[$k] = $temp_menu;
    }

    return $menu;
  }

}
