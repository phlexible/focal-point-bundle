<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent\Controller;

use Phlexible\CoreComponent\Controller\Action\Action;
use Phlexible\MediaTemplatesComponent\Template\ImageTemplate;

/**
 * Data controller
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class DataController extends Action
{
    /**
     * Get action
     */
    public function getAction()
    {
        $fileId = $this->getParam('file_id');
        $fileVersion = $this->getParam('file_version');
        $width = $this->getParam('width');
        $height = $this->getParam('height');

        $site  = $this->getContainer()->get('mediasite.manager')->getByFileId($fileId);
        $file  = $site->findFile($fileId, $fileVersion);

        $focalpoint = $file->getAttribute('focalpoint', array());
        $pointActive = !empty($focalpoint['active']) ? (integer) $focalpoint['active'] : 0;
        $pointX      = !empty($focalpoint['x']) ? (integer) $focalpoint['x'] : null;
        $pointY      = !empty($focalpoint['y']) ? (integer) $focalpoint['y'] : null;

        if ($pointX !== null && $pointY !== null) {
            $imageAnalyzer = $this->getContainer()->get('mediatools.image_analyzer');
            $info = $imageAnalyzer->analyze($file->getPhysicalPath());

            list($pointX, $pointY) = $this->_calcPoint(
                $info->getWidth(),
                $info->getHeight(),
                $width,
                $height,
                $pointX,
                $pointY,
                'down'
            );
        }

        $data = array(
            'focalpoint_active' => $pointActive,
            'focalpoint_x'      => $pointX,
            'focalpoint_y'      => $pointY,
        );

        $this->_response->setResult(true, $this->hasParam('id') ? $this->getParam('id') : null, null, $data);
    }

    /**
     * Set action
     */
    public function setAction()
    {
        $fileId = $this->getParam('file_id');
        $fileVersion = $this->getParam('file_version');
        $pointActive = (integer) $this->getParam('point_active');
        $pointX = (integer) $this->getParam('point_x');
        $pointY = (integer) $this->getParam('point_y');
        $width = (integer) $this->getParam('width');
        $height = (integer) $this->getParam('height');

        $site = $this->getContainer()->get('mediasite.manager')->getByFileId($fileId);
        $file = $site->findFile($fileId, $fileVersion);

        $pointX = $pointX !== null ? round($pointX) : null;
        $pointY = $pointY !== null ? round($pointY) : null;

        if ($pointX !== null && $pointY !== null) {
            $imageAnalyzer = $this->getContainer()->get('mediatools.image_analyzer');
            $info = $imageAnalyzer->analyze($file->getPhysicalPath());

            list($pointX, $pointY) = $this->_calcPoint(
                $info->getWidth(),
                $info->getHeight(),
                $width,
                $height,
                $pointX,
                $pointY,
                'up'
            );
        }

        $file->setAttribute('focalpoint', array('active' => $pointActive, 'x' => $pointX, 'y' => $pointY));
        $site->setFileAttributes($file, $file->getAttributes());

        $cropTemplates = $this->getCropTemplates();
        $templateKeys = array();
        foreach ($cropTemplates as $cropTemplate) {
            $templateKeys[] = $cropTemplate->getKey();
        }

        /*
        $batch = new \Phlexible\MediaCacheComponent\Queue\Batch();
        $batch
            ->file($file)
            ->templates($templateKeys);
        $batchQueuer = $this->getContainer()->get('mediacacheBatchQueuer');
        $cnt = $batchQueuer->add($batch);
        */

        $this->_response->setResult(true, $this->hasParam('id') ? $this->getParam('id') : null, 'Focal point saved.');
    }

    /**
     * @param integer $imageWidth
     * @param integer $imageHeight
     * @param integer $tempWidth
     * @param integer $tempHeight
     * @param integer $pointX
     * @param integer $pointY
     * @param string  $mode
     *
     * @throws \Exception
     * @return array
     */
    protected function _calcPoint($imageWidth, $imageHeight, $tempWidth, $tempHeight, $pointX, $pointY, $mode)
    {
        //echo 'image: '.$attributes->width." ".$attributes->height."<br>";
        //echo 'point: '.$pointX." ".$pointY."<br>";

        if ($tempWidth < $imageWidth && $tempHeight < $imageHeight) {
            $ratio = 1;
            if ($tempWidth == 400) {
                $ratio = $imageWidth / 400;
            } elseif ($tempHeight == 400) {
                $ratio = $imageHeight / 400;
            }

            if ($mode === 'up') {
                $pointX = round($pointX * $ratio);
                $pointY = round($pointY * $ratio);
            } elseif ($mode === 'down') {
                $pointX = round($pointX / $ratio);
                $pointY = round($pointY / $ratio);
            } else {
                throw new \Exception("unknown mode $mode");
            }

            //echo 'ratio: '.$ratio."<br>";
            //echo 'calulated: ' . $pointX." ".$pointY;
        }

        if ($pointX < 0) {
            $pointX = 0;
        } elseif ($pointX > $imageWidth) {
            $pointX = $imageWidth;
        }

        if ($pointY < 0) {
            $pointY = 0;
        } elseif ($pointY > $imageHeight) {
            $pointY = $imageHeight;
        }

        return array($pointX, $pointY);
    }

    /**
     * Image action
     */
    public function imageAction()
    {
        $fileId = $this->getParam('file_id');
        $fileVersion = $this->getParam('file_version');

        try {
            $site  = $this->getContainer()->get('mediasite.manager')->getByFileId($fileId);
            $file  = $site->findFile($fileId);

            $template = new ImageTemplate();
            $template
                ->setParameter('width', 400)
                ->setParameter('height', 400)
                ->setParameter('method', 'fit')
                ->setParameter('scale', 'down')
                ->setParameter('format', 'jpg')
            ;

            $tempDir = $this->getContainer()->getParameter('kernel.cache_dir');
            $outFilename = $tempDir . $fileId . '_' . $fileVersion . '.jpg';

            $this->getContainer()->get('mediatemplates.applier.image')->apply($template, $file, $file->getPhysicalPath(), $outFilename);

            $this->_response
                ->setContentType('image/jpg')
                ->setFile($outFilename);
        } catch (\Exception $e) {
            $this->_response
                ->setHttpResponseCode(500)
                ->setBody($e->getMessage());
        }
    }

    /**
     * Templates action
     */
    public function templatesAction()
    {
        $cropTemplates = $this->getCropTemplates();

        $data = array();
        foreach ($cropTemplates as $cropTemplate) {
            $data[$cropTemplate->getKey() . '___' . $cropTemplate->getId()] = array(
                'id'     => $cropTemplate->getId(),
                'type'   => 'image',
                'title'  => $cropTemplate->getKey(),
                'width'  => $cropTemplate->getWidth(),
                'height' => $cropTemplate->getHeight(),
            );
        }

        ksort($data);
        $data = array_values($data);

        $translator = $this->getContainer()->get('translator');

        array_unshift(
            $data,
            array(
                'id'     => '_safe',
                'type'   => 'safe',
                'title'  => $translator->trans('focalpoint.safe_area', array(), 'gui', $this->getUser()->getInterfaceLanguage('en')),
                'width'  => 0,
                'height' => 0
            )
        );

        $this->_response->setAjaxPayload(array('templates' => $data));
    }

    /**
     * @return ImageTemplate[]
     */
    private function getCropTemplates()
    {
        return array_filter(
            $this->getContainer()->get('mediatemplates.repository')->findAll(),
            function($template) {
                return $template instanceof ImageTemplate && $template->getMethod() === 'crop';
            }
        );
    }
}