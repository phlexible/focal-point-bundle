<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Phlexible\GuiComponent\Response\ResultResponse;
use Phlexible\MediaTemplatesComponent\Template\ImageTemplate;
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

        $site  = $this->get('mediasite.manager')->getByFileId($fileId);
        $file  = $site->findFile($fileId, $fileVersion);

        $focalpoint = $file->getAttribute('focalpoint', array());
        $pointActive = !empty($focalpoint['active']) ? (integer) $focalpoint['active'] : 0;
        $pointX      = !empty($focalpoint['x']) ? (integer) $focalpoint['x'] : null;
        $pointY      = !empty($focalpoint['y']) ? (integer) $focalpoint['y'] : null;

        if ($pointX !== null && $pointY !== null) {
            $imageAnalyzer = $this->get('mediatools.image_analyzer');
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

        return new ResultResponse(true, $data);
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
        $pointActive = (integer) $request->get('point_active');
        $pointX = (integer) $request->get('point_x');
        $pointY = (integer) $request->get('point_y');
        $width = (integer) $request->get('width');
        $height = (integer) $request->get('height');

        $site = $this->get('mediasite.manager')->getByFileId($fileId);
        $file = $site->findFile($fileId, $fileVersion);

        $pointX = $pointX !== null ? round($pointX) : null;
        $pointY = $pointY !== null ? round($pointY) : null;

        if ($pointX !== null && $pointY !== null) {
            $imageAnalyzer = $this->get('mediatools.image_analyzer');
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

        return new ResultResponse(true, 'Focal point saved.');
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

        $site  = $this->get('mediasite.manager')->getByFileId($fileId);
        $file  = $site->findFile($fileId);

        $template = new ImageTemplate();
        $template
            ->setParameter('width', 400)
            ->setParameter('height', 400)
            ->setParameter('method', 'fit')
            ->setParameter('scale', 'down')
            ->setParameter('format', 'jpg')
        ;

        $tempDir = $this->getParameter('kernel.cache_dir');
        $outFilename = $tempDir . $fileId . '_' . $fileVersion . '.jpg';

        $this->get('mediatemplates.applier.image')->apply($template, $file, $file->getPhysicalPath(), $outFilename);

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
            $this->get('mediatemplates.repository')->findAll(),
            function($template) {
                return $template instanceof ImageTemplate && $template->getMethod() === 'crop';
            }
        );
    }
}