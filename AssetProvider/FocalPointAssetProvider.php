<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointBundle\AssetProvider;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Phlexible\GuiBundle\AssetProvider\AssetProviderInterface;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * Focal point asset provider
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class FocalPointAssetProvider implements AssetProviderInterface
{
    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * @param FileLocator $locator
     */
    public function __construct(FileLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * {@inheritDoc}
     */
    public function getUxScriptsCollection()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getUxCssCollection()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getScriptsCollection()
    {
        $collection = new AssetCollection(array(
            new FileAsset($this->locator->locate('@PhlexibleFocalPointBundle/Resources/scripts/Definitions.js')),

            new FileAsset($this->locator->locate('@PhlexibleFocalPointBundle/Resources/scripts/MainPanel.js')),
            new FileAsset($this->locator->locate('@PhlexibleFocalPointBundle/Resources/scripts/FocalpointPanel.js')),
        ));

        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function getCssCollection()
    {
        $collection = new AssetCollection(array(
            new FileAsset($this->locator->locate('@PhlexibleFocalPointBundle/Resources/styles/focalpoint.css')),
        ));

        return $collection;
    }
}
