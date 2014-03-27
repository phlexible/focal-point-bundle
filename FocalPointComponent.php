<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent;

use Phlexible\Component\Component;

/**
 * Focal point component
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class FocalPointComponent extends Component
{
    const RESOURE_FOCAL_POINT = 'focalpoint';

    public function __construct()
    {
        $this
            ->setVersion('0.7.1')
            ->setId('focalpoint')
            ->setPackage('phlexible');
    }
}
