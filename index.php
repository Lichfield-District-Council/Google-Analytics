<?php
function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function setCache($metric, $filter, $startdate, $enddate, $max, $data) {
	$id = md5($metric . $filter . $startdate . $enddate . $max);
	$cachefile_full_filename = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$id;
	file_put_contents($cachefile_full_filename, serialize($data));
}

function getCache($metric, $filter, $startdate, $enddate, $max) {
	$id = md5($metric . $filter . $startdate . $enddate . $max);
	$cachefile_full_filename = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$id;
	
	$filetime = filemtime($cachefile_full_filename);
	$timenow = time();
	$oldtime = time()-3600;
	
	if(file_exists($cachefile_full_filename) && ($timenow - $filetime) < $oldtime) {
		return unserialize(file_get_contents($cachefile_full_filename));
	} else {
		return false;
	}
}


define('ga_email',''); // Enter your analytics username here
define('ga_password',''); // Enter your analytics password here

$sites = array(
 "ldc" => array("id" => "829194","name" => "Lichfield District Council","url" => "http://www.lichfielddc.gov.uk")
// You can always add more!
);

require 'gapi.class.php';

$ga = new gapi(ga_email,ga_password);

if ($_REQUEST['visitors'] == "ukonly" ) {
	$filter = 'country == United Kingdom';
} else {
	$filter = null;
}

if ($_REQUEST['site'] == null) { $_REQUEST['site'] = "ldc"; } // This is where the default site is selected
define('ga_profile_id',$sites[$_REQUEST['site']]['id']);

if ($_REQUEST['start_date']) {
	$startdate = $_REQUEST['start_date'];
} else {
	$startdate = date("Y-m-d",time()-60*60*24*30);
}

if ($_REQUEST['end_date']) {
	$enddate = $_REQUEST['end_date'];
} else {
	$enddate = date("Y-m-d",time());
}

if (!$_REQUEST['max']) {
	$max = 100;
} else {
	$max = $_REQUEST['max'];
}

	if ($_REQUEST['metric'] == "screenres") {
	
		if (getCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max) === FALSE) {
			$ga->requestReportData(ga_profile_id,array('screenResolution','screenColors'),array('pageviews','visits','bounces','newVisits','timeOnSite'),"-visits",$filter,$startdate,$enddate,"1",$max);
			setCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max, $ga);
		} else {
			$ga = getCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max);
		}
			$metriccheck['screenres'] = " checked=\"checked\"";
			$metriclabel = "Screen resolution";
		
	} elseif ($_REQUEST['metric'] == "browser") {
	
		if (getCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max) === FALSE) {
			$ga->requestReportData(ga_profile_id,array('browser','browserVersion'),array('pageviews','visits','bounces','newVisits','timeOnSite'),"-visits",$filter,$startdate,$enddate,"1",$max);
			setCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max, $ga);
		} else {
			$ga = getCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max);
		}
			$metriccheck['browser'] = " checked=\"checked\"";
			$metriclabel = "Browser &amp; Browser Version";
		
	} else {

		if (getCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max) === FALSE) {	
			$ga->requestReportData(ga_profile_id,array('pagePath','pageTitle'),array('pageviews','visits','newVisits','bounces','timeOnSite'),"-pageviews",$filter,$startdate,$enddate,"1",$max);
			setCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max, $ga);
		} else {
			$ga = getCache($_REQUEST['metric'],$filter,$startdate,$enddate,$max);
		}
		$metriccheck['pages'] = " checked=\"checked\"";
		$metriclabel = "Popular pages";

	}
	
// other dimensions: screenResolution, visitorType

//print_r($ga);

if ($_GET['format'] == "csv") {

header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"analytics.csv\"");

if ($_REQUEST['metric'] == "screenres") {
	$csv = $metriclabel."	Pageviews	Visits\r\n";
} elseif ($_REQUEST['metric'] == "pages") {
	$csv = "URL	Title	Pageviews\r\n";
} else {
	$csv = $metriclabel."	Pageviews	Visits\r\n";		
}

