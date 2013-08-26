<?php
/**
 * MAKEweb
 *
 * PHP Version 5
 *
 * @category    MAKEweb
 * @package     Media_FocalPoint
 * @copyright   2007 brainbits GmbH (http://www.brainbits.net)
 * @version     SVN: $Id: Generator.php 2312 2007-01-25 18:46:27Z swentz $
 */

/**
 * MetaSystem Component
 *
 * @category    MAKEweb
 * @package     Media_FocalPoint
 * @author      Stephan Wentz <sw@brainbits.net>
 * @copyright   2007 brainbits GmbH (http://www.brainbits.net)
 */
class Media_FocalPoint_Component extends MWF_Component_Abstract
{
    const RESOURE_FOCAL_POINT = 'focalpoint';

    /**
     * Constructor
     * Initialses the Component values
     */
    public function __construct()
    {
        $this->setVersion('0.7.1');
        $this->setId('focalpoint');
        $this->setFile(__FILE__);
        $this->setPackage('media');
        $this->setOrder('after mediamanager');
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
            'focalpoint_data' => new MWF_Controller_Router_Route(
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
