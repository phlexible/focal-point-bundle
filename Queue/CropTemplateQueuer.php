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
use Phlexible\Component\MediaCache\Queue\BatchBuilder;
use Phlexible\Component\MediaCache\Queue\BatchProcessor;
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
     * @var BatchProcessor
     */
    private $batchProcessor;

    /**
     * @param TemplateManagerInterface $templateManager
     * @param CacheManagerInterface    $cacheManager
     * @param BatchProcessor           $batchProcessor
     */
    public function __construct(TemplateManagerInterface $templateManager, CacheManagerInterface $cacheManager, BatchProcessor $batchProcessor)
    {
        $this->templateManager = $templateManager;
        $this->cacheManager = $cacheManager;
        $this->batchProcessor = $batchProcessor;
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
        $batchBuilder = new BatchBuilder();

        $batchBuilder
            ->files([$file])
            ->templates($this->getCropTemplates());

        $batch = $batchBuilder->getBatch();

        foreach ($this->batchProcessor->process($batch) as $instruction) {
            $this->cacheManager->updateCacheItem($instruction->getCacheItem());
        }
    }
}
