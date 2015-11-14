<?php

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
 
/**
 * Dependencies: Compose, WordPress, MySQL.
 */
 
// Configuration options.
$drupalDbHost = 'localhost';
$drupalDbPort = '3306';
$drupalDb = 'gameblaster64-old';
$drupalDbUser = 'root';
$drupalDbPass = '';
$drupalDbTblPrefix = 'gameblaster64_';

// WordPress configuration is handled through wp-config.php.
require_once('../wp-config.php');
require_once('../wp-load.php');

$uriBase = 'http://gameblaster64.xandorus.com';
$uriMakeAbsolute = true;
$htmlAllowed = 'p,b,a[href|title|rel],i,ul,ol,li,img[src|alt|width|height],br,iframe[src|width|height|frameborder],h1,h2,h3,h4,h5,table,thead,tbody,th,tr,td,span';
// End of configuration options.

$drupalDbTblBody = $drupalDbTblPrefix . 'field_data_body';
$drupalDbTblTags = $drupalDbTblPrefix . 'field_data_field_tags';
$drupalDbTblNode = $drupalDbTblPrefix . 'node';
$drupalDbTblAlias = $drupalDbTblPrefix . 'url_alias';
$drupalDbTblTaxonomy = $drupalDbTblPrefix . 'taxonomy_term_data';

require_once('vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php');

$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Trusted', true);
$config->set('Filter.YouTube', true);
$config->set('HTML.Allowed', $htmlAllowed);
$config->set('URI.Base', $uriBase);
$config->set('URI.MakeAbsolute', $uriMakeAbsolute);

$purifier = new HTMLPurifier($config);

$db = new PDO('mysql:host=' . $drupalDbHost . ':' . $drupalDbPort . ';dbname=' . $drupalDb, $drupalDbUser, $drupalDbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"'));

?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<link href="https://cdn.jsdelivr.net/pure/0.6.0/pure-min.css" rel="stylesheet">
	</head>
	<body>
	<div class="content-wrapper">
		<div class="content">
			<h1>Drupal To Wordpress</h1>

			<div class="pure-g">
				<div class="pure-u-1-2">
					<h2>From Drupal</h2>
					<h3><?php echo $drupalDbHost; ?>:<?php echo $drupalDbPort; ?>, <?php echo $drupalDb; ?></h3>
					<p>Exporting articles (posts) from Drupal: <?php $drupalArticles = getArticles(); ?>Done!</p>
					<p>Exporting taxonomies (tags) from Drupal: <?php $drupalTaxonomies = getTaxonomiesUrls(); ?>Done!</p>
					<p>Mapping taxonomies to articles: <?php $drupalArticles = mapTaxonomiesToArticles($drupalArticles); ?>Done!</p>
				</div>
				<div class="pure-u-1-2">
					<h2>To Wordpress</h2>
					<p>Importing completed posts to Wordpress: <?php putPosts($drupalArticles); ?>Done!</p>
					<p>Generating HTTP 301 redirects for posts: <?php // create301Articles(); ?>Done!</p>
					<p>Generating HTTP 301 redirects for tags: <?php // create301Taxonomies(); ?>Done!</p>
				</div>
			</div>
		</div>
	</div>
    </body>
</html>
<?php

/**
 * Extract articles from a Drupal 7 database.
 */
function getArticles() {

    global $db, $drupalDbTblBody, $drupalDbTblNode, $drupalDbTblAlias;

    $results = [];

    $sql  = 'SELECT ';
    $sql .= $drupalDbTblNode . '.nid, ';
    $sql .= $drupalDbTblNode . '.title, ';
	$sql .= $drupalDbTblNode . '.created, ';
    $sql .= $drupalDbTblAlias . '.alias, ';
    $sql .= $drupalDbTblBody . '.body_value as body ';
    
    $sql .= 'FROM ' . $drupalDbTblBody . ' ';
    $sql .= 'INNER JOIN ' . $drupalDbTblNode . ' ON ' . $drupalDbTblBody . '.entity_id = ' . $drupalDbTblNode . '.nid ';
    $sql .= 'INNER JOIN ' . $drupalDbTblAlias . ' ON ' . $drupalDbTblAlias . '.source = CONCAT("node/", ' . $drupalDbTblNode . '.nid)';

	$sql .= 'ORDER BY ' . $drupalDbTblNode . '.created';
	
    $stmt = $db->prepare($sql);

    $stmt->execute();

    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[$result['nid']] = $result;
    }

    return $results;
}

/**
 * Extract the URLs to taxonomies from a Drupal 7 database.
 */
function getTaxonomiesUrls() {

    global $db, $drupalDbTblNode, $drupalDbTblAlias, $drupalDbTblTaxonomy;

    $results = [];

    $sql  = 'SELECT ';
    $sql .= $drupalDbTblAlias . '.alias, ';
	$sql .= $drupalDbTblTaxonomy . '.tid, ';
	$sql .= $drupalDbTblTaxonomy . '.name ';
	$sql .= 'FROM ' . $drupalDbTblAlias . ' ';
	$sql .= 'INNER JOIN ' . $drupalDbTblTaxonomy . ' ON ' . $drupalDbTblAlias . '.source = CONCAT("taxonomy/term/", ' . $drupalDbTblTaxonomy . '.tid) ';
	$sql .= 'ORDER BY ' . $drupalDbTblTaxonomy . '.name';

    $stmt = $db->prepare($sql);

    $stmt->execute();

    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[$result['tid']] = $result;
    }

    return $results;
}

function mapTaxonomiesToArticles($drupalArticles) {

	$map = getTaxonomyArticleRelation();
    
    foreach ($map as $articleId => $tags) {
        $drupalArticles[$articleId]['tags'] = $tags;
    }
    
    return $drupalArticles;	
}

/**
 * Match the taxonomies to each article they're a part of.
 */
function getTaxonomyArticleRelation() {

    global $db, $articles, $drupalDbTblTags, $drupalDbTblAlias, $drupalDbTblTaxonomy;

    $results = [];

    $sql  = 'SELECT ';
    $sql .= $drupalDbTblTags . '.entity_id AS articleId, ';
    $sql .= $drupalDbTblTaxonomy . '.name ';
    $sql .= 'FROM ' . $drupalDbTblTags . ' ';
    $sql .= 'INNER JOIN ' . $drupalDbTblTaxonomy . ' ON ' . $drupalDbTblTags . '.field_tags_tid = ' . $drupalDbTblTaxonomy . '.tid';
    
    $stmt = $db->prepare($sql);

    $stmt->execute();

    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[$result['articleId']][] = $result['name'];
    }
    
    return $results;
}

/**
 * Insert posts into Wordpress.
 */
function putPosts($drupalArticles) {
    
    /**
     *    Use WordPress functions rather than inserting records directly
     * through MySQL just in case functionality changes, etc. This
     * relieves us of any changes in WordPress database structure
     * across versions.
     */
    foreach ($drupalArticles as $key => $article) {
        $post = array(
                'post_author' => 1,
                'post_content' => $article['body'],
                'post_date' => date('Y-m-d H:i:s', $article['created']),
                'post_name' => $article['alias'],
                'post_status' => 'publish',
                'post_title' => $article['title'],
                'tags_input' =>$article['tags']
            );
        wp_insert_post($post);
    }    
}
?>