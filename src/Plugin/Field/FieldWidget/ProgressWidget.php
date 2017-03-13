<?php
namespace Drupal\progress_field\Plugin\Field\FieldWidget;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Drupal\file\Entity\File;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_generic_progress' widget.
 *
 * @FieldWidget(
 *   id = "file_generic_progress",
 *   label = @Translation("File with progress"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */

class ProgressWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    # 检查是否开启session upload progress功能
    if(!ini_get('session.upload_progress.enabled')) {
      drupal_set_message(t('Session.upload_progress.enabled must be enabled.'), 'error');
    }

    $element['progress_indicator'] = array(
      '#type' => 'radios',
      '#title' => t('Progress indicator'),
      '#options' => array(
        'throbber' => t('Throbber'),
        'bar' => t('Bar with progress meter'),
      ),
      '#default_value' => $this->getSetting('progress_indicator'),
      '#description' => t('The throbber display does not show the status of uploads but takes up less space. The progress bar is helpful for monitoring progress on large uploads.'),
      '#weight' => 16,
      #'#access' => ini_get('session.upload_progress.enabled'),
    );
    return $element;
  }

  /**
   * 修改文件字段，设置上传进度条
   * HOOK_field_widget_WIDGET_TYPE_form_alter
   * WIDGET_TYPE 参考 https://api.drupal.org/comment/60724#comment-60724
   */
  public static function process($element, FormStateInterface $form_state, $form) {

    $element = parent::process($element, $form_state, $form);
    
    if ($element['#progress_indicator'] == 'bar') {
        $upload_progress_key = mt_rand();
        $element['UPLOAD_IDENTIFIER'] = [
            '#type' => 'hidden',
            '#value' => $upload_progress_key,
            '#attributes' => ['class' => ['file-progress']],
            // Uploadprogress extension requires this field to be at the top of
            // the form.
            '#weight' => -20,
        ];
        # 为表单提交路径添加一个key
        # 表单提交的时候同时提交key，获取进度的时候需要这个key作为凭证
        $element['upload_button']['#ajax']['options']['query']['X-Progress-ID'] = $upload_progress_key;
        // Add the upload progress callback.
        # 配置获取进度的URL
        $element['upload_button']['#ajax']['progress']['url'] = Url::fromRoute('progress_field.progress', ['key' => $upload_progress_key]);
    }
    
    return $element;
  }

  /**
   *  该方法在生成表单项目（初次生成，valueCallback之后生成）和处理文件时多次调用。
   *  需要注意内容不要被重复使用
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $post = \Drupal::request()->request->all();
    $validateSize = current($element['#upload_validators']['file_validate_size']);
    
    # 判断新提交的文件
    # 如果是通过Nginx上传的文件
    if(isset($post['tmp_file_name']) && isset($post['tmp_file_path']) && empty($post['fid'])) {
      # 检查文件大小
      if($post['tmp_file_size'] > $validateSize) {
        $form_state->setError($element, t('The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.', ['%file' => $post['tmp_file_name'], '%maxsize' => format_size($validateSize)]));
      }
      else {
        $directory = $element['#upload_location'];
        # 检查目标目录是否存在
        if(file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
          $post['tmp_file_name'] = self::renameChinese($post['tmp_file_name']);
          # 先把文件移动到目标目录
          if($path = file_unmanaged_move($post['tmp_file_path'], $directory . '/' . $post['tmp_file_name'])) {
            # 创建一个File对象
            $values = array(
              'uid' => $user->id(),
              'status' => 0,
              'filename' => $post['tmp_file_name'],
              'uri' => $path,
              'filesize' => $post['tmp_file_size'],
              'filemime' => $post['tmp_file_content_type']
            );
            $file = File::create($values);
            $file->save();
            $input['fids'] = $file->id();
            # 把保存过的文件放入 $_POST 数据中，第二次调用时不需要再创建文件。
            \Drupal::request()->request->add(['fid' => $file->id()]);
          }
        } 
      }
    }
    elseif(!empty($post['fid'])) {
      # 单个文件时，会返回上传的文件，所以需要设置表单默认值
      $element['#default_value']['fids'][] = array($post['fid']);
      $input = FALSE;
      # 多个文件时，会返回空白的表单，不需要设置默认值
      \Drupal::request()->request->remove('fid');
      \Drupal::request()->request->remove('tmp_file_path');
    }
    return parent::value($element, $input, $form_state);
  }

  static public function renameChinese($name) {
    if(preg_match("/[\x7f-\xff]/", $name)) {
      $suf = end(explode('.', $name));
      return md5($name) . '.' . $suf;
    }

    return $name;
  }
}