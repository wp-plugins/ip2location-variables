<?php

/*
Plugin Name: IP2Location Variables
Plugin URI: http://ip2location.com/tutorials/wordpress-ip2location-variables
Description: Enable you to use IP2Location variables to customize your content by country in any pages, plugins, or themes.
Version: 1.0
Author: IP2Location
Author URI: http://www.ip2location.com
*/

define('DS', DIRECTORY_SEPARATOR);
define('_ROOT', dirname(__FILE__) . DS);

$parts = explode(DS, dirname(__FILE__));
array_pop($parts);
array_pop($parts);

define('IP2LOCATION_DB', implode(DS, $parts) . DS . 'ip2location' . DS);

function ip2location_get_vars(){
	$ip = $_SERVER['REMOTE_ADDR'];
	if(isset($_SESSION['ip2location_' . $ip])){
		return json_decode($_SESSION['ip2location_' . $ip]);
	}

	$data = ip2location_get_location($ip);
	$_SESSION['ip2location_' . $ip] = json_encode($data);

	return json_encode($data);
}

function ip2location_get_location($ip){
	// Make sure IP2Location database is exist
	if(!file_exists(IP2LOCATION_DB . 'database.bin')) return false;

	require_once(_ROOT . 'ip2location.class.php');

	// Create IP2Location object
	$geo = new IP2Location(IP2LOCATION_DB . 'database.bin');

	// Get geolocation by IP address
	$result = $geo->lookup($ip);

	$data = array();

	foreach($result as $key=>$value){
		$data[$key] = ((in_array($key, array('countryName', 'regionName', 'cityName'))) ? ip2location_set_case($value) : $value);
	}

	return $data;
}

function ip2location_set_case($s){
	$s = ucwords(strtolower($s));
	$s = preg_replace_callback("/( [ a-zA-Z]{1}')([a-zA-Z0-9]{1})/s",create_function('$matches','return $matches[1].strtoupper($matches[2]);'),$s);
	return $s;
}

