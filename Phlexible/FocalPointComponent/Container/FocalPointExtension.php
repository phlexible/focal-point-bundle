<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\FocalPointComponent\Container;

use Phlexible\Container\ContainerBuilder;
use Phlexible\Container\Extension\Extension;
use Phlexible\Container\Loader\YamlFileLoader;

/**
 * Focal point extension
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class FocalPointExtension extends Extension
{
    public function load(ContainerBuilder $container, array $configs)
    {
        $loader = new YamlFileLoader($container);
        $loader->load(__DIR__ . '/../_config/services.yml');

        $container->setParameters(
            array(
                'focalpoint.asset.script_path' => __DIR__ . '/../_scripts',
                'focalpoint.asset.css_path'    => __DIR__ . '/../_styles',
            )
        );
    }
}
