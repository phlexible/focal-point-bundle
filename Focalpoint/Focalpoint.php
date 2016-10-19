<?php

/*
 * This file is part of the phlexible focal point package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\FocalPointBundle\Focalpoint;

/**
 * Focalpoint.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class Focalpoint
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_DISABLED = -1;

    /**
     * @var bool
     */
    private $status;

    /**
     * @var bool
     */
    private $x;

    /**
     * @var bool
     */
    private $y;

    /**
     * @param int $status
     * @param int $x
     * @param int $y
     */
    public function __construct($status, $x, $y)
    {
        $this->status = (int) $status;
        $this->x = (int) $x;
        $this->y = (int) $y;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isInactive()
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->status === self::STATUS_DISABLED;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }
}
