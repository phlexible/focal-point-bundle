<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\FocalPointBundle\Queue;

use Phlexible\Bundle\MediaCacheBundle\Model\QueueManagerInterface;
use Phlexible\Bundle\MediaCacheBundle\Queue\Batch;
use Phlexible\Bundle\MediaCacheBundle\Queue\BatchResolver;
use Phlexible\Bundle\MediaSiteBundle\Model\FileInterface;
use Phlexible\Bundle\MediaTemplateBundle\Model\ImageTemplate;
use Phlexible\Bundle\MediaTemplateBundle\Model\TemplateManagerInterface;

/**
 * Crop template queuer
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
     * @var QueueManagerInterface
     */
    private $queueManager;

    /**
     * @var BatchResolver
     */
    private $batchResolver;

    /**
     * @param TemplateManagerInterface $templateManager
     * @param QueueManagerInterface    $queueManager
     * @param BatchResolver            $batchResolver
     */
    public function __construct(TemplateManagerInterface $templateManager, QueueManagerInterface $queueManager, BatchResolver $batchResolver)
    {
        $this->templateManager = $templateManager;
        $this->queueManager = $queueManager;
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

        foreach ($queue->all() as $queueItem) {
            $this->queueManager->updateQueueItem($queueItem);
        }
    }
}