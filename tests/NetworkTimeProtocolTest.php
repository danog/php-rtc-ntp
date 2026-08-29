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

    public function testUnsignedStringRoundTrip()
    {
        foreach ([0, 1, PHP_INT_MAX, PHP_INT_MIN, -2387151028978245113, -1] as $ntp) {
            [$high, $low] = NetworkTimeProtocol::fromNtp($ntp);
            $this->assertSame(
                $ntp,
                NetworkTimeProtocol::toNtp($high, $low),
                "round trip of $ntp"
            );
        }
    }

    /** Seconds between the NTP epoch (1900-01-01) and the Unix epoch (1970-01-01). */
    private const NTP_UNIX_DELTA = 2208988800;

    public function testCurrentDatetimeIsUtcNow(): void
    {
        $before = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $now = NetworkTimeProtocol::currentDatetime();
        $after = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        // It must report UTC (the whole class assumes a UTC clock) and the actual current time.
        $this->assertSame('UTC', $now->getTimezone()->getName());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $now->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $now->getTimestamp());
    }

    public function testCurrentNtpTimeMatchesTheCurrentDatetime(): void
    {
        // currentNtpTime() is fromDatetime(currentDatetime()); bracket it with two real clock
        // reads so the assertion holds even across a second boundary, without mocking (ClockMock
        // freezes time() but not the DateTimeImmutable('now') this path uses).
        [$loHigh] = NetworkTimeProtocol::fromDatetime(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        [$high, $low] = NetworkTimeProtocol::currentNtpTime();
        [$hiHigh] = NetworkTimeProtocol::fromDatetime(new DateTimeImmutable('now', new DateTimeZone('UTC')));

        $this->assertGreaterThanOrEqual($loHigh, $high, 'NTP seconds went backwards');
        $this->assertLessThanOrEqual($hiHigh, $high, 'NTP seconds ran ahead of the clock');
        // The fractional half is an unsigned 32-bit quantity.
        $this->assertGreaterThanOrEqual(0, $low);
        $this->assertLessThanOrEqual(0xFFFFFFFF, $low);
    }

    public function testFromDatetimeEncodesSecondsAndFraction(): void
    {
        $dt = new DateTimeImmutable('2018-03-04T10:22:19.082427+0000', new DateTimeZone('UTC'));
        [$high, $low] = NetworkTimeProtocol::fromDatetime($dt);

        // The high half is the whole seconds since the NTP epoch (1900-01-01).
        $this->assertSame($dt->getTimestamp() + self::NTP_UNIX_DELTA, $high);
        // The low half is an unsigned 32-bit fraction encoding the .082427s sub-second part.
        $this->assertGreaterThanOrEqual(0, $low);
        $this->assertLessThanOrEqual(0xFFFFFFFF, $low);
        $this->assertEqualsWithDelta(0.082427, $low / (1 << 32), 1e-6);
    }

    /**
     * The real production path is fromDatetime() on the sender and toDatetime() on the receiver,
     * with the two 32-bit halves travelling over the wire in between. That round trip must recover
     * the original instant; the 32-bit NTP fraction quantizes to whole microseconds, so the
     * sub-second part is allowed to differ by at most 1µs.
     */
    public function testFromDatetimeToDatetimeRoundTrip(): void
    {
        $utc = new DateTimeZone('UTC');
        foreach ([
            '1900-01-01 00:00:00.000000',
            '1970-01-01 00:00:00.500000',
            '2018-03-04 10:22:19.082427',
            '2026-08-27 12:34:56.000001',
            '2035-12-31 23:59:59.999999',
        ] as $moment) {
            $dt = new DateTimeImmutable($moment, $utc);
            [$high, $low] = NetworkTimeProtocol::fromDatetime($dt);
            $back = NetworkTimeProtocol::toDatetime($high, $low);

            $this->assertSame(
                $dt->format('Y-m-d H:i:s'),
                $back->format('Y-m-d H:i:s'),
                "whole seconds must round trip for $moment"
            );
            $this->assertEqualsWithDelta(
                (int) $dt->format('u'),
                (int) $back->format('u'),
                1,
                "sub-second part must round trip to within 1µs for $moment"
            );
        }
    }
}