foreach($ga->getResults() as $result) {

	if ($_REQUEST['metric'] == "screenres") {

		$csv .= $result->getScreenResolution() . "	". $result->getPageviews() ."	". $result->getVisits() ."\r\n";
			
	} elseif ($_REQUEST['metric'] == "browser") {

		$csv .= $result->getBrowser() . " " . $result->getBrowserVersion() ."	". $result->getPageviews() ."	". $result->getVisits() ."\r\n";
			
	} else { // pages
		$csv .= $sites[$_REQUEST['site']]['url']. $result->getPagePath() ."	". $result->getPageTitle() . "	". number_format($result->getPageViews()) ."\r\n";
	}

}

echo $csv;


} else {

$html = "
<table class='totals'>
<!--
<tr>
  <th>Total Results</th>
  <td>".number_format($ga->getTotalResults())."</td>
</tr>
-->
<tr>
  <th>Total Pageviews</th>
  <td>".number_format($ga->getPageviews())."</td>
</tr>
<tr>
  <th>Total Visits</th>
  <td>".number_format($ga->getVisits())."</td>
</tr>
<tr>
  <th>Total New visits (from first-time visitors)</th>
  <td>".number_format($ga->getNewVisits()) . " (" . round($ga->getNewVisits()/$ga->getVisits()*100,1) . "%)"."</td>
</tr>
<tr>
  <th>Total Bounces (single page visits)</th>
  <td>".number_format($ga->getBounces()) . " (" . round($ga->getBounces()/$ga->getVisits()*100,1) . "%)"."</td>
</tr>
<tr>
  <th>Average time on site</th>
  <td>".round(($ga->getTimeOnSite()/$ga->getVisits())/60,2) . " minutes"."</td>
</tr>

<!--
<tr>
  <th>Results Updated</th>
  <td>".$ga->getUpdated()."</td>
</tr>
-->
</table>

<table>
";

if ($_REQUEST['metric'] == "screenres") {
	$html .= "
		<tr>
		  <th>{$metriclabel}</th>
		  <th>Pageviews</th>
		  <th>Visits</th>
		</tr>
		";

} elseif ($_REQUEST['metric'] == "pages") {

	$html .= "
		<tr>
		  <th>URL</th>
		  <th>TItle</th>
		  <th>Pageviews</th>
		</tr>
		";

} else {

	$html .= "
		<tr>
		  <th>{$metriclabel}</th>
		  <th>Pageviews</th>
		  <th>Visits</th>
		</tr>
		";
		
}
	
foreach($ga->getResults() as $result) {

	//print_r($result);


	if ($_REQUEST['metric'] == "screenres") {

		$html .= "
		<tr>
		  <td>".$result->getScreenResolution() . "</td>
		  <td>".$result->getPageviews()."</td>
		  <td>".$result->getVisits()."</td>
		</tr>
		";
			
	} elseif ($_REQUEST['metric'] == "browser") {

		$html .= "
		<tr>
		  <td>".$result->getBrowser() . " " . $result->getBrowserVersion()."</td>
		  <td>".$result->getPageviews()."</td>
		  <td>".$result->getVisits()."</td>
		</tr>
		";
			
	} else { // pages
	
		$html .= "
		<tr>
		  <td><a href=\"{$sites[$_REQUEST['site']]['url']}{$result->getPagePath()}\" target='_blank'>".$result->getPagePath()."</a></td>
		  <td>".str_replace(array("Lichfield District Council", " - ", " | "),"",$result->getPageTitle()) . "</td>
		  <td>".number_format($result->getPageViews())."</td>
		</tr>
		";


	}

}

$html .= "<tr><td colspan='3' class=\"csv\"><a href=\"". curPageURL()."&format=csv\">Download as CSV</a><img src=\"http://www.lichfielddc.gov.uk/site/images/file_type_icons/CSV.gif\" alt=\"CSV file\" /></td></tr>";

$html .= "
</table>

";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

  <head>
  <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />

<style type="text/css">
html {
   background: #eee;
}
body {
  font-family: "Lucida Grande", helvetica, arial, sans-serif;
  padding: 30px 20px 20px 20px;
  font-size: 0.8em;
}
h1, h2 {
  font-weight: normal;
}
h1 {
  color: #023866;
  margin: 10px 0 0 0;
  font-size: 3em;
}
h2 {
  color: #666;
  margin: 0;
  font-size: 1.05em;
}
h3 {
  font-size: 1em;
  margin: 0;
}
.item, #output table {
  margin-bottom: 10px;
  margin-right: 10px;
  padding: 5px 10px;
  background: white;
}
#output table th {
  text-align: left;
  width: 30%;
}
#output table {
  width: 100%;
  table-layout:fixed;
  word-wrap:break-word;
}
.totals {
  background: #ffe;
  border: 1px solid #cca;
}
#footer {
  clear: both;
}
form {
  margin-right: 0 !important;
}

