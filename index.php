<?php

/*
Plugin Name: IP2Location Variables
Plugin URI: http://ip2location.com/tutorials/wordpress-ip2location-variables
Description: A library that enables you to use IP2Location variables to display and customize the contents by country in pages, plugins, or themes.
Version: 1.1
Author: IP2Location
Author URI: http://www.ip2location.com
*/

define('DS', DIRECTORY_SEPARATOR);
define('_ROOT', dirname(__FILE__) . DS);

$parts = explode(DS, dirname(__FILE__));
array_pop($parts);
array_pop($parts);

// Change the DB location to the same folder as plugin
// define('IP2LOCATION_DB', implode(DS, $parts) . DS . 'ip2location' . DS);
define('IP2LOCATION_DB', substr(plugin_dir_path(__FILE__), 0, -1) . DS . "database.bin");
define('DEFAULT_SAMPLE_BIN', substr(plugin_dir_path(__FILE__), 0, -1) . DS . "default_sample_bin.txt");
//call the function to set server variable
ip2location_set_vars();

//118 : Set the server variable
function ip2location_set_vars(){
	$ip = $_SERVER['REMOTE_ADDR'];
	$envdata = ip2location_get_location($ip);
	$_SERVER['IP2LOCATION_IP_ADDRESS'] = $envdata['ipAddress'];
	$_SERVER['IP2LOCATION_COUNTRY_SHORT'] = $envdata['countryCode'];
	$_SERVER['IP2LOCATION_COUNTRY_LONG'] = $envdata['countryName'];
	$_SERVER['IP2LOCATION_REGION'] = $envdata['regionName'];
	$_SERVER['IP2LOCATION_CITY'] = $envdata['cityName'];
	$_SERVER['IP2LOCATION_ISP'] = $envdata['isp'];
	$_SERVER['IP2LOCATION_LATITUDE'] = $envdata['latitude'];
	$_SERVER['IP2LOCATION_LONGITUDE'] = $envdata['longitude'];
	$_SERVER['IP2LOCATION_DOMAIN'] = $envdata['domainName'];
	$_SERVER['IP2LOCATION_ZIPCODE'] = $envdata['zipCode'];
	$_SERVER['IP2LOCATION_TIMEZONE'] = $envdata['timeZone'];
	$_SERVER['IP2LOCATION_NETSPEED'] = $envdata['netSpeed'];
	$_SERVER['IP2LOCATION_IDDCODE'] = $envdata['iddCode'];
	$_SERVER['IP2LOCATION_AREACODE'] = $envdata['areaCode'];
	$_SERVER['IP2LOCATION_WEATHERSTATIONCODE'] = $envdata['weatherStationCode'];
	$_SERVER['IP2LOCATION_WEATHERSTATIONNAME'] = $envdata['weatherStationName'];
	$_SERVER['IP2LOCATION_MCC'] = $envdata['mcc'];
	$_SERVER['IP2LOCATION_MNC'] = $envdata['mnc'];
	$_SERVER['IP2LOCATION_MOBILEBRAND'] = $envdata['mobileCarrierName'];
	$_SERVER['IP2LOCATION_ELEVATION'] = $envdata['elevation'];
	$_SERVER['IP2LOCATION_USAGETYPE'] = $envdata['usageType'];
}

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
	if(!file_exists(IP2LOCATION_DB)) return false;

	require_once(_ROOT . 'ip2location.class.php');

	// Create IP2Location object
	$geo = new IP2Location(IP2LOCATION_DB);

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
			//108 : added the function for download db similar to country blocker
			if(!file_exists(IP2LOCATION_DB)) {
				echo '
				<div style="border:1px solid #f00;background:#faa;padding:10px">
					Unable to find the IP2Location BIN database! Please download the database at at <a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> | <a href="http://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>.
				</div>';
			}else {
				if (file_exists(DEFAULT_SAMPLE_BIN)){
					//Still using the sample old BIN
					echo '
					<p>
						<b>Current Database Version: </b>
					</p>
					<p style="border:1px solid #f00;background:#faa;padding:10px">
						<strong>Reminder: </strong>Your IP2Location database was outdated. Please download the latest version for accurate result.
					</p>';
				}
				else{
					echo '
					<p>
						<b>Current Database Version: </b>
						' . date('F Y', filemtime(IP2LOCATION_DB)) . '
					</p>';

					if(filemtime(IP2LOCATION_DB) < strtotime('-2 months')) {
						echo '
						<p style="border:1px solid #f00;background:#faa;padding:10px">
							<strong>Reminder: </strong>Your IP2Location database was outdated. Please download the latest version for accurate result.
						</p>';
					}
				}
			}


			echo '
				<script>
					jQuery(document).ready(function($) {
						// Code here will be executed on document ready. Use $ as normal.
						jQuery("#download").click(function(){
							var product_code = jQuery("#product_code").val();
							var username = jQuery("#username").val();
							var password = jQuery("#password").val();

							//disable the download button
							jQuery("#download").attr("disabled","disabled");
							jQuery("#download_status").html("<div style=\"padding:10px; border:1px solid #ccc; background-color:#ffa;\">Downloading " + product_code + " BIN database in progress... Please wait...</div>");

							var data = {
								\'action\': \'download_db\',
								\'product_code\':product_code.toString(),
								\'username\':username.toString(),
								\'password\':password.toString()
							};

							$.post(ajaxurl, data, function(result) {
								if (result == "SUCCESS"){
									alert("Downloading completed.");
									jQuery("#download_status").html("<div style=\"padding:10px; border:1px solid #0f0; background-color:#afa;\">Successfully downloaded the " + product_code + " BIN database. Please refresh information by reloading the page.</div>");
								}
								else{
									alert("Downloading failed");
									jQuery("#download_status").html("<div style=\"padding:10px; border:1px solid #f00; background-color:#faa;\">Failed to download " + product_code + " BIN database. Please make sure you correctly enter the product code and login crendential. Please also take note to download the BIN product code only.</a>");
								}
							}).always(function() {
								//clear the entry
								jQuery("#product_code").val("");
								jQuery("#username").val("");
								jQuery("#password").val("");
								jQuery("#download").removeAttr("disabled");
							});
						});
					});
				</script>
				<div style="margin-top:10px; padding:10px; border:1px solid #ccc;">
					<span style="display:block; font-weight:bold; margin-bottom:5px;">Download BIN Database</span>
					Product Code: <select id="product_code" type="text" value="" style="margin-right:10px;" >
						<option value="DB1LITEBIN">DB1LITEBIN</option>
						<option value="DB3LITEBIN">DB3LITEBIN</option>
						<option value="DB5LITEBIN">DB5LITEBIN</option>
						<option value="DB9LITEBIN">DB9LITEBIN</option>
						<option value="DB11LITEBIN">DB11LITEBIN</option>
						<option value="DB1BIN">DB1BIN</option>
						<option value="DB2BIN">DB2BIN</option>
						<option value="DB3BIN">DB3BIN</option>
						<option value="DB4BIN">DB4BIN</option>
						<option value="DB5BIN">DB5BIN</option>
						<option value="DB6BIN">DB6BIN</option>
						<option value="DB7BIN">DB7BIN</option>
						<option value="DB8BIN">DB8BIN</option>
						<option value="DB9BIN">DB9BIN</option>
						<option value="DB10BIN">DB10BIN</option>
						<option value="DB11BIN">DB11BIN</option>
						<option value="DB1LITEBINIPV6">DB1LITEBINIPV6</option>
						<option value="DB3LITEBINIPV6">DB3LITEBINIPV6</option>
						<option value="DB5LITEBINIPV6">DB5LITEBINIPV6</option>
						<option value="DB9LITEBINIPV6">DB9LITEBINIPV6</option>
						<option value="DB11LITEBINIPV6">DB11LITEBINIPV6</option>
						<option value="DB1BINIPV6">DB1BINIPV6</option>
						<option value="DB2BINIPV6">DB2BINIPV6</option>
						<option value="DB3BINIPV6">DB3BINIPV6</option>
						<option value="DB4BINIPV6">DB4BINIPV6</option>
						<option value="DB5BINIPV6">DB5BINIPV6</option>
						<option value="DB6BINIPV6">DB6BINIPV6</option>
						<option value="DB7BINIPV6">DB7BINIPV6</option>
						<option value="DB8BINIPV6">DB8BINIPV6</option>
						<option value="DB9BINIPV6">DB9BINIPV6</option>
						<option value="DB10BINIPV6">DB10BINIPV6</option>
						<option value="DB11BINIPV6">DB11BINIPV6</option>
					</select>
					Email: <input id="username" type="text" value="" style="margin-right:10px;" />
					Password: <input id="password" type="password" value="" style="margin-right:10px;" /> <input type="submit" name="download" id="download" value="Download" class="button action" />
					<input id="site_url" type="hidden" value="' . get_site_url() . '" />
					<span style="display:block; font-size:0.8em">Enter the product code, i.e, DB1LITEBIN, (the code in square bracket on your license page) and login credential for the download.</span>

					<div style="margin-top:20px;">
						Note: If you failed to download the BIN database using this automated downloading tool, please follow the below procedures to manually update the database.
						<ol style="list-style-type:circle;margin-left:30px">
							<li>Download the BIN database at <a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> | <a href="http://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>.</li>
							<li>Decompress the zip file and rename the BIN database to <b>database.bin</b>.</li>
							<li>Upload <b>database.bin</b> to /wp-content/plugins/ip2location-country-blocker/.</li>
							<li>Once completed, please refresh the information by reloading the setting page.</li>
						</ol>
					</div>
				</div>
				<div id="download_status" style="margin:10px 0;">

				</div>
			';

			echo '
				<p>&nbsp;</p>

				<h3>Usage Example</h3>
				<p>
					Call the function <b>ip2location_get_vars()</b> in any pages, plugins, or themes to retrieve IP2Location variables. The variables are returned in object. 
					Use <b>json_decode()</b> to decode the json object.

					<pre>&lt;?php
