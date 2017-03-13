<?php

namespace Drupal\progress_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProgressFieldController extends ControllerBase {

    public function progress($key) {
        $progress = array(
            'message' => t('Starting upload...'),
            'percentage' => -1,
        );
        
        $status = self::getProgressInfo($key);
        if ($status['state'] == 'uploading') {
            // We set a message only when the upload is in progress.
            $progress['message'] = t('Uploading... (@current of @total)', array('@current' => format_size($status['received']), '@total' => format_size($status['size'])));
            $progress['percentage'] = round(100 * $status['received'] / $status['size']);
        }

        return new JsonResponse($progress);
    }

    /**
     * 获取上传进度信息
     *
     */
    public static function getProgressInfo($key) {
        # Guzzle类库文档 http://docs.guzzlephp.org/en/latest/index.html
        $client = \Drupal::httpClient();
        $url = \Drupal::request()->getSchemeAndHttpHost() . '/progress';
        $response = $client->request('GET', $url, ['headers' => ['X-Progress-ID' => $key]]);
        $data = (string) $response->getBody();
        $data = preg_replace('/^\(|\);/', '', $data);
        
        return json_decode($data, TRUE);
    }

}