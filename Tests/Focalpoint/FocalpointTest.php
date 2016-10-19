<?php

/*
 * This file is part of the phlexible elastica package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\FocalPointBundle\Tests\Focalpoint;

use Phlexible\Bundle\FocalPointBundle\Focalpoint\Focalpoint;

/**
 * Focalpoint test.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class FocalpointTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return bool
     */
    public function testIsActive()
    {
        $fp = new Focalpoint(Focalpoint::STATUS_ACTIVE, 10, 20);

        $this->assertTrue($fp->isActive());
        $this->assertFalse($fp->isInactive());
        $this->assertFalse($fp->isDisabled());
    }

    /**
     * @return bool
     */
    public function testIsInactive()
    {
        $fp = new Focalpoint(Focalpoint::STATUS_INACTIVE, 10, 20);

        $this->assertFalse($fp->isActive());
        $this->assertTrue($fp->isInactive());
        $this->assertFalse($fp->isDisabled());
    }

    /**
     * @return bool
     */
    public function testIsDisabled()
    {
        $fp = new Focalpoint(Focalpoint::STATUS_DISABLED, 10, 20);

        $this->assertFalse($fp->isActive());
        $this->assertFalse($fp->isInactive());
        $this->assertTrue($fp->isDisabled());
    }

    public function testGetStatus()
    {
        $fp = new Focalpoint(1, 10, 20);

        $this->assertSame(1, $fp->getStatus());
    }

    /**
     * @return int
     */
    public function testGetX()
    {
        $fp = new Focalpoint(1, 10, 20);

        $this->assertSame(10, $fp->getX());
    }

    /**
     * @return int
     */
    public function testGetY()
    {
        $fp = new Focalpoint(1, 10, 20);

        $this->assertSame(20, $fp->getY());
    }
}
