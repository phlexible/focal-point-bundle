<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent;

use MWF_Component_Abstract as AbstractComponent;
use Zend_Controller_Router_Route as Route;

/**
 * Focal point component
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class Media_FocalPoint_Component extends AbstractComponent
{
    const RESOURE_FOCAL_POINT = 'focalpoint';

    public function __construct()
    {
        $this
            ->setVersion('0.7.1')
            ->setId('focalpoint')
            ->setFile(__FILE__)
            ->setPackage('media');
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

    public function getRoutes()
    {
        return array(
            'focalpoint_data' => new Route(
                '/focalpoint/data/:action/*',
                array('module' => 'focalpoint', 'controller' => 'data', 'action' => 'index'),
                array('_resource' => self::RESOURE_FOCAL_POINT)
            ),
        );
    }

    public function getScripts()
    {
        $path = $this->getPath().'/_scripts/';

        return array(
            $path . 'Definitions.js',
            $path . 'MainPanel.js',
            $path . 'FocalpointPanel.js',
        );
    }

    public function getStyles()
    {
        $path = $this->getPath().'/_styles/';

        return array(
            $path . 'focalpoint.css',
        );
    }

    public function getTranslations()
    {
        $t9n  = $this->getContainer()->t9n;
        $page = $t9n->focalpoint->toArray();

        return array(
            'Media.strings.Focalpoint' => $page
        );
    }
}
