<?php

$connection = mysql_connect ('localhost', 'dump1090', 'dump1090');

if (!$connection) {
   die('Not connected : ' . mysql_error());
}

$db_selected = mysql_select_db('dump1090', $connection);

if (!$db_selected) {
  die ('Can\'t use db : ' . mysql_error());
}

// $maxQuery = "SELECT MAX(last_update) AS max FROM dump1090.tracks";
// $minQuery = "SELECT MIN(last_update) as min FROM dump1090.tracks";
// $maxResult = mysql_query($maxQuery);
// $minResult = mysql_query($minQuery);
// $max = strtotime(mysql_fetch_row($maxResult)[0]);
// $min = strtotime(mysql_fetch_row($minResult)[0]);

$query = "SELECT GROUP_CONCAT(SUBSTRING(tracks.lon,1, 10), ',', SUBSTRING(tracks.lat,1, 10), ',', tracks.alt separator ' ') AS coordinates, max(tracks.last_update), tracks.icao, flights.flight FROM dump1090.tracks AS tracks INNER JOIN dump1090.flights AS flights ON tracks.icao = flights.icao WHERE tracks.alt != 0 && flights.flight != \"\" GROUP BY tracks.icao WITH ROLLUP;";
$result = mysql_query($query);

if (!$result) {
  die('Invalid query: ' . mysql_error());
}

// Start KML file, create parent node
$dom = new DOMDocument('1.0','UTF-8');

//Create the root KML element and append it to the Document
$node = $dom->createElementNS('http://earth.google.com/kml/2.1','kml');
$parNode = $dom->appendChild($node);
$fnode = $dom->createElement('Folder');

$folderNode = $parNode->appendChild($fnode);

//Iterate through the MySQL results
$count = 1;
while ($row = mysql_fetch_assoc($result)) {
    //Create Line Stylez
	$styleNode = $dom->createElement('Style');
	$styleNode->setAttribute('id','LineStyle'.$count);

	$lineStyle = $styleNode->appendChild($dom->createElement('LineStyle'));
	$lineStyle->appendChild($dom->createElement(color, 'ff'.dechex(rand(1,255)).'ff55'));
	$lineStyle->appendChild($dom->createElement(width,'4'));
	$folderNode->appendChild($styleNode);

	//Create a Placemark and append it to the document
	$placeNode = $folderNode->appendChild($dom->createElement('Placemark'));

	//Create an id attribute and assign it the value of id column
	$placeNode->setAttribute('id',$row['icao']);

	//Create name, description, and address elements and assign them the values of
	//the name, type, and address columns from the results

	$nameNode = $dom->createElement('name',$row['icao']);
	$placeNode->appendChild($nameNode);
	$placeNode->appendChild($dom->createElement(styleUrl, '#LineStyle'. $count));
	$count++;
	//$descNode= $dom->createElement('description', 'This is the path that I took through my favorite restaurants in Seattle');
	//$placeNode->appendChild($descNode);

	//Create a LineString element
	$lineNode = $dom->createElement('LineString');
	$placeNode->appendChild($lineNode);
	$exnode = $dom->createElement('extrude', '0');
	$tesNode = $dom->createElement('tessellate', '0');
	$lineNode->appendChild($exnode);
	$lineNode->appendChild($tesNode);
	$almodenode =$dom->createElement('altitudeMode','relativeToGround');
	$lineNode->appendChild($almodenode);

	//Create a coordinates element and give it the value of the lng and lat columns from the results

	$coorNode = $dom->createElement('coordinates',$row['coordinates']);
	$lineNode->appendChild($coorNode);
}

$kmlOutput = $dom->saveXML();

//assign the KML headers.
header('Content-type: application/vnd.google-earth.kml+xml');
echo $kmlOutput;
?>