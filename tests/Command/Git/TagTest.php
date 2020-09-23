<?php

use Tomato\Command\Git\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Tag
     */
    private $tag;

    public function setUp(): void
    {
        $this->tag = $this->getMockBuilder(Tag::class)
            ->disableOriginalConstructor()
            ->getMock();

    }

    public function testConfig()
    {

    }

    public function testExecute()
    {

    }

    public function testPrune()
    {

    }
}
