Textpattern to WordPress exporter
=================================

Create a WordPress-format export file from a Textpattern blog.

Connects your TXP database tables and creates a WP-style extended RSS file. This can then be imported into WordPress, or something else that supports WordPress format files. I suggest [Perch](http://grabaperch.com/).

Put the `textpattern-to-wordpress.php` file in your Textpattern site, at the same level as the textpattern folder (usually root), then load it up in your web browser.

This isn't pretty, but it works well.

Requirements
------------

* PHP 5.2
* PDO-mysql
* A strong stomach

Configuration
-------------

At the top of the file you can tweak two settings:

* The include path to your TXP config file
* The `$export_html` setting. By default this is true, and posts are exported as HTML. Set to `false` to export as raw Textile.

