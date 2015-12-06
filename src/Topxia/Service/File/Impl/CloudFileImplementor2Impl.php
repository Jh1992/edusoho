<?php
namespace Topxia\Service\File\Impl;

use Topxia\Common\FileToolkit;
use Topxia\Common\ArrayToolkit;
use Topxia\Service\Common\BaseService;
use Topxia\Service\File\FileImplementor2;
use Topxia\Service\CloudPlatform\CloudAPIFactory;

class CloudFileImplementor2Impl extends BaseService implements FileImplementor2
{
    public function getFile($file)
    {
        $api       = CloudAPIFactory::create();
        $cloudFile = $api->get("/resources/{$file['globalId']}");

        return $this->mergeCloudFile($file, $cloudFile);
    }

    public function findFiles($files)
    {
        $globalIds  = ArrayToolkit::column($files, 'globalId');
        $api        = CloudAPIFactory::create();
        $result     = $api->get("/resources?nos=".implode(',', $globalIds));
        $cloudFiles = $result['data'];
        $cloudFiles = ArrayToolkit::index($cloudFiles, 'id');

        foreach ($files as $i => $file) {
            if (empty($cloudFiles[$file['globalId']])) {
                continue;
            }

            $files[$i] = $this->mergeCloudFile($file, $cloudFiles[$file['globalId']]);
        }

        return $files;
    }

    public function resumeUpload($globalId, $file)
    {
        $params = array(
            'bucket' => $file['bucket'],
            'extno'  => $file['extno'],
            'size'   => $file['size'],
            'name'   => $file['name'],
            'hash'   => $file['hash']
        );

        $api     = CloudAPIFactory::create();
        $resumed = $api->post("/resources/{$globalId}/upload_resume", $params);

        if (empty($resumed['resumed']) || ($resumed['resumed'] !== 'ok')) {
            return null;
        }

        return $resumed;
    }

    public function prepareUpload($params)
    {
        $file             = array();
        $file['filename'] = empty($params['fileName']) ? '' : $params['fileName'];

        $pos         = strrpos($file['filename'], '.');
        $file['ext'] = empty($pos) ? '' : substr($file['filename'], $pos + 1);

        $file['fileSize']   = empty($params['fileSize']) ? 0 : $params['fileSize'];
        $file['status']     = 'uploading';
        $file['targetId']   = $params['targetId'];
        $file['targetType'] = $params['targetType'];
        $file['storage']    = 'cloud';

        $file['type'] = FileToolkit::getFileTypeByExtension($file['ext']);

        $file['updatedUserId'] = empty($params['userId']) ? 0 : $params['userId'];
        $file['updatedTime']   = time();
        $file['createdUserId'] = $file['updatedUserId'];
        $file['createdTime']   = $file['updatedTime'];

        // 以下参数在cloud模式下弃用，填充随机值
        $keySuffix           = date('Ymdhis').'-'.substr(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36), 0, 16);
        $key                 = "{$params['targetType']}-{$params['targetId']}/{$keySuffix}";
        $file['hashId']      = $key;
        $file['etag']        = $file['hashId'];
        $file['convertHash'] = $file['hashId'];

        return $file;
    }

    public function initUpload($file)
    {
        $params = array(
            "extno"  => $file['extno'],
            "bucket" => $file['bucket'],
            "key"    => $file['key'],
            "hash"   => $file['hash'],
            'name'   => $file['name'],
            'size'   => $file['size']
        );

        $api = CloudAPIFactory::create();
        return $api->post('/resources/upload_init', $params);
    }

    public function getDownloadFile($file)
    {
        $api              = CloudAPIFactory::create();
        $download         = $api->get("/files/{$file['globalId']}/download");
        $download['type'] = 'url';
        return $download;
    }

    public function finishedUpload($globalId, $params)
    {
        $params = array(
            "length" => $params['length'],
            'name'   => $params['name'],
            'size'   => $params['size']
        );
        $api = CloudAPIFactory::create();
        return $api->post("/resources/{$globalId}/upload_finish", $params);
    }

    private function mergeCloudFile($file, $cloudFile)
    {
        $file['hashId']   = $cloudFile['reskey'];
        $file['fileSize'] = $cloudFile['size'];

        $statusMap = array(
            'none'       => 'none',
            'waiting'    => 'waiting',
            'processing' => 'doing',
            'ok'         => 'success',
            'error'      => 'error'
        );

        $file['convertStatus'] = $statusMap[$cloudFile['processStatus']];

        if (empty($cloudFile['directives']['output'])) {
            $file['convertParams'] = array();
            $file['metas2']        = array();
        } else {
            if ($file['type'] == 'video') {
                $file['convertParams'] = array(
                    'convertor'    => $cloudFile['directives']['output'],
                    'videoQuality' => $cloudFile['directives']['videoQuality'],
                    'audioQuality' => $cloudFile['directives']['audioQuality']
                );
                $file['metas2'] = $cloudFile['metas']['levels'];
            } elseif ($file['type'] == 'ppt') {
                $file['convertParams'] = array(
                    'convertor' => $cloudFile['directives']['output']
                );
                $file['metas2'] = $cloudFile['metas'];
            } elseif ($file['type'] == 'document') {
                $file['convertParams'] = array(
                    'convertor' => $cloudFile['directives']['output']
                );
                $file['metas2'] = $cloudFile['metas'];
            } elseif ($file['type'] == 'audio') {
                $file['convertParams'] = array(
                    'convertor'    => $cloudFile['directives']['output'],
                    'videoQuality' => 'normal',
                    'audioQuality' => 'normal'
                );
                $file['metas2'] = $cloudFile['metas']['levels'];
            }
        }

        return $file;
    }
}
