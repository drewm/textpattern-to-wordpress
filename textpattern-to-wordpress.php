<?php

// Include your TXP config file:
include('textpattern/config.php');

// Do you want to export HTML (for WordPress, default) or raw Textile formatting?
$export_html = true;


/* ==============================================================================================================

	SUPER-CRUDE TEXTPATTERN TO WORDPRESS RSS EXPORTER

	Connects your TXP database tables and creates a WP-style extended RSS file. This can then be imported into
	WordPress, or something else that supports WordPress format files.

	Put this file in your Textpattern site, at the same level as the textpattern folder (usually root), then
	load it up in your web browser.
	
	This isn't pretty.

================================================================================================================= */


$dsn = 'mysql:dbname='.$txpcfg['db'].';host='.(isset($txpcfg['host']) ? $txpcfg['host'] : '127.0.0.1');

try {
	$Connection = new PDO($dsn, $txpcfg['user'], $txpcfg['pass'], array(1002 => "SET NAMES '".$txpcfg['dbcharset']."'"));
} catch (PDOException $e) {
	die('Connection failed: ' . $e->getMessage());
}

define('EOL', "\n");

header("Content-Type: text/xml; charset=UTF-8", true);
header("Content-Disposition: attachment; filename=\"textpattern-export.xml\"", true);



echo '<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.1/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.1/"
>
<channel>';


/* ====== FRONT MATTER ======= */

$result = $Connection->query('SELECT name, val FROM '.$txpcfg['table_prefix'].'txp_prefs');
if ($result) {
	$prefs = $result->fetchAll(PDO::FETCH_ASSOC);

	if (is_array($prefs) && count($prefs)) {
		foreach($prefs as $pref) {
			switch($pref['name']) {
				case 'sitename':
					echo '<title>'.txpspecialchars($pref['val']).'</title>'.EOL;
					break;

				case 'site_slogan':
					echo '<description>'.txpspecialchars($pref['val']).'</description>'.EOL;
					break;

				case 'siteurl':
					echo '<link>http://'.$pref['val'].'</link>'.EOL;
					echo '<wp:base_site_url>http://'.$pref['val'].'</wp:base_site_url>'.EOL;
					echo '<wp:base_blog_url>http://'.$pref['val'].'</wp:base_blog_url>'.EOL;
					$siteurl = 'http://'.$pref['val'].'/';
					break;

				case 'language':
					echo '<language>'.$pref['val'].'</language>'.EOL;
					break;

				case 'lastmod':
					echo '<pubDate>'.date('r', strtotime($pref['val'])).'</pubDate>'.EOL;
					break;

				case 'permlink_mode':
					$permlink_mode = $pref['val'];
					break;
			}
		}
	}
}

echo '<wp:wxr_version>1.1</wp:wxr_version>'.EOL;
echo '<generator>https://github.com/drewm/textpattern-to-wordpress</generator>'.EOL;


/* ======= AUTHORS ======== */

$result = $Connection->query('SELECT * FROM '.$txpcfg['table_prefix'].'txp_users');
if ($result) {
	$users = $result->fetchAll(PDO::FETCH_ASSOC);

	if (is_array($users) && count($users)) {
		foreach($users as $user) {
			echo '<wp:author>'.EOL;
			echo '<wp:author_id>'.$user['user_id'].'</wp:author_id>'.EOL;
			echo '<wp:author_login>'.txpspecialchars($user['name']).'</wp:author_login>'.EOL;
			echo '<wp:author_email>'.$user['email'].'</wp:author_email>'.EOL;
			echo '<wp:author_display_name><![CDATA['.$user['RealName'].']]></wp:author_display_name>'.EOL;
			$parts = explode(' ', $user['RealName'], 2);
			if (isset($parts[0])) echo '<wp:author_first_name><![CDATA['.$parts[0].']]></wp:author_first_name>'.EOL;
			if (isset($parts[1])) echo '<wp:author_last_name><![CDATA['.$parts[1].']]></wp:author_last_name>'.EOL;
			echo '</wp:author>'.EOL;
		}
	}
}


/* ======= CATEGORIES ======== */

$cat_titles = array();

$result = $Connection->query('SELECT name, title FROM '.$txpcfg['table_prefix'].'txp_category');
if ($result) {
	$cats = $result->fetchAll(PDO::FETCH_ASSOC);

	if (is_array($cats) && count($cats)) {
		foreach($cats as $cat) {
			$cat_titles[$cat['name']] = $cat['title'];
		}
	}
}


/* ======= POSTS ======== */


