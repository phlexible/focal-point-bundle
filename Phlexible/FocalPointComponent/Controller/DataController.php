<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent\Controller;

use Phlexible\CoreComponent\Controller\Action\Action;
use Phlexible\MediaTemplatesComponent\ImageTemplate;

/**
 * Data controller
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class Focalpoint_DataController extends Action
{
    public function getAction()
    {
        try
        {
            // ------------------------------
            // start: input validation
            $filters = array("StripTags", "StringTrim");

            $validators = array(
                'file_id' => array(
                    new \Brainbits_Validate_Uuid(),
                    'presence' => 'required'
                ),
                'file_version' => array(
                    'Digits',
                    'presence' => 'required'
                ),
                'width' => array(
                    'Digits',
                    'presence' => 'required',
                ),
                'height' => array(
                    'Digits',
                    'presence' => 'required',
                ),
            );

            $fi = new \Brainbits_Filter_Input($filters, $validators, $this->_getAllParams());

            if (!$fi->isValid())
            {
                throw new \Brainbits_Filter_Exception('Error occured', 0, $fi);
            }
            // end: input validation
            // ------------------------------

            $site  = $this->getContainer()->mediaSiteManager;
            $file  = $site->getByFileId($fi->file_id)->getFilePeer()->getById($fi->file_id);
            $asset = $file->getAsset();

            $attributes = $asset->getAttributes();

            $pointActive = (int)$attributes->focalpoint_active;
            $pointX      = $attributes->focalpoint_x ? $attributes->focalpoint_x : null;
            $pointY      = $attributes->focalpoint_y ? $attributes->focalpoint_y : null;

            if ($pointX !== null && $pointY !== null)
            {
                list($pointX, $pointY) = $this->_calcPoint(
                    $attributes->width,
                    $attributes->height,
                    $fi->width,
                    $fi->height,
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

            $result = \MWF_Ext_Result::encode(true, $this->_hasParam('id') ? $this->_getParam('id') : null, null, $data);
        }
        catch (\Brainbits_Filter_Exception $e)
        {
            $result = \MWF_Ext_Result::encode(false, $this->_hasParam('id') ? $this->_getParam('id') : null, $e->getFilterMessagesAsString());
        }
        catch (\Exception $e)
        {
            $result = \MWF_Ext_Result::encode(false, $this->_hasParam('id') ? $this->_getParam('id') : null, $e->getMessage());
        }

        $this->_response->setAjaxPayload($result);
    }

    public function setAction()
    {
        try
        {
            // ------------------------------
            // start: input validation
            $filters = array("StripTags", "StringTrim");

            $validators = array(
                'file_id' => array(
                    new \Brainbits_Validate_Uuid(),
                    'presence' => 'required'
                ),
                'file_version' => array(
                    'Digits',
                    'presence' => 'required'
                ),
                'point_active' => array(
                    'presence' => 'required',
                    'allowEmpty' => true,
                    'default' => 0
                ),
                'point_x' => array(
                    'presence' => 'required',
                    'allowEmpty' => true,
                    'default' => 0
                ),
                'point_y' => array(
                    'presence' => 'required',
                    'allowEmpty' => true,
                    'default' => 0
                ),
                'width' => array(
                    'presence' => 'required',
                ),
                'height' => array(
                    'presence' => 'required',
                ),
            );

            $fi = new \Brainbits_Filter_Input($filters, $validators, $this->_getAllParams());

            if (!$fi->isValid())
            {
                throw new \Brainbits_Filter_Exception('Error occured', 0, $fi);
            }
            // end: input validation
            // ------------------------------

            $site = $this->getContainer()->mediaSiteManager;
            $file = $site->getByFileId($fi->file_id)->getFilePeer()->getById($fi->file_id);
            $asset = $file->getAsset();

            $attributes = $asset->getAttributes();

            $pointActive = (int)$fi->point_active;
            $pointX      = $fi->point_x !== null ? round($fi->point_x) : null;
            $pointY      = $fi->point_y !== null ? round($fi->point_y) : null;

            if ($pointX !== null && $pointY !== null)
            {
                list($pointX, $pointY) = $this->_calcPoint(
                    $attributes->width,
                    $attributes->height,
                    $fi->width,
                    $fi->height,
                    $pointX,
                    $pointY,
                    'up'
                );
            }

            $attributes->focalpoint_active = $pointActive;
            $attributes->focalpoint_x      = $pointX;
            $attributes->focalpoint_y      = $pointY;

            $asset->storeAttributes($attributes);

            $cropTemplates = $this->_getCropTemplates();
            $templateKeys = array();
            foreach ($cropTemplates as $cropTemplate)
            {
                $templateKeys[] = $cropTemplate->key;
            }

            $batch = new \Phlexible\MediaCacheComponent\Queue\Batch();
            $batch
                ->file($file)
                ->templates($templateKeys);
            $batchQueuer = $this->getContainer()->mediaCacheBatchQueuer;
            $cnt = $batchQueuer->add($batch);

            $result = \MWF_Ext_Result::encode(true, $this->_hasParam('id') ? $this->_getParam('id') : null, 'Focal point saved.');
        }
        catch (\Brainbits_Filter_Exception $e)
        {
            $result = \MWF_Ext_Result::encode(false, $this->_hasParam('id') ? $this->_getParam('id') : null, $e->getFilterMessagesAsString());
        }
        catch (\Exception $e)
        {
            $result = \MWF_Ext_Result::encode(false, $this->_hasParam('id') ? $this->_getParam('id') : null, $e->getMessage());
        }

        $this->_response->setAjaxPayload($result);
    }

    protected function _calcPoint($imageWidth, $imageHeight, $tempWidth, $tempHeight, $pointX, $pointY, $mode)
    {
        #echo 'image: '.$attributes->width." ".$attributes->height."<br>";
        #echo 'point: '.$pointX." ".$pointY."<br>";

        if ($tempWidth < $imageWidth && $tempHeight < $imageHeight)
        {
            if ($tempWidth == 400)
            {
                $ratio = $imageWidth / 400;
            }
            elseif ($tempHeight == 400)
            {
                $ratio = $imageHeight / 400;
            }

            if ($mode === 'up')
            {
                $pointX = round($pointX * $ratio);
                $pointY = round($pointY * $ratio);
            }
            elseif ($mode === 'down')
            {
                $pointX = round($pointX / $ratio);
                $pointY = round($pointY / $ratio);
            }
            else
            {
                die('unknown mode');
            }

            #echo 'ratio: '.$ratio."<br>";
            #echo 'calulated: ' . $pointX." ".$pointY;
        }

        if ($pointX < 0)
        {
            $pointX = 0;
        }
        elseif ($pointX > $imageWidth)
        {
            $pointX = $imageWidth;
        }

        if ($pointY < 0)
        {
            $pointY = 0;
        }
        elseif ($pointY > $imageHeight)
        {
            $pointY = $imageHeight;
        }

        return array($pointX, $pointY);
    }

    public function imageAction()
    {
        try
        {
            // ------------------------------
            // start: input validation
            $filters = array("StripTags", "StringTrim");

            $validators = array(
                'file_id' => array(
                    new \Brainbits_Validate_Uuid(),
                    'presence' => 'required'
                ),
                'file_version' => array(
                    'Digits',
                    'presence' => 'required'
                ),
            );

            $fi = new \Brainbits_Filter_Input($filters, $validators, $this->getAllParams());

            if (!$fi->isValid())
            {
                throw new \Brainbits_Filter_Exception('Error occured', 0, $fi);
            }
            // end: input validation
            // ------------------------------

            $site  = $this->getContainer()->mediaSiteManager;
            $file  = $site->getByFileId($fi->file_id)->getFilePeer()->getById($fi->file_id);
            $filePath = $file->getFilePath();

            $template = new ImageTemplate();
            $template->setParameter('width', 400);
            $template->setParameter('height', 400);
            $template->setParameter('method', 'fit');
            $template->setParameter('scale', \Brainbits_Toolkit_Image_Imagemagick::SCALE_DOWN);

            $toolkit = $template->getAppliedToolkit($filePath);
            $toolkit->setFormat('jpg');

            $tempDir = $this->getContainer()->getParameter(':app.temp_dir');
            $filename = $toolkit->save($tempDir . $fi->file_id . '_' . $fi->file_version, true);

            $this->_response
                ->setContentType('image/jpg')
                ->setFile($filename);
        }
        catch (\Exception $e)
        {
            $this->_response
                ->setHttpResponseCode(500)
                ->setBody($e->getMessage());
        }
    }

    public function templatesAction()
    {
        $cropTemplates = $this->_getCropTemplates();

        $data = array();
        foreach ($cropTemplates as $cropTemplate)
        {
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

        $t9n = $this->getContainer()->t9n;

        array_unshift(
            $data,
            array(
                'id'     => '_safe',
                'type'   => 'safe',
                'title'  => $t9n->focalpoint->safe_area,
                'width'  => 0,
                'height' => 0
            )
        );

        $this->_response->setAjaxPayload(array('templates' => $data));
    }

    protected function _getCropTemplates()
    {
        $templates = $this->getContainer()->mediatemplatesRepository->findAll();

        $cropTemplates = array();
        foreach ($templates as $template)
        {
            if (!$template instanceof ImageTemplate)
            {
                continue;
            }

            if ($template->getMethod() !== \Brainbits_Toolkit_Image_Interface::RESIZE_METHOD_CROP)
            {
                continue;
            }

            $cropTemplates[] = $template;
        }

        return $cropTemplates;
    }
}