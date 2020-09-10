<?php
namespace Drupal\custom_menus\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * 自定义渲染元素
 * 这里的Annotations注解是用于声明当前对象创建的Element类型
 * 是必须要写的，且全部小写
 *
 * @RenderElement("custombar")
 */
class Custombar extends RenderElement {

  public function getInfo()
  {
    // TODO: Implement getInfo() method.
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderCustombar'],
      ],
      '#theme' => 'custombar',
      '#attached' => [
        'library' => [
          //'toolbar/toolbar',  # 配置加载自定义资源库
        ],
      ],
      // 配置元素属性
      '#attributes' => [
        'id' => 'custombar-administration',
        'role' => 'group',
        'aria-label' => $this->t('Site administration toolbar'),
      ]
    ];
  }

  public static function preRenderCustombar($element) {
    $module_handler = static::moduleHandler();
    // 调用所有的 hook_custombar().
    $items = $module_handler->invokeAll('custombar');
    // 允许 hook_custombar_alter().
    $module_handler->alter('custombar', $items);
    // 按照 #weight 排序
    uasort($items, ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
    // 合并数组
    $element['items'] = $items;

    return $element;
  }

  /**
   * 获取 module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected static function moduleHandler() {
    return \Drupal::moduleHandler();
  }
}
