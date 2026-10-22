=== Nepali Date Kit – Bikram Sambat Dates & Converter ===
Contributors: suryabistta
Tags: nepali date, bikram sambat, nepali post date, nepali calendar, date converter
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show post dates in Nepali (Bikram Sambat / BS), today's Nepali date, and a BS ⇄ AD date converter. Works with block and classic themes.

== Description ==

**Nepali Date Kit** shows dates in the Nepali calendar (Bikram Sambat, BS / वि.सं.) across your WordPress site, in Nepali (बिहिबार, ५ कात्तिक २०८३) or English (Bihibar, 5 Kartik 2083).

= Features =

* **Nepali post dates.** Published and modified dates and times appear in BS automatically. It works with **block themes** (the Post Date block) and **classic themes** (`the_date()`, `get_the_date()`, `the_time()`…). Modified dates use the real modified date.
* **Today's Nepali date** as a block, widget or shortcode, optionally with the English date beside it.
* **BS ⇄ AD date converter** for visitors (shortcode or widget). Day pickers follow the real length of each Nepali month, so impossible dates can't be chosen.
* **SEO-safe.** Machine-readable dates (`<time datetime>`, schema markup, RSS feeds, sitemaps) stay in AD, so search engines still read them correctly.
* **Nepal time or site time.** Today's date follows Nepal time (Asia/Kathmandu) by default, so it changes at midnight in Nepal. You can switch to your site's timezone.
* **Fast and safe.** Each conversion takes microseconds. Invalid or out-of-range dates never break a page.
* **Cache-friendly.** The converter uses a public REST endpoint with no nonce, so it keeps working behind page caches and CDNs.
* **Developer API.** PHP functions and a REST endpoint (see below).

= Shortcodes =

* `[ndk_today]` – today's BS date. Options: `format="l, j F Y"`, `lang="ne"` or `"en"`, `show_ad="yes"`, `ad_format="l, F j, Y"`.
* `[ndk_date date="2026-10-22"]` – a fixed AD date shown in BS. Add `from="bs"` to show a BS date in AD.
* `[ndk_converter]` – the interactive BS ⇄ AD converter.

= Date format characters =

`d j l D N w z t m n F M Y y a A g G h H i s`, the same as PHP's `date()`. Use `\` to print a letter as-is. Inside shortcode attributes, double it, e.g. `format="j \\o\\f F"`, because WordPress removes single backslashes there.

= For developers =

`ndk_ad_to_bs( '2026-10-22' )` and `ndk_bs_to_ad( '2083-07-05' )` return `['year', 'month', 'day', 'weekday']` or a `WP_Error`.
`ndk_format_bs( $bs, 'l, j F Y', 'ne' )` formats a BS date.
`ndk_format_datetime( $datetime, $format )` formats any `DateTimeInterface` as BS.

REST: `GET /wp-json/ndk/v1/convert?from=ad&date=2026-10-22&format=Y-m-d&lang=en`

Source code, issues and contributions: [github.com/Suryabistta/nepali-date-kit](https://github.com/Suryabistta/nepali-date-kit)

== Installation ==

1. Go to **Plugins → Add New**, search for **Nepali Date Kit**, and click **Install** and then **Activate**.
2. Post dates now show in Nepali. Adjust language, timezone and formats under **Settings → Nepali Date**.
3. To show today's date, add the **Nepali Date: Today** block, the widget, or `[ndk_today]`.
4. For a converter, add `[ndk_converter]` to a page, or use the **Nepali Date: Converter** widget.

== Frequently Asked Questions ==

= How accurate are the dates? =

Month lengths up to BS 2083 are checked against the published Nepal calendar, and the plugin's tests cover every day from AD 1943 to 2043. Calendars for later years have not been published yet, so those months are projections. They will be corrected in updates as official calendars are released.

= What date range is supported? =

BS 2000-01-01 to 2099-12-31 (AD 1943-04-14 to 2043-04-15). Dates outside this range are shown in AD.

= Does it work with block themes like Twenty Twenty-Five? =

Yes. The Post Date block shows BS dates, and there is a **Nepali Date: Today** block.

= Will it affect my SEO or schema markup? =

No. Only the text visitors read changes. The `datetime` attribute, schema, feeds and sitemaps stay in standard AD format.

= Can I show dates in English letters instead of Devanagari? =

Yes. Choose English under **Settings → Nepali Date**, or use `lang="en"` on any shortcode.

= Today's date is a day off around midnight. =

The default is Nepal time. If your site is for another country, choose "Site timezone" under **Settings → Nepali Date**. If you use a page cache, the cached page may show yesterday's date until the cache refreshes.

= How do I show "३ दिन अगाडि" (3 days ago)? =

Turn on **Relative dates** in the settings, or set the Post Date block's format to "Relative".

= Can I use it with the "Nepali Date Converter" plugin? =

It can run alongside it, but both change post dates, so keep only one active for post dates.

== Screenshots ==

1. The BS ⇄ AD converter on the front end.
2. Post dates in Bikram Sambat in a block theme.
3. Settings → Nepali Date.
4. The Nepali Date: Today block in the editor.

== Changelog ==

= 1.0.2 =
* Numeric date formats such as Y-m-d H:i:s (used by schema markup and date comparisons) now stay in AD.
* The Date format setting now replaces only the site's default date format, so explicit formats requested by a theme are kept.

= 1.0.1 =
* Register post-date filters early, so themes that answer AJAX requests on the 'wp' hook (e.g. JNews "load more") also show BS dates.
* Uninstall cleans up every site on multisite.

= 1.0.0 =
* First release: Nepali post dates (block and classic themes), the Today block, widgets and shortcodes, the BS ⇄ AD converter, a REST API and a settings page.

== Upgrade Notice ==

= 1.0.2 =
Fixes BS dates leaking into schema markup (JSON-LD datePublished).

= 1.0.1 =
BS dates in theme AJAX blocks; multisite uninstall cleanup.

= 1.0.0 =
First release.
