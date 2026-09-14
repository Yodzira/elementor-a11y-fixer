=== Elementor A11y Fixer ===
Contributors: yodzira
Tags: accessibility, elementor, wcag, slider, accordion
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accessibility fixes that know Elementor markup — slider arrows, accordion states, icon-only links, lazy images. Previewable, additive-only, no overlay.

== Description ==

Generic accessibility plugins give generic advice. This one knows Elementor markup:

* slider navigation arrows get "Previous slide"/"Next slide" aria-labels
* accordion/tab titles get aria-expanded state
* icon-only links can be named from their URL (review before enabling)
* images without alt get alt from the media library
* non-Elementor pages are never parsed — zero overhead elsewhere
* repairs only ADD attributes; nothing is removed, preview shows every change

Works standalone; pairs with A11yFix for the full audit.

== Pro Version ==

Pro adds automation, reports and integrations on top of the free version
(one license = one site, 12 months of updates):

https://yodsira.duckdns.org/buy/elementor-a11y

== Installation ==

1. Install and activate (Elementor required for effect).
2. Open "Elementor A11y" → Preview on homepage → enable repairs.

== Frequently Asked Questions ==

= Does it add JavaScript? =
No. All v1 repairs are pure attribute additions via output buffering.

= What about pages without Elementor? =
They are served byte-for-byte unchanged.

== Changelog ==

= 0.1.0 =
* First release: 4 widget-aware repairs, master switch, homepage preview, clean uninstall.
