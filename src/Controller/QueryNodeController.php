<?php
namespace Drupal\custom_menus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\lingotek\Exception\LingotekApiException;
use Drupal\lingotek\Lingotek;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManager;
use Drupal\lingotek\LanguageLocaleMapper;
use http\Client\Response;

class QueryNodeController extends ControllerBase {
  protected $entityTypeManager;
  protected $languageManager;
  protected $languageLocaleMapper;

  /**
   * QueryNodeController constructor.
   * @param EntityTypeManager $entityQuery
   */
  public function __construct(EntityTypeManager $entityTypeManager, LanguageManager $languageManager, LanguageLocaleMapper $languageLocaleMapper)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->languageLocaleMapper = $languageLocaleMapper;
  }

  /**
   * @param ContainerInterface $container
   * @return QueryNodeController|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('lingotek.language_locale_mapper')
    );
  }

  public function checkState() {
    $t = \Drupal::service('lingotek.content_translation');
    $node = \Drupal::service('entity_type.manager')->getStorage('node')->load(11);
    $i = 1;
    $x = 1;
    while($i == 1) {
      // 检查上传状态
      $state = $t->checkSourceStatus($node);
      if(!$state) {
        // 上传内容
        $t->uploadDocument($node, NULL);
        sleep(5);
        continue;
      }
      // 获取所有支持的语言
      $languages = $this->languageManager->getLanguages();
      // 获取目标语言翻译状态
      $t_status = $t->getTargetStatuses($node);
      $state = true;
      foreach($t_status as $tk => $tv) {
        if($tv == Lingotek::STATUS_REQUEST) {
          $state = FALSE;
          break;
        }
      }

      if(!$state) {
        // 请求翻译
        $t->requestTranslations($node);
        sleep(5);
        continue;
      } else {
        foreach ($languages as $langcode => $language) {
          $nlid = $node->language()->getId();
          if ($langcode !== $nlid) {
            $drupal_language = $this->languageLocaleMapper->getConfigurableLanguageForLocale($langcode);
            $locale = $this->languageLocaleMapper->getLocaleForLangcode($langcode);
            if ($t->checkTargetStatus($node, $drupal_language->id()) === Lingotek::STATUS_READY) {
              try {
                // 下载翻译
                $t->downloadDocument($node, $locale);
                $x ++;
                if($x == count($languages)) $i = 0;
              }
              catch (LingotekApiException $exception) {
                $this->messenger()->addError(t('The download for @entity_type %title translation failed. Please try again.', [
                  '@entity_type' => $node->getEntityTypeId(),
                  '%title' => $node->label(),
                ]));
              }
            }
          }
        }
      }
    }
  }
}
