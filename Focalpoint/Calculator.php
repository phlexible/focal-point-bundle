<?php

/*
 * This file is part of the phlexible elastica package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\FocalPointBundle\Focalpoint;

use Phlexible\Bundle\FocalPointBundle\Exception\UnknownModeException;
use Phlexible\Component\ImageAnalyzer\ImageAnalyzer;
use Phlexible\Component\Volume\Model\FileInterface;

/**
 * Focalpoint calculator.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class Calculator
{
    const MODE_DOWN = 'down';
    const MODE_UP = 'up';

    /**
     * @var ImageAnalyzer
     */
    private $imageAnalyzer;

    /**
     * @param ImageAnalyzer $imageAnalyzer
     */
    public function __construct(ImageAnalyzer $imageAnalyzer)
    {
        $this->imageAnalyzer = $imageAnalyzer;
    }

    /**
     * @param FileInterface $file
     * @param int           $width
     * @param int           $height
     *
     * @return Focalpoint
     */
    public function calculateDown(FileInterface $file, $width, $height)
    {
        $focalpoint = $file->getAttribute('focalpoint', array());
        $pointStatus = !empty($focalpoint['active']) ? (int) $focalpoint['active'] : 0;
        $pointX = !empty($focalpoint['x']) ? round((int) $focalpoint['x']) : null;
        $pointY = !empty($focalpoint['y']) ? round((int) $focalpoint['y']) : null;

        list($x, $y) = $this->calculate($file, $width, $height, $pointX, $pointY, self::MODE_DOWN);

        return new Focalpoint($pointStatus, $x, $y);
    }

    /**
     * @param FileInterface $file
     * @param int           $width
     * @param int           $height
     * @param int           $pointStatus
     * @param int           $pointX
     * @param int           $pointY
     *
     * @return Focalpoint
     */
    public function calculateUp(FileInterface $file, $width, $height, $pointStatus, $pointX, $pointY)
    {
        list($x, $y) = $this->calculate($file, $width, $height, $pointX, $pointY, self::MODE_UP);

        return new Focalpoint($pointStatus, $x, $y);
    }

    /**
     * @param FileInterface $file
     * @param int           $width
     * @param int           $height
     * @param int           $pointX
     * @param int           $pointY
     * @param string        $mode
     *
     * @return Focalpoint
     */
    public function calculate(FileInterface $file, $width, $height, $pointX, $pointY, $mode)
    {
        if ($pointX !== null && $pointY !== null) {
            $info = $this->imageAnalyzer->analyze($file->getPhysicalPath());

            list($pointX, $pointY) = $this->_calcPoint(
                $info->getWidth(),
                $info->getHeight(),
                $width,
                $height,
                $pointX,
                $pointY,
                $mode
            );
        }

        return array($pointX, $pointY);
    }

    /**
     * @param int    $imageWidth
     * @param int    $imageHeight
     * @param int    $tempWidth
     * @param int    $tempHeight
     * @param int    $pointX
     * @param int    $pointY
     * @param string $mode
     *
     * @throws UnknownModeException
     *
     * @return array
     */
    private function _calcPoint($imageWidth, $imageHeight, $tempWidth, $tempHeight, $pointX, $pointY, $mode)
    {
        //echo 'image: '.$attributes->width." ".$attributes->height."<br>";
        //echo 'point: '.$pointX." ".$pointY."<br>";

        if ($tempWidth < $imageWidth && $tempHeight < $imageHeight) {
            $ratio = 1;
            if ($tempWidth === 400) {
                $ratio = $imageWidth / 400;
            } elseif ($tempHeight === 400) {
                $ratio = $imageHeight / 400;
            }

            if ($mode === self::MODE_UP) {
                $pointX = round($pointX * $ratio);
                $pointY = round($pointY * $ratio);
            } elseif ($mode === self::MODE_DOWN) {
                $pointX = round($pointX / $ratio);
                $pointY = round($pointY / $ratio);
            } else {
                throw new UnknownModeException("Unknown mode $mode");
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
}
