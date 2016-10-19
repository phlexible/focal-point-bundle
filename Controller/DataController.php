<?php

/*
 * This file is part of the phlexible focal point package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\FocalPointBundle\Controller;

use Phlexible\Bundle\GuiBundle\Response\ResultResponse;
use Phlexible\Component\MediaTemplate\Model\ImageTemplate;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Data controller.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 * @Route("/focalpoint")
 * @Security("is_granted('ROLE_FOCAL_POINT')")
 */
class DataController extends Controller
{
    /**
     * Get action.
     *
     * @param Request $request
     *
     * @return ResultResponse
     * @Route("/get", name="focalpoint_get")
     */
    public function getAction(Request $request)
    {
        $fileId = $request->get('file_id');
        $fileVersion = $request->get('file_version');
        $width = $request->get('width');
        $height = $request->get('height');

        $volumeManager = $this->get('phlexible_media_manager.volume_manager');

        $site = $volumeManager->getByFileId($fileId);
        $file = $site->findFile($fileId, $fileVersion);

        $calculator = $this->get('phlexible_focal_point.focalpoint_calculator');
        $focalpoint = $calculator->calculateDown($file, $width, $height);

        $data = array(
            'focalpoint_active' => $focalpoint->getStatus(),
            'focalpoint_x' => $focalpoint->getX(),
            'focalpoint_y' => $focalpoint->getY(),
        );

        return new ResultResponse(true, '', $data);
    }

    /**
     * Set action.
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

        $volumeManager = $this->get('phlexible_media_manager.volume_manager');

        $site = $volumeManager->getByFileId($fileId);
        $file = $site->findFile($fileId, $fileVersion);

        $pointX = $pointX !== null ? round($pointX) : null;
        $pointY = $pointY !== null ? round($pointY) : null;

        $calculator = $this->get('phlexible_focal_point.focalpoint_calculator');
        $focalpoint = $calculator->calculateUp($file, $width, $height, $pointActive, $pointX, $pointY);

        $file->setAttribute('focalpoint', array('active' => $focalpoint->getStatus(), 'x' => $focalpoint->getX(), 'y' => $focalpoint->getY()));
        $site->setFileAttributes($file, $file->getAttributes(), $this->getUser()->getId());

        $cropTemplateQueuer = $this->get('phlexible_focal_point.crop_template_queuer');
        $cropTemplateQueuer->queueCropTemplates($file);

        return new ResultResponse(true, 'Focal point saved.');
    }

    /**
     * Image action.
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

        $volumeManager = $this->get('phlexible_media_manager.volume_manager');

        $site = $volumeManager->getByFileId($fileId);
        $file = $site->findFile($fileId, $fileVersion);

        $template = new ImageTemplate();
        $template
            ->setParameter('width', 400)
            ->setParameter('height', 400)
            ->setParameter('method', 'fit')
            ->setParameter('scale', 'down')
            ->setParameter('format', 'jpg')
        ;

        $tempDir = $this->container->getParameter('kernel.cache_dir');
        $outFilename = $tempDir.$fileId.'_'.$fileVersion.'.jpg';

        $this->get('phlexible_media_template.applier.image')
            ->apply($template, $file, $file->getPhysicalPath(), $outFilename);


        $response = new BinaryFileResponse($outFilename, 200, array('Content-Type' => 'image/jpg'));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->getName());

        return $response;
    }

    /**
     * Templates action.
     *
     * @return JsonResponse
     * @Route("/templates", name="focalpoint_templates")
     */
    public function templatesAction()
    {
        $cropTemplateQueuer = $this->get('phlexible_focal_point.crop_template_queuer');
        $cropTemplates = $cropTemplateQueuer->getCropTemplates();

        $data = array();
        foreach ($cropTemplates as $cropTemplate) {
            $data[$cropTemplate->getKey().'___'.$cropTemplate->getId()] = array(
                'id' => $cropTemplate->getId(),
                'type' => 'image',
                'title' => $cropTemplate->getKey(),
                'width' => $cropTemplate->getWidth(),
                'height' => $cropTemplate->getHeight(),
            );
        }

        ksort($data);
        $data = array_values($data);

        $translator = $this->get('translator');

        array_unshift(
            $data,
            array(
                'id' => '_safe',
                'type' => 'safe',
                'title' => $translator->trans('focalpoint.safe_area', array(), 'gui', $this->getUser()->getInterfaceLanguage('en')),
                'width' => 0,
                'height' => 0,
            )
        );

        return new JsonResponse(array('templates' => $data));
    }
}
