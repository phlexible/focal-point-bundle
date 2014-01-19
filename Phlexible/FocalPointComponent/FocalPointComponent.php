<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent;

use Phlexible\Component\AbstractComponent;
use Zend_Controller_Router_Route as Route;

/**
 * Focal point component
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class FocalPointComponent extends AbstractComponent
{
    const RESOURE_FOCAL_POINT = 'focalpoint';

    public function __construct()
    {
        $this
            ->setVersion('0.7.1')
            ->setId('focalpoint')
            ->setPackage('phlexible');
    }

    public function getAcl()
    {
        return array(
            array(
                'roles' => array(
                ),
                'resources' => array(
                    self::RESOURE_FOCAL_POINT,
                ),
                'allow' => array(
                )
            )
        );
    }
}