$sql = 'SELECT * FROM '.$txpcfg['table_prefix'].'textpattern';
try {
	$Statement = $Connection->query($sql);
	$result = $Statement->setFetchMode(PDO::FETCH_ASSOC);
	while ($row = $Statement->fetch()) {

		$article_time = strtotime($row['Posted']);

		echo '<item>'.EOL;
		echo '<title>'.escape_title($row['Title']).'</title>'.EOL;

		switch ($permlink_mode) {
			case 'section_id_title':
				$url = $siteurl.$row['Section'].'/'.$row['ID'].'/'.$row['url_title'];
				break;

			case 'year_month_day_title':
				$url = $siteurl.date('Y/m/d', $article_time).'/'.$row['url_title'];
				break;

			case 'section_title':
				$url = $siteurl.$row['Section'].'/'.$row['url_title'];
				break;

			case 'title_only':
				$url = $siteurl.$row['url_title'];
				break;

			case 'id_title':
				$url = $siteurl.$row['ID'].'/'.$row['url_title'];
				break;
		}

		echo '<link>'.$url.'</link>'.EOL;

		echo '<pubDate>'.date('r', $article_time).'</pubDate>'.EOL;
		echo '<dc:creator>'.$row['AuthorID'].'</dc:creator>'.EOL;
		echo '<guid isPermaLink="false">'.$url.'</guid>'.EOL;
		echo '<description></description>'.EOL;

		if ($export_html) {
			echo '<content:encoded><![CDATA['.$row['Body_html'].']]></content:encoded>'.EOL;
			echo '<excerpt:encoded><![CDATA['.$row['Excerpt_html'].']]></excerpt:encoded>'.EOL;
		}else{
			echo '<content:encoded><![CDATA['.$row['Body'].']]></content:encoded>'.EOL;
			echo '<excerpt:encoded><![CDATA['.$row['Excerpt'].']]></excerpt:encoded>'.EOL;
		}
		
		echo '<wp:post_id>'.$row['ID'].'</wp:post_id>'.EOL;
		echo '<wp:post_date>'.$row['Posted'].'</wp:post_date>'.EOL;
		echo '<wp:post_date_gmt>'.$row['Posted'].'</wp:post_date_gmt>'.EOL;
		echo '<wp:comment_status>'.($row['Annotate']=='1' ? 'open' : 'closed').'</wp:comment_status>'.EOL;
		echo '<wp:ping_status></wp:ping_status>'.EOL;
		echo '<wp:post_name>'.$row['url_title'].'</wp:post_name>'.EOL;
		echo '<wp:status>'.((int)$row['Status'] < 4 ? 'draft' : 'publish').'</wp:status>'.EOL;
		echo '<wp:post_parent>0</wp:post_parent>'.EOL;
		echo '<wp:menu_order>0</wp:menu_order>'.EOL;
		echo '<wp:post_type>post</wp:post_type>'.EOL;
		echo '<wp:post_password></wp:post_password>'.EOL;
		echo '<wp:is_sticky>'.(int)($row['Status'] == 5).'</wp:is_sticky>'.EOL;

		if ($row['Category1']!='') {
			echo '<category domain="category" nicename="'.$row['Category1'].'"><![CDATA['.$cat_titles[$row['Category1']].']]></category>'.EOL;
		}

		if ($row['Category2']!='') {
			echo '<category domain="category" nicename="'.$row['Category2'].'"><![CDATA['.$cat_titles[$row['Category2']].']]></category>'.EOL;
		}



		/* ============ COMMENTS ============== */

		$sql = 'SELECT * FROM '.$txpcfg['table_prefix'].'txp_discuss WHERE parentid='.(int)$row['ID'].' AND visible=1';
		$Statement2 = $Connection->query($sql);
		$result = $Statement2->setFetchMode(PDO::FETCH_ASSOC);
		while ($comment = $Statement2->fetch()) {

			echo '<wp:comment>'.EOL;
			echo '<wp:comment_id>'.$comment['discussid'].'</wp:comment_id>'.EOL;
			echo '<wp:comment_author><![CDATA['.$comment['name'].']]></wp:comment_author>'.EOL;
			echo '<wp:comment_author_email>'.$comment['email'].'</wp:comment_author_email>'.EOL;
			echo '<wp:comment_author_url>http://'.txpspecialchars($comment['web']).'</wp:comment_author_url>'.EOL;
			echo '<wp:comment_author_IP>'.$comment['ip'].'</wp:comment_author_IP>'.EOL;
			echo '<wp:comment_date>'.$comment['posted'].'</wp:comment_date>'.EOL;
			echo '<wp:comment_date_gmt>'.$comment['posted'].'</wp:comment_date_gmt>'.EOL;
			echo '<wp:comment_content><![CDATA['.fix_encoded_mess($comment['message']).']]></wp:comment_content>'.EOL;
			echo '<wp:comment_approved>'.$comment['visible'].'</wp:comment_approved>'.EOL;
			echo '<wp:comment_type></wp:comment_type>'.EOL;
			echo '<wp:comment_parent>0</wp:comment_parent>'.EOL;
			echo '<wp:comment_user_id>0</wp:comment_user_id>'.EOL;
			echo '</wp:comment>'.EOL;
		}



		echo '</item>'.EOL;
		flush();

	}
} catch (PDOException $e) {
	print $e->getMessage();
}

echo '</channel>
</rss>';


function fix_encoded_mess($str)
{
	$find = array(
		'â€™',
		'â€˜',
		'â€œ',
		'â€',
		'Â£',
	);

	$replace = array(
		"’",
		"‘",
		'“',
		'”',
		'£',
	);

	return str_replace($find, $replace, $str);
}

function txpspecialchars($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true)
{
	return htmlspecialchars($string, $flags, $encoding, $double_encode);
}

function escape_title($title)
{
	return strtr($title, array(
		'<' => '&#60;',
		'>' => '&#62;',
		"'" => '&#39;',
		'"' => '&#34;',
	));
}
