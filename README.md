# Network Time Protocol


[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](LICENSE)

The NetworkTimeProtocol class provides utility methods for working with NTP (Network Time Protocol) timestamps in PHP. It is part of the PHP WebRTC package and supports conversion between NTP timestamps and PHP DateTimeImmutable objects.

## About this fork

This is the `danog/php-rtc-ntp` fork used by MadelineProto. It targets PHP 8.2+, removes the GMP dependency, and correctly handles unsigned NTP values above `PHP_INT_MAX` using pure-PHP arithmetic.

The forked stack keeps the upstream `quasarstream/*` dependency constraints for compatibility. Each `danog/php-rtc-*` package replaces its upstream counterpart, so consumers select the complete maintained stack by requiring the corresponding danog packages together.

##  Features

- Convert current time to NTP timestamp (64-bit fixed-point)

- Convert NTP timestamp to DateTimeImmutable

- Accurate fractional second handling

- Handles unsigned 64-bit fixed-point values without GMP

- Fully UTC-compliant


## Requirements

- PHP ≥ 8.2

## Documentation

This package is part of the PHP WebRTC library. For complete documentation, examples, and API reference, visit:

[PHP WebRTC Documentation](https://www.quasarstream.com/php-webrtc)

## Credits

### Authors

- **Amin Yazdanpanah**  
  - Website: [aminyazdanpanah.com](https://www.aminyazdanpanah.com)
  - Email: [github@aminyazdanpanah.com](mailto:github@aminyazdanpanah.com)

- **Sana Moniri**  
  - GtiHub: [sanamoniri](https://github.com/sanamoniri)

## Reporting Issues

Found a bug? Please report it on our [issues](https://github.com/php-webrtc/ntp/issues).

## License

BSD 3-Clause License. See [LICENSE](LICENSE) for details.
