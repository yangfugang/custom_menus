<?php

/**
 * @file
 * Hooks provided by the toolbar module.
 */

use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * 为菜单增加节点
 * 结构如下
 * title : 标题
 * url : 一个URI字符串，参考 @see \Drupal\Core\Url::toUriString()
 * level : 级别
 * attributes : 额外的属性
 *
 * @see \Drupal\custom_menus\Element\Custombar::preRenderCustombar()
 * @ingroup toolbar_tabs
 */
function hook_custombar() {
  $items = [];
  $items['custom_menu_link'] = array(
    'title' => 'title',
    'url' => 'URI',
    'level' => 0,
    'attributes' => array(
      'class' => array()
    )
  );

  return $items;
}

/**
 * 修改现有的菜单节点
 */
function hook_custombar_alter(&$items) {
  // Move the User tab to the right.
  $items['commerce']['#weight'] = 5;
}

