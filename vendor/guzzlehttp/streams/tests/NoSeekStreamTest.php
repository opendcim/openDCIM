<?php
namespace GuzzleHttp\Tests\Stream;

use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\NoSeekStream;

/**
 * @covers GuzzleHttp\Stream\NoSeekStream
 * @covers GuzzleHttp\Stream\StreamDecoratorTrait
 */
class NoSeekStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testCannotSeek()
    {
        $s = $this->getMockBuilder('GuzzleHttp\Stream\StreamInterface')
            ->setMethods(['isSeekable', 'seek'])
            ->getMockForAbstractClass();
        $s->expects($this->never())->method('seek');
        $s->expects($this->never())->method('isSeekable');
        $wrapped = new NoSeekStream($s);
        $this->assertFalse($wrapped->isSeekable());
        $this->assertFalse($wrapped->seek(2));
    }

    public function testHandlesClose()
    {
        $s = Stream::factory('foo');
        $wrapped = new NoSeekStream($s);
        $wrapped->close();
        $this->assertFalse($wrapped->write('foo'));
    }
}
