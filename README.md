# Nepali Date Kit

A WordPress plugin for Bikram Sambat (BS) dates:

- **Post dates in BS.** Works with classic themes (`get_the_date()`, `the_time()` …) and block themes (the core *Post Date* block). Published and modified dates each use their own value.
- **Today's date** as a shortcode, widget or block.
- **BS ⇄ AD converter** as a shortcode or widget. Day pickers follow the real length of each month.
- Output in Nepali (`बिहिबार, ५ कात्तिक २०८३`) or English (`Bihibar, 5 Kartik 2083`).

Machine-readable dates (`<time datetime>`, `DATE_W3C`, `c`, `U`, feeds) are always left in AD, so SEO and schema markup keep working.

## Install

Copy this folder to `wp-content/plugins/nepali-date-kit` and activate it. Settings are under **Settings → Nepali Date**.

## Shortcodes

| Shortcode | Output |
|---|---|
| `[ndk_today]` | बिहिबार, ५ कात्तिक २०८३ |
| `[ndk_today format="Y-m-d" lang="en" show_ad="yes"]` | 2083-07-05 Thursday, October 22, 2026 |
| `[ndk_date date="2026-10-22"]` | A fixed AD date in BS |
| `[ndk_date date="2083-07-05" from="bs"]` | A fixed BS date in AD |
| `[ndk_converter]` | The interactive converter |

Formats use PHP date characters: `d j l D N w z t m n F M Y y a A g G h H i s`, with `\` escapes. Inside shortcode attributes, write backslashes twice (`j \\o\\f F`), because WordPress strips single ones.

## PHP API

```php
$bs = ndk_ad_to_bs( '2026-10-22' );          // ['year' => 2083, 'month' => 7, 'day' => 5, 'weekday' => 4] or WP_Error
$ad = ndk_bs_to_ad( '2083-07-05' );          // ['year' => 2026, 'month' => 10, 'day' => 22, 'weekday' => 4] or WP_Error
echo ndk_format_bs( $bs, 'l, j F Y', 'ne' ); // बिहिबार, ५ कात्तिक २०८३
echo ndk_format_datetime( get_post_datetime() ); // any DateTimeInterface, in the plugin's timezone
```

Invalid or out-of-range dates return a `WP_Error`. They never fatal or print warnings.

## REST API

`GET /wp-json/ndk/v1/convert?from=ad&date=2026-10-22&format=Y-m-d&lang=en`

```json
{ "calendar": "bs", "date": "2083-07-05", "weekday": 4, "formatted": "2083-07-05", "projected": false }
```

The endpoint is public and read-only. It needs no nonce, so it keeps working behind full-page caches.

## Calendar data

Supported range: BS 2000-01-01 to 2099-12-31 (AD 1943-04-14 to 2043-04-15). Month lengths up to **BS 2083** have been checked against the published Nepal calendar. Later years are projections that differ between sources and will be updated as official calendars come out. `NDK_Calendar::is_projected( $year )` tells you which is which, and the REST response includes `projected`.

To correct a year, edit its row in `includes/class-ndk-calendar.php`, add the month boundaries to `tests/run.php`, and run the tests.

## Development

```sh
php tests/run.php   # engine + formatter tests, no WordPress needed
```

CI runs the tests and a lint on PHP 7.4–8.4.

## License

GPL-2.0-or-later. Inspired by [Nepali Date Converter](https://github.com/codersantosh/nepali-date-converter) (GPLv2+); rewritten from scratch.
