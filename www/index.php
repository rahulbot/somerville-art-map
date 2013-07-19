<?php
// figure out what neighborhood to show
if(array_key_exists('neighborhood',$_GET)) {
	$neighborhood = $_GET['neighborhood'];
} else {
	$neighborhood = "Somerville";
}
$title = "Public Art in ".$neighborhood;

// parse in the data from the scraper
$current_number = 1;
$neighborhood_list = array();
$all_art_pieces = json_decode(file_get_contents("somerville-public-art-list.json"));
$art_pieces = array();
foreach ($all_art_pieces as $art_piece) {
	if( ($neighborhood=="Somerville") || ($art_piece->neighborhood==$neighborhood) ) {
		if(empty($art_piece->latitude) && empty($art_piece->longitude)) {	// handle non-geocoded ones well
			$art_piece->number = "?";
		} else {
			$art_piece->number = $current_number;
			$current_number++;
		}
		$neighborhood_list[] = $art_piece->neighborhood;
		$art_pieces[] = $art_piece;
	}
}
$neighborhood_list = array_unique($neighborhood_list);
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo $title?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1;" />
    
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css">
    <link href="css/leaflet.css" rel="stylesheet" type="text/css">
	<!--[if lte IE 8]>
    	<link href="css/leaflet.ie.css" rel="stylesheet">
    <![endif]-->
    <link href="css/art-map.css" rel="stylesheet" type="text/css">
	<link href="css/art-map-print.css" rel="stylesheet" type="text/css" media="print"/>
</head>

<body>

<div id="sam-app">

	<div class="container-fluid">

		<div class="row-fluid">
			<div class="span12 sam-header">
				<h1><?php echo $title?>
				<?php if($neighborhood=="Somerville"){ ?>
					<br />
					<h2 class="subhead">
					<?php foreach($neighborhood_list as $name){ ?>
						<a href="index.php?neighborhood=<?php echo $name?>"><?php echo $name?></a> &nbsp;
					<?php } ?>
					</h2>
				<?php } ?>
				</h1>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span12">
				 <div id="sam-map"></div>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span12 sam-art-piece-list">
				<?php foreach ($art_pieces as $art_piece){ ?>
				<div class="sam-art-piece">
					<img class="sam-number-background" src="img/marker-square.png"/>
					<div class="number sam-number"><?php print $art_piece->number; ?></div>
					<img class="sam-thumbnail" src="<?php print $art_piece->thumbnail_url; ?>" alt="<?php print $art_piece->name; ?>"
							name="<?php print $art_piece->name; ?>" data-number="<?php print $art_piece->number; ?>"/>
				</div>
				<?php } ?>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span12 sam-footer">
				<p>
				<i>
				Find up-to-date content, and submit missing art at 
					<a class="do-not-print" href="http://somervilleartscouncil.org/artmap">http://somervilleartscouncil.org/artmap</a>
					<span class="only-print">http://somervilleartscouncil.org/artmap</span>
				<br />
				Brought to you by the Somerville Arts Council.  Maps courtesy Stamen, Inc.
				<span class="do-not-print">
					<br />
					Download the list as a <a href="somerville-public-art-list.csv">CSV</a> 
					or in <a href="somerville-public-art-list.json">JSON</a> format.
				</span>
				</i>
				</p>
			</div>
		</div>

	</div>

</div>

<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/leaflet.js"></script>
<script type="text/javascript" src="js/leaflet-numbered-markers.js"></script>
<script type="text/javascript" src="js/tile.stamen.js"></script>

<script type="text/javascript">
// set up the map
var layer = new L.StamenTileLayer("toner");
var map = new L.Map("sam-map", {
    center: new L.LatLng(42.38, -71.099),
    zoom: 10,
    maxZoom: 17,	// don't zoom in too much
    /*zoomControl: false*/
});
// turn off zooming - we want a static map here
/*map.dragging.disable();
map.touchZoom.disable();
map.doubleClickZoom.disable();
map.scrollWheelZoom.disable();
*/
map.addLayer(layer);
// now add the pins to the map
var markers = [];	// a list of all the markers
<?php foreach ($art_pieces as $art_piece){ ?>
	var marker = L.marker([<?php echo $art_piece->latitude?>, <?php echo $art_piece->longitude?>], {
		icon: new L.NumberedDivIcon({number: <?php echo $art_piece->number?>})
	});
	markers.push(marker);
	marker.addTo(map);
<?php } ?>
// zoom to the extents of the markers
var group = new L.featureGroup(markers);
map.fitBounds(group.getBounds());

// add a click handler to highlight things on the map
$('.sam-thumbnail').click(function(){
	// reset all the markers
	$('.leaflet-marker-icon').removeClass('bring-to-top').addClass('faded');
	// hight this image's marker
	var number = $(this).attr('data-number');
	var marker = $('#sam-map .number[data-number='+number+']').parent();
	//marker.attr('data-z-index',marker.css('z-index'));	// save originial z-index
	marker.removeClass('faded').addClass('bring-to-top');	// bring to top
	$('html, body').animate({scrollTop: 0}, 500);
	// center on the marker
	map.panTo(markers[number-1].getLatLng());
});
</script>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-42576470-1', 'somervilleartmap.org');
  ga('send', 'pageview');

</script>

</body>

</html>
