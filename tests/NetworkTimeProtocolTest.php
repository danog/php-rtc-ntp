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
        $expectedDatetime = new DateTimeImmutable('2018-03-04T10:22:19.082427+0000', new DateTimeZone('UTC'));
        $this->assertEquals($expectedDatetime, NetworkTimeProtocol::toDatetime(3729147739, 354025564));
    }

    public function testDatetimeToNtp()
    {
        $dt = new DateTimeImmutable('2018-06-28 09:03:05.423998', new DateTimeZone('UTC'));
        $converted = NetworkTimeProtocol::toDatetime(...NetworkTimeProtocol::fromDatetime($dt));
        // The raw 64-bit pattern is a negative PHP integer, so compare the unsigned rendering:
        // asserting against the literal would compare against a lossy float.
        $this->assertSame(
            $dt->getTimestamp() . '.' . $dt->format('u'),
            $converted->getTimestamp() . '.' . $converted->format('u')
        );
    }

    public function testUnsignedStringRoundTrip()
    {
        foreach ([0, 1, 9223372036854775807, -9223372036854775808, -2387151028978245113, -1] as $ntp) {
            $this->assertTrue(is_int($ntp));
            $high = $ntp >> 32;
            $low = $ntp & 0xFFFFFFFF;

            $generated = NetworkTimeProtocol::toDatetime($high, $low);
            [$roundTripHigh, $roundTripLow] = NetworkTimeProtocol::fromDatetime($generated);
            $this->assertSame(
                $ntp,
                ($roundTripHigh << 32) | $roundTripLow,
                "round trip of $ntp"
            );
        }
    }
}
