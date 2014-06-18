<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointBundle\AclProvider;

use Phlexible\SecurityBundle\Acl\AclProvider\AclProvider;

/**
 * Focal point acl provider
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class FocalPointAclProvider extends AclProvider
{
    /**
     * {@inheritdoc}
     */
    public function provideResources()
    {
        return array(
            'focalpoint',
        );
    }
}