class IP2LocationVariables {
	function admin_options() {
		if(is_admin()) {
			echo '
			<style type="text/css">
				.red{color:#cc0000}
				.code{color:#003399;font-family:\'Courier New\'}
				pre{margin:0 0 20px 0;border:1px solid #c0c0c0;backgroumd:#e4e4e4;color:#535353;font-family:\'Courier New\';padding:8px}
				.result{margin:0 0 20px 0;border:1px solid #006699;backgroumd:#99ffcc;color:#000033;padding:8px}
			</style>
			<div class="wrap">
				<h3>IP2LOCATION VARIABLES</h3>
				<p>
					IP2Location Variables provides a solution to easily get the visitor\'s location information based on IP address and customize the content display in ppages, plugin, or themes for different countries. This plugin uses IP2Location BIN file for location queries, therefore there is no need to set up any relational database to use it. Depending on the BIN file that you are using, this plugin is able to provide you the information of country, region or state, city, latitude and longitude, US ZIP code, time zone, Internet Service Provider (ISP) or company name, domain name, net speed, area code, weather station code, weather station name, mobile country code (MCC), mobile network code (MNC) and carrier brand, elevation and usage type of origin for an IP address.<br/><br/>
					BIN file download: <a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location Commercial database</a> | <a href="http://lite.ip2location.com/?r=wordpress" targe="_blank">IP2Location LITE database (free edition)</a>.
				</p>

				<p>&nbsp;</p>';

			if(!file_exists(IP2LOCATION_DB . 'database.bin')){
				echo '
				<p class="red">
					IP2Location BIN file not found. Please download the BIN file at the following links:
					<a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> | <a href="http://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>.
				</p>
				<p class="red">
					After downloaded the package, decompress it and rename the .BIN file inside the package to <strong>database.bin</strong>. The, upload the BIN file,<strong>database.bin</strong>, to <em>/wp-content/ip2location/</em>.
				</p>';
			}
			else{
				echo '
				<p>
					<b>Database Version: </b>
					' . date('F Y', filemtime(IP2LOCATION_DB . 'database.bin')) . '
				</p>';

				if(filemtime(IP2LOCATION_DB . 'database.bin') < strtotime('-2 months')){
					echo '
					<p class="red">
						<b>Reminder: </b>Your IP2Location database was outdated. Please download the latest version from <a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> or <a href="http://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>..
					</p>
					<p class="red">
						After downloaded the package, decompress it and rename the .BIN file inside the package to <strong>database.bin</strong>. The, upload the BIN file,<strong>database.bin</strong>, to <em>/wp-content/ip2location/</em>.
					</p>';
				}
			}

			echo '
				<p>&nbsp;</p>

				<h3>Usage Example</h3>
				<p>
					Call the function <b>ip2location_get_vars()</b> in any pages, plugins, or themes to retrieve IP2Location variables. The variables are returned in object.

					<pre>&lt;?php
$data = ip2location_get_vars();
?&gt;</pre>
				</p>
				<p>
					Here is the list of fields you can access depends on IP2Location database BIN file you are using.

					<ul>
						<li><span class="code">$data->ipAddress</span> - Visitor IP address.</li>
						<li><span class="code">$data->countryCode</span> - Two-character country code based on ISO 3166.</li>
						<li><span class="code">$data->countryName</span> - Country name based on ISO 3166.</li>
						<li><span class="code">$data->regionName</span> - Region, province or state name.</li>
						<li><span class="code">$data->cityName</span> - City name.</li>
						<li><span class="code">$data->latitude</span> - Latitude of the city.</li>
						<li><span class="code">$data->longitude</span> - Longitude of the city.</li>
						<li><span class="code">$data->zipCode</span> - ZIP/Postal code.</li>
						<li><span class="code">$data->isp</span> - Internet Service Provider or company\'s name.</li>
						<li><span class="code">$data->domainName</span> - Internet domain name associated to IP address range.</li>
						<li><span class="code">$data->timeZone</span> - UTC time zone.</li>
						<li><span class="code">$data->netSpeed</span> - Internet connection type. DIAL = dial up, DSL = broadband/cable, COMP = company/T1</li>
						<li><span class="code">$data->iddCode</span> - The IDD prefix to call the city from another country.</li>
						<li><span class="code">$data->areaCode</span> - A varying length number assigned to geographic areas for call between cities.</li>
						<li><span class="code">$data->weatherStationCode</span> - The special code to identify the nearest weather observation station.</li>
						<li><span class="code">$data->weatherStationName</span> - The name of the nearest weather observation station.</li>
						<li><span class="code">$data->mcc</span> - Mobile Country Codes (MCC) as defined in ITU E.212 for use in identifying mobile stations in wireless telephone networks, particularly GSM and UMTS networks.</li>
						<li><span class="code">$data->mnc</span> - Mobile Network Code (MNC) is used in combination with a Mobile Country Code (MCC) to uniquely identify a mobile phone operator or carrier.</li>
						<li><span class="code">$data->mobileCarrierName</span> - Commercial brand associated with the mobile carrier.</li>
						<li><span class="code">$data->elevation</span> - Average height of city above sea level in meters (m).</li>
						<li><span class="code">$data->usageType</span> - Usage type classification of ISP or company.</li>
					</ul>
				</p>

				<p>&nbsp;</p>

				<h3>References</h3>

				<p>Please visit <a href="http://www.ip2location.com/free/country-multilingual" target="_blank">http://www.ip2location.com</a> for ISO country codes and names supported.</p>';
		}
	}

	function admin_page(){
		add_management_page('IP2Location Variables', 'IP2Location Variables', 8, 'ip2location-variables', array(&$this, 'admin_options'));
	}

	function activate(){
		die(header('Location: edit.php?page=ip2location-variables'));
	}

	function start(){
		add_action('admin_menu', array(&$this, 'admin_page'));
	}
}

// Initial class
$ip2location_vars = new IP2LocationVariables();
$ip2location_vars->start();
?>