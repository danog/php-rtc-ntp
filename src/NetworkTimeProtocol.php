<?php

/**
 * This file is part of the PHP WebRTC package.
 *
 * (c) Amin Yazdanpanah <https://www.aminyazdanpanah.com/#contact>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webrtc\NTP;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Class NetworkTimeProtocol
 *
 * This class provides functionality to work with NTP (Network Time Protocol) timestamps.
 * NTP timestamps are based on a custom epoch (January 1, 1900, 00:00:00 UTC) and are represented
 * as 64-bit fixed-point numbers, where the high 32 bits represent seconds and the low 32 bits
 * represent fractional seconds.
 *
 * The full 64-bit value does not fit in a *signed* PHP integer, so it is kept as the raw 64-bit
 * bit pattern (i.e. it may be negative) and every extraction masks off the bits it needs: this
 * yields the same results as unsigned arithmetic without requiring the GMP extension.
 */
class NetworkTimeProtocol
{
    /** The NTP epoch (January 1, 1900, 00:00:00 UTC) as a Unix timestamp. */
    private const NTP_EPOCH = -2208988800; // strtotime('1900-01-01 00:00:00 UTC')

    /**
     * Get the current datetime in UTC.
     *
     * @return DateTimeImmutable The current datetime in UTC.
     * @throws DateMalformedStringException
     */
    public static function currentDatetime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * Get the current time in milliseconds since the NTP epoch.
     *
     * @return int The number of milliseconds since the NTP epoch.
     */
    public static function currentMs(): int
    {
        return (time() - self::NTP_EPOCH) * 1000;
    }

    /**
     * Get the current NTP time as a 64-bit fixed-point number.
     *
     * @throws DateMalformedStringException
     */
    public static function currentNtpTime(): array
    {
        return self::fromDatetime(self::currentDatetime());
    }

    /**
     * Convert an NTP timestamp to a DateTimeImmutable object.
     *
     * @return DateTimeImmutable The corresponding DateTime object in UTC.
     * @throws DateMalformedStringException
     */
    public static function toDatetime(int $high, int $low): DateTimeImmutable
    {
        $microseconds = intdiv($low * 1000000, 1 << 32);
        $timestamp = self::NTP_EPOCH + $high;
        $datetime = new DateTimeImmutable('@' . $timestamp, new DateTimeZone('UTC'));

        return $datetime->modify("+$microseconds microseconds");
    }

    /**
     * Convert a DateTimeImmutable object to an NTP timestamp.
     *
     * @param DateTimeImmutable $dt The DateTime object to convert.
     * @return array
     */
    public static function fromDatetime(DateTimeImmutable $dt): array
    {
        $delta = $dt->getTimestamp() - self::NTP_EPOCH;
        $microseconds = (int) $dt->format('u'); // Microseconds

        $high = $delta & 0xFFFFFFFF; // Seconds since NTP epoch
        $low = intdiv($microseconds * (1 << 32), 1000000) & 0xFFFFFFFF; // Fractional seconds

        // Combine both halves into the raw 64-bit NTP bit pattern.
        return [$high, $low];
    }

    /**
     * Merge two 32-bit NTP halves back into the raw 64-bit bit pattern.
     *
     * The full 64-bit value does not fit in a *signed* PHP integer, so it is kept as the raw
     * bit pattern (i.e. it may be negative). Only integer arithmetic is used: no floating point
     * and no decimal-string round trips, so the result is exactly the value that {@see fromNtp()}
     * split.
     *
     * @param int $high The high 32 bits (whole seconds since the NTP epoch).
     * @param int $low The low 32 bits (fractional seconds).
     * @return int The raw 64-bit NTP bit pattern.
     */
    public static function toNtp(int $high, int $low): int
    {
        return ($high << 32) | $low;
    }

    /**
     * Split a raw 64-bit NTP bit pattern into its two 32-bit halves.
     *
     * The high half is the arithmetic (sign-extending) shift, so the caller gets the same bit
     * pattern back from {@see toNtp()}. Uses only integer arithmetic.
     *
     * @param int $ntp The raw 64-bit NTP bit pattern.
     * @return array{0: int, 1: int} The [high, low] halves.
     */
    public static function fromNtp(int $ntp): array
    {
        return [$ntp >> 32, $ntp & 0xFFFFFFFF];
    }
}
