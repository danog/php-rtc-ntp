# Network Time Protocol


[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](LICENSE)

The NetworkTimeProtocol class provides utility methods for working with NTP (Network Time Protocol) timestamps in PHP. It is part of the PHP WebRTC package and supports conversion between NTP timestamps and PHP DateTimeImmutable objects. Internally, it uses GMP to handle 64-bit fixed-point representations of time.

##  Features

- Convert current time to NTP timestamp (64-bit fixed-point)

- Convert NTP timestamp to DateTimeImmutable

- Accurate fractional second handling

- Uses GMP for high-precision arithmetic

- Fully UTC-compliant


## Requirements

- PHP ≥ 8.4

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

