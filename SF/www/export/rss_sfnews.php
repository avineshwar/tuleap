<?php
// ## export sf front page news in RSS
require($DOCUMENT_ROOT.'/include/pre.php');
require('./rss_utils.inc');
header("Content-Type: text/xml");
print '<?xml version="1.0"?>
<!DOCTYPE rss SYSTEM "http://my.netscape.com/publish/formats/rss-0.91.dtd">
<rss version="0.91">
';
// ## default limit
if (!$limit) $limit = 10;
if ($limit > 100) $limit = 100;

if ($group_id) {
    $project = new Project($group_id);
    $where_clause = " group_id=$group_id ";
} else {
    $where_clause = " is_approved=1 ";
}
    

$res = db_query('SELECT forum_id,summary,date,details,group_id FROM news_bytes '
	.'WHERE '.$where_clause.' ORDER BY date DESC LIMIT '.$limit);

// ## one time output
print " <channel>\n";
print "  <copyright>Copyright (c) ".$GLOBALS['sys_long_org_name'].", ".$GLOBALS['sys_name']." Team, 2001-".date('Y',time()).". All Rights Reserved</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y G:i:s',time())." GMT</pubDate>\n";

if ($group_id) {
    print "  <description>".$GLOBALS['sys_name']." Project News Highlights - ".$project->getPublicName()."</description>\n";
    print "  <link>http://".$GLOBALS['sys_default_domain']."/project/?group_id=$group_id</link>\n";
    print "  <title>".$GLOBALS['sys_name']." News - ".$project->getPublicName()."</title>\n";
} else {
    print "  <description>".$GLOBALS['sys_name']." Project News Highlights</description>\n";
    print "  <link>http://".$GLOBALS['sys_default_domain']."</link>\n";
    print "  <title>".$GLOBALS['sys_name']." News</title>\n";
}
list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
print "  <webMaster>webmaster@".$host."</webMaster>\n";
print "  <language>en-us</language>\n";
// ## item outputs
while ($row = db_fetch_array($res)) {
	print "  <item>\n";
	print "   <title>".htmlspecialchars($row[summary])."</title>\n";
	// if news group, link is main page
	if ($row[group_id] != $GLOBALS['sys_news_group']) {
		print "   <link>http://".$GLOBALS['sys_default_domain']."/forum/forum.php?forum_id=$row[forum_id]</link>\n";
	} else {
		print "   <link>http://".$GLOBALS['sys_default_domain']."/</link>\n";
	}
	print "   <description>".rss_description($row['details'])."</description>\n";
	print "  </item>\n";
}
// ## end output
print " </channel>\n";
?>
</rss>
