Textpattern to WordPress exporter
=================================

Create a WordPress-format export file from a Textpattern blog.

Connects your TXP database tables and creates a WP-style extended RSS file. This can then be imported into WordPress, or something else that supports WordPress format files. I suggest [Perch](http://grabaperch.com/).

Put the `textpattern-to-wordpress.php` file in your Textpattern site, at the same level as the textpattern folder (usually root), then load it up in your web browser.

This isn't pretty.

Requirements
------------

* PHP
* PDO-mysql
* A strong stomach