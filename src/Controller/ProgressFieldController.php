<?php

namespace Drupal\progress_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProgressFieldController extends ControllerBase {

    /**
     * Http Client
     *
     * @var GuzzleHttp\Client
     */
    protected $http_client;

    /**
     * Current Requset
     *
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $current_request;

    /**
     * 初始化控制器
     *
     * @param GuzzleHttp\Client
     *  Http Client
     * @param Symfony\Component\HttpFoundation\Request
     *  Current Request
     */
    public function __construct(Client $http_client, Request $current_request) {
        $this->http_client = $http_client;
        $this->current_request = $current_request;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client'),
            $container->get('request_stack')->getCurrentRequest()
        );
    }

    /**
     * 处理上传进度信息
     * @param String
     *  Upload Progress Key
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function progress($key) {
        $progress = array(
            'message' => t('Starting upload...'),
            'percentage' => -1,
        );
        
        $status = $this->getProgressInfo($key);
        if ($status['state'] == 'uploading') {
            // We set a message only when the upload is in progress.
            $progress['message'] = t('Uploading... (@current of @total)', array('@current' => format_size($status['received']), '@total' => format_size($status['size'])));
            $progress['percentage'] = round(100 * $status['received'] / $status['size']);
        }

        return new JsonResponse($progress);
    }

    /**
     * 获取上传进度信息
     * @param String
     *  Upload Progress Key
     *
     * @return String
     */
    public function getProgressInfo($key) {
        # Guzzle类库文档 http://docs.guzzlephp.org/en/latest/index.html
        $url = $this->current_request->getSchemeAndHttpHost() . '/progress';
        $response = $this->http_client->request('GET', $url, ['headers' => ['X-Progress-ID' => $key]]);
        $data = (string) $response->getBody();
        $data = preg_replace('/^\(|\);/', '', $data);
        
        return json_decode($data, TRUE);
    }

}