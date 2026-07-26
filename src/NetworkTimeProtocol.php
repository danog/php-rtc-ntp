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
     * @return int The current NTP timestamp.
     * @throws DateMalformedStringException
     */
    public static function currentNtpTime(): int
    {
        return self::fromDatetime(self::currentDatetime());
    }

    /**
     * Convert an NTP timestamp to a DateTimeImmutable object.
     *
     * @param int|string $ntp The raw 64-bit NTP timestamp.
     * @return DateTimeImmutable The corresponding DateTime object in UTC.
     * @throws DateMalformedStringException
     */
    public static function toDatetime(int|string $ntp): DateTimeImmutable
    {
        $ntp = (int) $ntp;

        // Extract the high 32 bits (seconds) and low 32 bits (fractional seconds).
        $high = ($ntp >> 32) & 0xFFFFFFFF; // Seconds
        $low = $ntp & 0xFFFFFFFF;          // Fractional seconds

        $microseconds = intdiv($low * 1000000, 1 << 32);
        $timestamp = self::NTP_EPOCH + $high;
        $datetime = new DateTimeImmutable('@' . $timestamp, new DateTimeZone('UTC'));

        return $datetime->modify("+$microseconds microseconds");
    }

    /**
     * Convert a DateTimeImmutable object to an NTP timestamp.
     *
     * @param DateTimeImmutable $dt The DateTime object to convert.
     * @return int The raw 64-bit NTP timestamp.
     */
    public static function fromDatetime(DateTimeImmutable $dt): int
    {
        $delta = $dt->getTimestamp() - self::NTP_EPOCH;
        $microseconds = (int) $dt->format('u'); // Microseconds

        $high = $delta & 0xFFFFFFFF; // Seconds since NTP epoch
        $low = intdiv($microseconds * (1 << 32), 1000000) & 0xFFFFFFFF; // Fractional seconds

        // Combine both halves into the raw 64-bit NTP bit pattern.
        return ($high << 32) | $low;
    }
}
