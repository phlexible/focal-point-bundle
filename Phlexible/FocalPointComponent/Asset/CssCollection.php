<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent\Asset;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;

/**
 * CSS collection
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class CssCollection extends AssetCollection
{
    /**
     * @param array $cssDir
     */
    public function __construct($cssDir)
    {
        $cssDir = rtrim($cssDir, '/') . '/';

        $assets = array(
            new FileAsset($cssDir . 'focalpoint.css'),
        );

        parent::__construct($assets);
    }
}