$data = ip2location_get_vars();
$data = json_decode($data);
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

				<h3>Usage Example (Server Variables Method)</h3>
				<p>
					Use any of the server variables below to retrieve IP2Location variables.
				</p>
				<p>
					Here is the list of fields you can access depends on IP2Location database BIN file you are using.

					<ul>
						<li><span class="code">$_SERVER[\'IP2LOCATION_IP_ADDRESS\']</span> - Visitor IP address.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_COUNTRY_SHORT\']</span> - Two-character country code based on ISO 3166.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_COUNTRY_LONG\']</span> - Country name based on ISO 3166.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_REGION\']</span> - Region, province or state name.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_CITY\']</span> - City name.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_LATITUDE\']</span> - Latitude of the city.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_LONGITUDE\']</span> - Longitude of the city.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_ZIPCODE\']</span> - ZIP/Postal code.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_ISP\']</span> - Internet Service Provider or company\'s name.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_DOMAIN\']</span> - Internet domain name associated to IP address range.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_TIMEZONE\']</span> - UTC time zone.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_NETSPEED\']</span> - Internet connection type. DIAL = dial up, DSL = broadband/cable, COMP = company/T1</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_IDDCODE\']</span> - The IDD prefix to call the city from another country.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_AREACODE\']</span> - A varying length number assigned to geographic areas for call between cities.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_WEATHERSTATIONCODE\']</span> - The special code to identify the nearest weather observation station.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_WEATHERSTATIONNAME\']</span> - The name of the nearest weather observation station.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_MCC\']</span> - Mobile Country Codes (MCC) as defined in ITU E.212 for use in identifying mobile stations in wireless telephone networks, particularly GSM and UMTS networks.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_MNC\']</span> - Mobile Network Code (MNC) is used in combination with a Mobile Country Code (MCC) to uniquely identify a mobile phone operator or carrier.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_MOBILEBRAND\']</span> - Commercial brand associated with the mobile carrier.</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_ELEVATION\']</span> - Average height of city above sea level in meters (m).</li>
						<li><span class="code">$_SERVER[\'IP2LOCATION_USAGETYPE\']</span> - Usage type classification of ISP or company.</li>
					</ul>
				</p>
				
				<p>&nbsp;</p>

				<h3>References</h3>

				<p>Please visit <a href="http://www.ip2location.com/free/country-multilingual" target="_blank">http://www.ip2location.com</a> for ISO country codes and names supported.</p>';
		}
	}
	//change management to options
	function admin_page(){
		add_options_page('IP2Location Variables', 'IP2Location Variables', 8, 'ip2location-variables', array(&$this, 'admin_options'));
	}

	function activate(){
		die(header('Location: edit.php?page=ip2location-variables'));
	}

	function start(){
		add_action('admin_menu', array(&$this, 'admin_page'));
	}
}
//function to download db
function ip2location_download_db() {
	try {
		$product_code = $_POST['product_code'];
		$username = $_POST['username'];
		$password = $_POST['password'];

		if(!class_exists('WP_Http'))
			include_once(ABSPATH . WPINC . '/class-http.php');

		$request = new WP_Http ();
		$result = $request->request ("http://www.ip2location.com/download?productcode=" . strtoupper($product_code) . "&login=" . rawurlencode($username) . "&password=" . rawurlencode($password), array('timeout' => 120));
		
		if ((isset ($result->errors)) || (! (in_array ('200', $result ['response'])))) die('ERROR');

		$fp = fopen (WP_PLUGIN_DIR . "/" . dirname (plugin_basename (__FILE__)) . "/database.zip", "w");
		
		fwrite ($fp, $result['body']);
		fclose ($fp);
		// unzip the file
		$zip = zip_open(WP_PLUGIN_DIR . "/" . dirname (plugin_basename (__FILE__)) . "/database.zip");
		// Make sure it is a ZIP resource
		if (is_resource($zip)) {
			$found = false;
			while($zip_entry = zip_read($zip)) {
				// Extract the BIN file only
				$zip_name = zip_entry_name($zip_entry);
				$pos = strpos(strtoupper($zip_name), '.BIN');
				if ($pos !== false) {
					$file_size = zip_entry_filesize($zip_entry);
					$whandle = fopen(WP_PLUGIN_DIR . "/" . dirname (plugin_basename (__FILE__)) . "/database.bin", 'w+');
					fwrite($whandle, zip_entry_read($zip_entry, $file_size));
					fclose($whandle);

					//remove the default sample file upon successfully download the latest copy.
					if (file_exists(DEFAULT_SAMPLE_BIN))
						unlink(DEFAULT_SAMPLE_BIN);

					// success
					$found = true;
				}
			}
			// Only report true upon success unzip
			if ($found)
				echo "SUCCESS";
			else
				echo "ERROR";

			@unlink(WP_PLUGIN_DIR . "/" . dirname (plugin_basename (__FILE__)) . "/database.zip");
		}else
			echo "ERROR";
	}
	catch (Exception $e) {
		echo 'ERROR' . $e . getMessage();
	}

	die;
}

add_action('wp_ajax_download_db', 'ip2location_download_db');

// Initial class
$ip2location_vars = new IP2LocationVariables();
$ip2location_vars->start();
?>