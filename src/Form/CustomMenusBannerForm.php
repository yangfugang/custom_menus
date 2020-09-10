<?php
namespace Drupal\custom_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilder;

class CustomMenusBannerForm extends FormBase {

  private $entityTypeManager;
  private $formBuilder;

  /**
   * CustomMenusBannerForm constructor.
   * 初始化对象，并设置成员变量
   * @param EntityTypeManager $entityTypeManager
   */
  public function __construct(EntityTypeManager $entityTypeManager, FormBuilder $formBuilder)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * 返回初始化对象并注入依赖
   * @param ContainerInterface $container
   * @return CustomMenusBannerForm|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  public function getFormId()
  {
    // TODO: Implement getFormId() method.
    return 'custom_menus_banners_config';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // TODO: Implement buildForm() method.
    $node = $this->entityTypeManager->getStorage('node')->create(array(
      'type' => 'node_type_banner'
    ));

    // 获取Node类型的展示模式 EntityFormDisplay (例如 default Form Display)
    $entity_form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load('node.node_type_banner.default');

    // 获取Node Create表单
    $nodeForm = $this->entityTypeManager->getFormObject('node', 'default')->setEntity($node);
    $createForm = $this->formBuilder->getForm($nodeForm);

    // 获取 段落 类型的字段 Widget 并添加到表单
    if ($widget = $entity_form_display->getRenderer('field_slide_banner')) { // 返回Widget对象
      $items = $node->get('field_slide_banner'); // 返回 FieldItemsList Interface
      $items->filterEmptyItems();
      $form['test_reference_field'] = $widget->form($items, $createForm, $form_state); //Builds the widget form and attach it to your form
      $form['test_reference_field']['#access'] = $items->access('edit');
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // TODO: Implement submitForm() method.
  }

  private function container() {
    return \Drupal::getContainer();
  }
}