td.csv {
	text-align: right;
	padding: 7px 0;
	}
	
td.csv img {
	padding-left: 7px;
	}

</style>

<title>Web traffic data from Lichfield District Council</title>
<link type="text/css" href="css/smoothness/jquery-ui-1.8.4.custom.css" rel="Stylesheet" />	
<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.4.custom.min.js"></script>

<script type="text/javascript">
$(function() {
	$(".date").datepicker({dateFormat: 'yy-mm-dd'});
});
</script>

</head>
<body>
<img src="LDClogo.png" alt="Lichfield District Council" style="float: right; margin: 10px;" />
<h1>Lichfield District Council website traffic data</h1>
<h2>An experimental tool to provide live access to non-personal data on usage of the Lichfield District Council website tracked using Google Analytics</h2>

<br />

<form method='get' action='index.php' class='item'>
<h3>Select criteria for data report:</h3>

<p>Site to analyse:<br /><select name='site'>

<?php

foreach($sites as $sid => $sinfo) {
	$selected = ($_REQUEST['site'] == $sid) ? "selected='selected'" : null;
	echo "<option value='{$sid}' $selected>{$sinfo['name']}</option>\r";
}

?>

</select></p>

<p>Visitors:<br /><select name='visitors'>
<option value='all' <?php if ($_REQUEST['visitors'] == "all") echo "selected='selected'"; ?>>All visitors</option>
<option value='ukonly' <?php if ($_REQUEST['visitors'] == "ukonly") echo "selected='selected'"; ?>>UK visitors only</option>
</select></p>

<p>Start date (YYYY-MM-DD) (leave blank for all available data):<br /><input type="text" name='start_date' value="<?php echo ($startdate); ?>" size='30' class="date" /></p>
<p>End date (YYYY-MM-DD) (leave blank for all available data):<br /><input type="text" name='end_date' value="<?php echo ($enddate); ?>" size='30' class="date"  /></p>

<p>Analysis:<br />
<label><input type='radio' name='metric' value="browser"<?php echo($metriccheck['browser']);?> />Browser version</label> &nbsp; 
<label><input type='radio' name='metric' value="screenres"<?php echo($metriccheck['screenres']);?> />Screen resolution</label>
<label><input type='radio' name='metric' value="pages"<?php echo($metriccheck['pages']);?> />Popular pages</label>
</p>

<p>
Maximum Results:<br />
<select name='max'>
<option>10</option>
<option>50</option>
<option>100</option>
<option>1000</option>
<option>5000</option>
<option>10000</option>
</select>
</p>

<input type='submit' value='Get data' />
</form>

<div id="output">
<?php echo ($html); ?>
</div>

<div id='footer'><p>Based on the <a href="http://interactive.bis.gov.uk/gastats">BIS websites traffic data</a> tool from the Department for Business, Innovation and Skills Crown Copyright, 2009-10.</p>
<p>This data is free to reuse under the terms of a <a href="http://creativecommons.org/publicdomain/zero/1.0/">Creative Commons CC0 1.0 Universal Licence</a>. Made possible by the <a href="http://code.google.com/apis/analytics/">Google Analytics API</a> and <a href="http://code.google.com/p/gapi-google-analytics-php-interface/">GAPI</a>.</p></div>

</body>
</html>

<?php } ?>



