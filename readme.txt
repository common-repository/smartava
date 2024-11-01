=== smartAva ===
Contributors: AliceWonderFull
Donate link: http://www.clfsrpm.net/wpdonate/
Tags: gravatar, privacy
Requires at least: 2.5
Tested up to: 3.7.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Obfuscates the e-mail address hash used to get avatars from gravatar.com thus
protecting the privacy of users who do not want their activity tracked.

It has an admin panel (in Settings) that allows the blog administrator to
white-list specific domains and/or e-mail addresses that are not to be
obfuscated when hashed.

Please see the Extended Description (In 'Other Notes' tab in WordPress hosted
page) for more discussion on this issue.

== Installation ==

1. Upload `smartAva.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin through the 'smartAva' sub-menu of 'Settings'
4. If you so choose, check the box at bottom of admin page to 'Allow
   Notification to Users of smartAva usage' so users of your blog will know you
   care about their privacy.

== Frequently Asked Questions ==

= Aren't you being paranoid? =

Perhaps, but I do know that some of the blogs I visit and comment on are not
something I would want a future employer to find about me when he or she does
a background check that involves checking a database that has built profiles
of users based upon the MD5 hash of their e-mail address.

I assume there are other people like me who want to retain some privacy with
respect to their on-line activity, and I hope as many blog administrators as
possible will understand this desire for privacy and take this simple step
to ensure the privacy of their visitors.

== Changelog ==

= 0.4.0 =
* December 04, 2013
* With proper support in php, you can now white-list UTF8 International
  Domain Names without needing to convert them to ASCII punycode first.
* Admin interface now (mostly) matches look and feel of other Settings pages.
* Error message for non-conforming domain/addresses.

= 0.3.1 =
* December 02, 2013
* Fixed a typo, added label tags to the text inputs on admin page. No new
  functionallity or bugs/todo issues addressed.

= 0.3 =
* November 29, 2013
* Footer notifying users of smartAva website turned into an option the blog
  master has to specifically enable.
* Fixed some HTML tags in admin page that were not valid.

= 0.2 =
* First public release. November 28, 2013

== Screenshots ==

1. Admin interface showing input fields for adding domains and e-mail addresses
   to white-list and check-boxes for deleting existing domains.
2. Admin interface showing the existing salts along with option input fields
   for manually specifying salts and checkbox for re-generating salts. Also
   shows check-box for displaying footer message.

== Upgrade Notice ==

= 0.4 =
Nicer admin interface, internal IDN support (if your php install supports it.)

= 0.3 =
Fixes some incorrect HTML in the administrative page.

== Regression Warning ==
This plugin uses the code for `get_avatar()` from the version of WordPress
listed as 'Tested up to' in the top of this 'readme.txt' file (shown as
'Compatible up to' on WordPress hosted plugin page), with one minor
modification.

If you are running a newer version of WordPress that has fixed a bug in that
function, use of this plugin could cause a regression.

I will do my best to try and release updates in a timely manner whenever a new
stable version of WordPress is released that makes changes to that function.

== Extended Description ==
WordPress by default uses avatars from gravatar.com to display with comments
made by visitors to your site. To accomplish this, it uses an MD5 hash of the
commenter's e-mail address. This is done by the function `get_avatar()` in the
pluggable.php file.

There are many benefits to using gravatar.com avatars, but there are also some
privacy concerns. As the hash of the commenter's e-mail address becomes part of
the blog they commented on, it is possible for third parties to use bots to
scrape WordPress blogs, creating profiles of commenter's based upon their
unique hash that is used to display avatars.

With the default gravatar theme, this only impacts users who have set up an
account at gravatar.com but many of the fun gravatar themes will use the hash
to ask gravatar.com to generate an avatar for that user even if that user has
no knowledge of gravatar.com.

This plugin obfuscates the hash so that the fun gravatar themes with generated
avatars can still be used without sacrificing the privacy of the user. The user
can not be tracked through the gravatar image URI because the hash associated
with the users e-mail address is specific to the WordPress install.

This plugin does allow two white-lists where the hash of the e-mail address
will not be obfuscated.

The first white-list is domain-based. For example, you may want employees of
your company to have custom gravatar.com avatars. Simply add your companies
domain to the domain white-list and employees of your company will not have the
hash of their e-mail address obfuscated, thus allowing custom gravatars to
work.

The second white-list is for specific e-mail addresses. You may have a
colleague who posts at your blog often and would like his or her custom avatar
used with comments. By adding the e-mail address he or she uses to the e-mail
white-list, their e-mail address will not be obfuscated.

== How It Works ==
The standard URI for a gravatar.com hosted avatar is generated using the
`get_avatar()` function in the pluggables.php file of a standard WordPress
installation.

This plugin replaces that function with a slightly modified version. In the
modified version, instead of using

`$email_hash = md5( strtolower( trim( $email ) ) );`

it uses

`$email_hash = smartAvaHash($email);`

The function `smartAvaHash` checks to see if the argument `$email` is from a
white-listed domain or is white-listed itself.

If the e-mail address is white-listed, then the functions returns the MD5 hash
of the e-mail address.

If it is not white-listed, the function salts the address and creates a SHA256
hash. That hash is salted a second time and an MD5 hash is created and
returned.

== International Domain Names Support ==

If your php install has the necessary support, there is IDN support for white-
listing domains and e-mail addresses using UTF8 Internation Domain Names.

The following php requirements must be met:

1. php >= 5.3.0
2. PECL intl >= 1.0.2
3. PECL idn >= 0.1

When you enter a UTF8 domain name or e-mail address for white-listing, smartAva
will actually convert it to ASCII punycode for the actual listing and pattern
matching. The domains however will be displayed in UTF8 in the admin interface.

The display of UTF8 domains will have the ASCII punycode equivalent in the
HTML title tag, so you can visually see what it is when you mouse over the
UTF8 domain name.

= WordPress Support =

At least in version 3.7.1, WordPress itself does not like user comments posted
with a UTF8 version of an International Domain Name. Users with e-mail
addresses at those domains will need to use the ASCII punycode version of their
domain name.

There may be a WordPress plugin that fixes that issue, I do not think it would
be very hard to write one.

= IDN Bugs and Issues =

I have noticed some bugs that I believe are related to the PECL modules. For
example, the domain 'sßorra.it' does not work, but '名がドメイン.com' does.

Until the PECL modules (it may actually be the operating system libidn library)
are fixed, present best practice is to convert IDN domains to ASCII punycode
before entering them in the admin interface. That should work even if you do
not have the required version of php or the PECL modules.

Also, the list of domains and e-mail addresses are sorted using the ASCII
punycode versions of Internation Domain Names, which always starts with 'xn--'.
This quite likely will result them in being out of order in many cases. At some
point I will see if there is a better way to sort.

== Plugin Security ==
This plugin does not make any network connections and does not write any files.

It does write to the WordPress database but the only user supplied data that
it writes to the WordPress database is from the restricted administration page.

The only user supplied data this plugin interacts with outside of the
administration page is the e-mail address supplied by commenters when the user
comments on a post. This e-mail address is verified as a legal e-mail address
using the php `filter_var($address, FILTER_VALIDATE_EMAIL)` function. If it
fails that validation, it is discarded and the generic e-mail address
'unknown@gravatar.com' is used in its place.

The administrative page is protected by requiring an 'manage_options' level
of administration capabilities.

Additionally, processing of the POST data sent when changing options uses a
NONCE as an extra check to make sure the data submitted comes from the form
on the administrative page.

== TODO ==
* Properly internationalize at least for Spanish, German, and Greek.
* Implement a mechanism by which users who are registered and logged in at a
  blog can add their e-mail address to the white-list.

== Bug Reports ==
If you believe you have found a bug, the fastest way to get my attention is
probably through twitter at (NSFW) https://twitter.com/AliceWonder32

You can also try using the support forum at
http://wordpress.org/support/plugin/smartava

== Description of WordPress Options ==
This plugin creates and uses up to five WordPress options. Options are managed
using the following standard WordPress functions:

* `get_option()`
* `update_option()`
* `delete_option()`

= smartAvaDomains =
An array of domains that are white-listed from hash obfuscation. Posts with
e-mail addresses at those domains (and sub-domains) will not have their e-mail
address obfuscated before the hash that is used with gravatar.com is created.
This option is not created until the first time you specifically add a domain
to the white-list.

= smartAvaAddys =
An array of e-mail addresses that are white-listed from hash obfuscation. Posts
with e-mail addresses in that array will not have their e-mail address
obfuscated before the hash that is used with gravatar.com is created. This
option is not created until the first time you specifically add an e-mail
address to the white-list.

= smartAvaSalts =
An array that contains the two salts used to obfuscate the the e-mail hash. It
is automatically created whenever the plug-in wants a salt and the option does
not exist.

= smartAvaFooter =
If this option exists, a footer is added to pages letting users know that you
are running smartAva with a link to a web page describing what it is. If the
option does not exist, the notice is not added to the footer. The option is
created or deleted by checking or un-checking a box in the admin page.

= smartAvaAuthKey =
When an admin page is served, a NONCE is created and stored as this option. It
is inserted into the form as a hidden input and checked against what is stored
in the WP database when the form is processed to make sure they match. If they
do not match, the form is not processed.
