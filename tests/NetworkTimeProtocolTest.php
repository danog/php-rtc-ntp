<?php

namespace Webrtc\tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Webrtc\NTP\NetworkTimeProtocol;

#[CoversClass(NetworkTimeProtocol::class)]
class NetworkTimeProtocolTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ClockMock::register(NetworkTimeProtocol::class);
    }

    protected function tearDown(): void
    {
        ClockMock::withClockMock(false);
    }

    public function testCurrentMs()
    {
        ClockMock::withClockMock(strtotime('2018-09-11 00:00:00'));
        $this->assertEquals(3745612800000, NetworkTimeProtocol::currentMs());

        ClockMock::withClockMock(strtotime('2018-09-11 00:00:01'));
        $this->assertEquals(3745612801000, NetworkTimeProtocol::currentMs());
    }

    public function testDatetimeFromNtp()
    {
        $expectedDatetime = new DateTimeImmutable('2018-06-28 09:03:05.423997', new DateTimeZone('UTC'));
        $this->assertEquals($expectedDatetime, NetworkTimeProtocol::toDatetime("16059593044731306503"));
    }

    public function testDatetimeToNtp()
    {
        $dt = new DateTimeImmutable('2018-06-28 09:03:05.423998', new DateTimeZone('UTC'));
        $this->assertEquals(16059593044731306503, NetworkTimeProtocol::fromDatetime($dt));
    }
}
