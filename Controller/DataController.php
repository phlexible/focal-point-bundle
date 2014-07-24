<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\FocalPointBundle\Controller;

use Phlexible\Bundle\MediaCacheBundle\Queue\Batch;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Phlexible\Bundle\GuiBundle\Response\ResultResponse;
use Phlexible\Bundle\MediaTemplateBundle\Model\ImageTemplate;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Data controller
 *
 * @author Stephan Wentz <sw@brainbits.net>
 * @Route("/focalpoint")
 */
class DataController extends Controller
{
    /**
     * Get action
     *
     * @param Request $request
     *
     * @return ResultResponse
     * @Route("/get", name="focalpoint_get")
     * @Security("is_granted('focalpoint')")
     */
    public function getAction(Request $request)
    {
        $fileId = $request->get('file_id');
        $fileVersion = $request->get('file_version');
        $width = $request->get('width');
        $height = $request->get('height');

        $site  = $this->get('phlexible_media_site.site_manager')->getByFileId($fileId);
        $file  = $site->findFile($fileId, $fileVersion);

        $focalpoint = $file->getAttribute('focalpoint', array());
        $pointActive = !empty($focalpoint['active']) ? (int) $focalpoint['active'] : 0;
        $pointX      = !empty($focalpoint['x']) ? (int) $focalpoint['x'] : null;
        $pointY      = !empty($focalpoint['y']) ? (int) $focalpoint['y'] : null;

        if ($pointX !== null && $pointY !== null) {
            $imageAnalyzer = $this->get('phlexible_media_tool.image_analyzer');
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

        return new ResultResponse(true, '', $data);
    }

    /**
     * Set action
     *
     * @param Request $request
     *
     * @return ResultResponse
     * @Route("/set", name="focalpoint_set")
     */
    public function setAction(Request $request)
    {
        $fileId = $request->get('file_id');
        $fileVersion = $request->get('file_version');
        $pointActive = (int) $request->get('point_active');
        $pointX = (int) $request->get('point_x');
        $pointY = (int) $request->get('point_y');
        $width = (int) $request->get('width');
        $height = (int) $request->get('height');

        $site = $this->get('phlexible_media_site.site_manager')->getByFileId($fileId);
        $file = $site->findFile($fileId, $fileVersion);

        $pointX = $pointX !== null ? round($pointX) : null;
        $pointY = $pointY !== null ? round($pointY) : null;

        if ($pointX !== null && $pointY !== null) {
            $imageAnalyzer = $this->get('phlexible_media_tool.image_analyzer');
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
        $site->setFileAttributes($file, $file->getAttributes(), $this->getUser()->getId());

        $batch = new Batch();
        $batch->addFile($file);

        $cropTemplates = $this->getCropTemplates();
        foreach ($cropTemplates as $cropTemplate) {
            $batch->addTemplate($cropTemplate);
        }

        $batchResolver = $this->get('phlexible_media_cache.queue.batch_resolver');
        $queue = $batchResolver->resolve($batch);

        $queueManager = $this->get('phlexible_media_cache.queue_manager');
        foreach ($queue->all() as $queueItem) {
            $queueManager->updateQueueItem($queueItem);
        }

        return new ResultResponse(true, 'Focal point saved.');
    }

    /**
     * @param int     $imageWidth
     * @param int     $imageHeight
     * @param int     $tempWidth
     * @param int     $tempHeight
     * @param int     $pointX
     * @param int     $pointY
     * @param string  $mode
     *
     * @throws \Exception
     * @return array
     */
    private function _calcPoint($imageWidth, $imageHeight, $tempWidth, $tempHeight, $pointX, $pointY, $mode)
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
     *
     * @param Request $request
     *
     * @return Response
     * @Route("/image", name="focalpoint_image")
     */
    public function imageAction(Request $request)
    {
        $fileId = $request->get('file_id');
        $fileVersion = $request->get('file_version');

        $site  = $this->get('phlexible_media_site.site_manager')->getByFileId($fileId);
        $file  = $site->findFile($fileId);

        $template = new ImageTemplate();
        $template
            ->setParameter('width', 400)
            ->setParameter('height', 400)
            ->setParameter('method', 'fit')
            ->setParameter('scale', 'down')
            ->setParameter('format', 'jpg')
        ;

        $tempDir = $this->container->getParameter('kernel.cache_dir');
        $outFilename = $tempDir . $fileId . '_' . $fileVersion . '.jpg';

        $this->get('phlexible_media_template.applier.image')->apply($template, $file, $file->getPhysicalPath(), $outFilename);

        return new Response(file_get_contents($outFilename), 200, array('Content-type' => 'image/jpg'));
    }

    /**
     * Templates action
     *
     * @return JsonResponse
     * @Route("/templates", name="focalpoint_templates")
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

        $translator = $this->get('translator');

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

        return new JsonResponse(array('templates' => $data));
    }

    /**
     * @return ImageTemplate[]
     */
    private function getCropTemplates()
    {
        return array_filter(
            $this->get('phlexible_media_template.template_manager')->findAll(),
            function($template) {
                return $template instanceof ImageTemplate && $template->getMethod() === 'crop';
            }
        );
    }
}