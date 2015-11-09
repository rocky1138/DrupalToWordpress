﻿<?php

/**
 * The MIT License (MIT).
 *
 * Copyright (c) 2015 John Rockefeller.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the 'Software'), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
 
// Configuration options.
$uriBase = 'http://gameblaster64.xandorus.com';
$uriMakeAbsolute = true;
$htmlAllowed = 'p,b,a[href|title|rel],i,ul,ol,li,img[src|alt|width|height],br,iframe[src|width|height|frameborder],h1,h2,h3,h4,h5,table,thead,tbody,th,tr,td,span';

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
	<link href="https://cdn.jsdelivr.net/pure/0.6.0/pure-min.css" rel="stylesheet">
</head>
<body>

<table class="pure-table">
	<thead>
		<tr>
			<th>Title</th>
			<th>URL alias</th>
			<th>Body</th>
		</tr>
	</thead>
	
	<tbody>

<?php

require_once('vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php');

$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Trusted', true);
$config->set('Filter.YouTube', true);
$config->set('HTML.Allowed', $htmlAllowed);
$config->set('URI.Base', $uriBase);
$config->set('URI.MakeAbsolute', $uriMakeAbsolute);

$purifier = new HTMLPurifier($config);

$db = new PDO('mysql:host=localhost;dbname=gameblaster64-old', 'root', '', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"'));

$tblBody = 'gameblaster64_field_data_body';
$tblNode = 'gameblaster64_node';
$tblUrlAlias = 'gameblaster64_url_alias';

$articles = getArticles();

for ($i = 0; $i < count($articles); $i++) {
    
    echo '<tr>';
    echo '<td>';
    
    echo $articles[$i]['title'];
    
    echo '</td>';
    echo '<td>';
    
    echo $articles[$i]['alias'];
    
    echo '</td>';
    echo '<td>';
    
    echo htmlentities($purifier->purify($articles[$i]['body']));
    //echo htmlentities($articles[$i]['body']);
    
    echo '</td>';
    echo '</tr>';   
}

?>
	</tbody>

</table>

<?php

// Taxonomies URLs.
echo '<h2>Taxonomies URLs</h2>';

$taxonomiesUrls = getTaxonomiesUrls();

sort($taxonomiesUrls);

for ($i = 0; $i < count($taxonomiesUrls); $i++) {
    echo $taxonomiesUrls[$i] . '<br>';
}

/**
 * Extract articles from a Drupal 7 database.
 */
function getArticles() {

    global $db, $tblBody, $tblNode, $tblUrlAlias;

    $results = [];

    $sql  = 'SELECT ';
    $sql .= $tblNode . '.title, ';
    $sql .= $tblUrlAlias . '.alias, ';
    $sql .= $tblBody . '.body_value as body ';
    
    $sql .= 'FROM ';
    $sql .= $tblBody . ' ';
    $sql .= 'INNER JOIN ' . $tblNode . ' ON ' . $tblBody . '.entity_id = ' . $tblNode . '.nid ';
    $sql .= 'INNER JOIN ' . $tblUrlAlias . ' ON ' . $tblUrlAlias . '.source = CONCAT("node/", ' . $tblNode . '.nid)';

    $stmt = $db->prepare($sql);

    $stmt->execute();

    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = $result;
    }

    return $results;
}

/**
 * Extract the URLs to taxonomies from a Drupal 7 database.
 */
function getTaxonomiesUrls() {

    global $db, $tblNode, $tblUrlAlias;

    $results = [];

    $sql  = 'SELECT alias FROM ' . $tblUrlAlias . ' ';
    $sql .= 'LEFT JOIN ' . $tblNode . ' ON ' . $tblUrlAlias . '.source = CONCAT("node/", ' . $tblNode . '.nid)';
    $sql .= 'WHERE (' . $tblNode . '.nid is NULL)';

    $stmt = $db->prepare($sql);

    $stmt->execute();

    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = $result['alias'];
    }

    return $results;
}
?>
    </body>
</html>