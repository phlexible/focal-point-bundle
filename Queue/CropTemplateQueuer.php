<?php

/*
 * This file is part of the phlexible focal point package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\FocalPointBundle\Queue;

use Phlexible\Component\MediaCache\Model\CacheManagerInterface;
use Phlexible\Component\MediaCache\Queue\Batch;
use Phlexible\Component\MediaCache\Queue\BatchResolver;
use Phlexible\Component\MediaTemplate\Model\ImageTemplate;
use Phlexible\Component\MediaTemplate\Model\TemplateManagerInterface;
use Phlexible\Component\Volume\Model\FileInterface;

/**
 * Crop template queuer.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class CropTemplateQueuer
{
    /**
     * @var TemplateManagerInterface
     */
    private $templateManager;

    /**
     * @var CacheManagerInterface
     */
    private $cacheManager;

    /**
     * @var BatchResolver
     */
    private $batchResolver;

    /**
     * @param TemplateManagerInterface $templateManager
     * @param CacheManagerInterface    $cacheManager
     * @param BatchResolver            $batchResolver
     */
    public function __construct(TemplateManagerInterface $templateManager, CacheManagerInterface $cacheManager, BatchResolver $batchResolver)
    {
        $this->templateManager = $templateManager;
        $this->cacheManager = $cacheManager;
        $this->batchResolver = $batchResolver;
    }

    /**
     * @return ImageTemplate[]
     */
    public function getCropTemplates()
    {
        return array_filter(
            $this->templateManager->findBy(array('type' => 'image')),
            function($template) {
                return $template instanceof ImageTemplate && $template->getMethod() === 'crop';
            }
        );
    }

    /**
     * @param FileInterface $file
     */
    public function queueCropTemplates(FileInterface $file)
    {
        $batch = new Batch();
        $batch->addFile($file);

        foreach ($this->getCropTemplates() as $cropTemplate) {
            $batch->addTemplate($cropTemplate);
        }

        $queue = $this->batchResolver->resolve($batch);

        foreach ($queue->all() as $cacheItem) {
            $this->cacheManager->updateCacheItem($cacheItem);
        }
    }
}
