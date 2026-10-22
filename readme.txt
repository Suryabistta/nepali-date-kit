=== Nepali Date Kit ===
Contributors: suryabistta
Tags: nepali date, bikram sambat, nepali calendar, date converter, patro
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bikram Sambat (BS) dates for WordPress: post dates in BS, today's date, and a BS ⇄ AD converter.

== Description ==

* **Post dates in BS.** Shows published and modified dates/times printed by your theme as Bikram Sambat. Machine-readable dates (`<time datetime>`, schema, feeds) stay in AD, so SEO markup keeps working.
* **Today's date** as a shortcode, widget or block.
* **BS ⇄ AD converter** as a shortcode or widget. Day pickers follow the real length of each month.
* Nepali (Devanagari) or English output. Uses PHP date format characters, including backslash escapes.
* Nepal time (Asia/Kathmandu) or your site timezone.

Supported range: BS 2000-01-01 to 2099-12-31 (AD 1943-04-14 to 2043-04-15). Data up to BS 2083 has been checked against the published Nepal calendar. Later years are projections and may be corrected in updates.

= Shortcodes =

* `[ndk_today]` – today in BS. Options: `format="l, j F Y"`, `lang="ne|en"`, `show_ad="yes"`, `ad_format="l, F j, Y"`.
* `[ndk_date date="2026-10-22"]` – a fixed AD date shown in BS. Use `from="bs"` to show a BS date in AD.
* `[ndk_converter]` – the interactive converter. Options: `lang`, `bs_format`, `ad_format`.

= Format characters =

`d j l D N w z t m n F M Y y a A g G h H i s S` and `\` escapes. Other characters are printed as-is.

Inside shortcode attributes WordPress removes single backslashes, so double them: `[ndk_today format="j \\o\\f F"]`.

= For developers =

`ndk_ad_to_bs( '2026-10-22' )` and `ndk_bs_to_ad( '2083-07-05' )` return `['year','month','day','weekday']` or a `WP_Error`. `ndk_format_bs( $bs, 'l, j F Y', 'ne' )` formats one. `ndk_format_datetime( $datetime, $format )` formats any `DateTimeInterface` as BS.

REST: `GET /wp-json/ndk/v1/convert?from=ad&date=2026-10-22&format=Y-m-d&lang=en`

== Changelog ==

= 1.0.0 =
* First release.
