<?php
/*
Plugin Name: IP2Location Variables
Plugin URI: http://ip2location.com/tutorials/wordpress-ip2location-variables
Description: A library that enables you to use IP2Location variables to display and customize the contents by country in pages, plugins, or themes.
Version: 2.0.0
Author: IP2Location
Author URI: http://www.ip2location.com
*/
defined( 'DS' ) or define( 'DS', DIRECTORY_SEPARATOR );
define( 'IP2LOCATION_VARIABLES_ROOT', dirname( __FILE__ ) . DS );

class IP2LocationVariables {
	function admin_page() {
		add_options_page( 'IP2Location Variables', 'IP2Location Variables', 8, 'ip2location-variables', array( &$this, 'admin_options' ) );
	}

	function init() {
		add_action('admin_menu', array(&$this, 'admin_page'));
	}

	function set_defaults() {
		// Initial default settings
		update_option( 'ip2location_variables_lookup_mode', 'bin' );
		update_option( 'ip2location_variables_api_key', '' );
		update_option( 'ip2location_variables_database', '' );

		// Find any .BIN files in current directory
		$files = scandir( IP2LOCATION_VARIABLES_ROOT );

		foreach( $files as $file ){
			if ( strtoupper( substr( $file, -4 ) ) == '.BIN' ){
				update_option( 'ip2location_variables_database', $file );
				break;
			}
		}
	}

	function uninstall() {
		// Remove all settings
		delete_option( 'ip2location_variables_lookup_mode' );
		delete_option( 'ip2location_variables_api_key' );
		delete_option( 'ip2location_variables_database' );
	}

	function add_variables() {
		if ( !session_id() ) {
			session_start();
		}

		$ipAddress = $_SERVER['REMOTE_ADDR'];

		if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
			$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		if ( !isset( $_SESSION['ip2location_variables_' . $ipAddress ] ) ) {
			$result = $this->get_location( $ipAddress );

			$_SESSION['ip2location_variables_' . $ipAddress ] = json_encode($result);
		}

		if ( is_null( $json = json_decode( $_SESSION['ip2location_variables_' . $ipAddress ] ) ) === FALSE ) {
			$_SERVER['IP2LOCATION_IP_ADDRESS'] = $json->ipAddress;
			$_SERVER['IP2LOCATION_COUNTRY_SHORT'] = $json->countryCode;
			$_SERVER['IP2LOCATION_COUNTRY_LONG'] = $json->countryName;
			$_SERVER['IP2LOCATION_REGION'] = $json->regionName;
			$_SERVER['IP2LOCATION_CITY'] = $json->cityName;
			$_SERVER['IP2LOCATION_ISP'] = $json->isp;
			$_SERVER['IP2LOCATION_LATITUDE'] = $json->latitude;
			$_SERVER['IP2LOCATION_LONGITUDE'] = $json->longitude;
			$_SERVER['IP2LOCATION_DOMAIN'] = $json->domainName;
			$_SERVER['IP2LOCATION_ZIPCODE'] = $json->zipCode;
			$_SERVER['IP2LOCATION_TIMEZONE'] = $json->timeZone;
			$_SERVER['IP2LOCATION_NETSPEED'] = $json->netSpeed;
			$_SERVER['IP2LOCATION_IDDCODE'] = $json->iddCode;
			$_SERVER['IP2LOCATION_AREACODE'] = $json->areaCode;
			$_SERVER['IP2LOCATION_WEATHERSTATIONCODE'] = $json->weatherStationCode;
			$_SERVER['IP2LOCATION_WEATHERSTATIONNAME'] = $json->weatherStationName;
			$_SERVER['IP2LOCATION_MCC'] = $json->mcc;
			$_SERVER['IP2LOCATION_MNC'] = $json->mnc;
			$_SERVER['IP2LOCATION_MOBILEBRAND'] = $json->mobileCarrierName;
			$_SERVER['IP2LOCATION_ELEVATION'] = $json->elevation;
			$_SERVER['IP2LOCATION_USAGETYPE'] = $json->usageType;
		}
	}

	function get_variables() {
		if ( !session_id() ) {
			session_start();
		}

		$ipAddress = $_SERVER['REMOTE_ADDR'];

		if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
			$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		if ( isset( $_SESSION['ip2location_variables_' . $ipAddress ] ) ) {
			return $_SESSION['ip2location_variables_' . $ipAddress ];
		}

		return false;
	}

	function admin_options() {
		if ( !is_admin() ) {
			return;
		}

		// Include jQuery library.
		add_action( 'wp_enqueue_script', 'load_jquery' );

		// Find any .BIN files in current directory
		$files = scandir( IP2LOCATION_VARIABLES_ROOT );

		foreach( $files as $file ){
			if ( strtoupper( substr( $file, -4 ) ) == '.BIN' ){
				update_option( 'ip2location_variables_database', $file );
				break;
			}
		}

		$mode_status = '';

		$lookup_mode = ( isset( $_POST['lookupMode'] ) ) ? $_POST['lookupMode'] : get_option( 'ip2location_variables_lookup_mode' );
		$api_key = ( isset( $_POST['apiKey'] ) ) ? $_POST['apiKey'] : get_option( 'ip2location_variables_api_key' );

		if( isset( $_POST['lookupMode'] ) ) {
			update_option( 'ip2location_variables_lookup_mode', $lookup_mode );
			update_option( 'ip2location_variables_api_key', $api_key );

			$mode_status .= '
			<div id="message" class="updated">
				<p>Changes saved.</p>
			</div>';
		}

		echo '
		<style type="text/css">
			.red{color:#cc0000}
			.code{color:#003399;font-family:\'Courier New\'}
			pre{margin:0 0 20px 0;border:1px solid #c0c0c0;backgroumd:#e4e4e4;color:#535353;font-family:\'Courier New\';padding:8px}
			.result{margin:0 0 20px 0;border:1px solid #006699;backgroumd:#99ffcc;color:#000033;padding:8px}
		</style>

		<script>
			(function( $ ) {
				$(function(){
					$("#download").on("click", function(e){
						e.preventDefault();

						if ($("#productCode").val() == "" || $("#username").val() == "" || $("#password").val() == ""){
							return;
						}

						$("#download").attr("disabled", "disabled");
						$("#download-status").html(\'<div style="padding:10px; border:1px solid #ccc; background-color:#ffa;">Downloading \' + $("#productCode").val() + \' BIN database in progress... Please wait...</div>\');

						$.post(ajaxurl, { action: "update_ip2location_variables_database", productCode: $("#productCode").val(), username: $("#username").val(), password: $("#password").val() }, function(response) {
							if(response == "SUCCESS") {
								alert("Downloading completed.");

								$("#download-status").html(\'<div id="message" class="updated"><p>Successfully downloaded the \' + $("#productCode").val() + \' BIN database. Please refresh information by <a href="javascript:;" id="reload">reloading</a> the page.</p></div>\');

								$("#reload").on("click", function(){
									window.location = window.location.href.split("#")[0];
								});
							}
							else {
								alert("Downloading failed.");

								$("#download-status").html(\'<div id="message" class="error"><p><strong>ERROR</strong>: Failed to download \' + $("#productCode").val() + \' BIN database. Please make sure you correctly enter the product code and login crendential. Please also take note to download the BIN product code only.</p></div>\');
							}
						}).always(function() {
							$("#productCode").val("DB1LITEBIN");
							$("#username").val("");
							$("#password").val("");
							$("#download").removeAttr("disabled");
						});
					});

					$("#use-bin").on("click", function(){
						$("#bin-mode").show();
						$("#ws-mode").hide();

						$("html, body").animate({
							scrollTop: $("#use-bin").offset().top - 50
						}, 100);
					});

					$("#use-ws").on("click", function(){
						$("#bin-mode").hide();
						$("#ws-mode").show();

						$("html, body").animate({
							scrollTop: $("#use-ws").offset().top - 50
						}, 100);
					});

					$("#' . ( ( $lookup_mode == 'bin' ) ? 'bin-mode' : 'ws-mode' ) . '").show();
				});
			})( jQuery );
		</script>
		<div class="wrap">
			<h3>IP2Location Variables</h3>
			<p>
				IP2Location Variables provides a solution to easily get the visitor\'s location information based on IP address and customize the content display in ppages, plugin, or themes for different countries. This plugin uses IP2Location BIN file for location queries, therefore there is no need to set up any relational database to use it. Depending on the BIN file that you are using, this plugin is able to provide you the information of country, region or state, city, latitude and longitude, US ZIP code, time zone, Internet Service Provider (ISP) or company name, domain name, net speed, area code, weather station code, weather station name, mobile country code (MCC), mobile network code (MNC) and carrier brand, elevation and usage type of origin for an IP address.
			</p>

			<p>&nbsp;</p>

			<div style="border-bottom:1px solid #ccc;">
				<h3>Lookup Mode</h3>
			</div>

			' . $mode_status . '

			<form id="form-lookup-mode" method="post">
				<p>
					<label><input id="use-bin" type="radio" name="lookupMode" value="bin"' . ( ( $lookup_mode == 'bin' ) ? ' checked' : '' ) . '> Local BIN database</label>

					<div id="bin-mode" style="margin-left:50px;display:none;background:#d7d7d7;padding:20px">
						<p>
							BIN file download: <a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location Commercial database</a> | <a href="http://lite.ip2location.com/?r=wordpress" targe="_blank">IP2Location LITE database (free edition)</a>.
						</p>';

					if ( !file_exists( IP2LOCATION_VARIABLES_ROOT . get_option( 'ip2location_variables_database' ) ) ) {
						echo '
						<div id="message" class="error">
							<p>
								Unable to find the IP2Location BIN database! Please download the database at at <a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> | <a href="http://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>.
							</p>
						</div>';
					}
					else {
						echo '
						<p>
							<b>Current Database Version: </b>
							' . date( 'F Y', filemtime( IP2LOCATION_VARIABLES_ROOT . get_option( 'ip2location_variables_database' ) ) ) . '
						</p>';

						if ( filemtime( IP2LOCATION_VARIABLES_ROOT . get_option( 'ip2location_variables_database' ) ) < strtotime( '-2 months' ) ) {
							echo '
							<div style="background:#fff;padding:2px 10px;border-left:3px solid #cc0000">
								<p>
									<strong>REMINDER</strong>: Your IP2Location database was outdated. Please download the latest version for accurate result.
								</p>
							</div>';
						}
					}

					echo '
						<p>&nbsp;</p>

						<div style="border-bottom:1px solid #ccc;">
							<h4>Download BIN Database</h4>
						</div>

						<div id="download-status" style="margin:10px 0;"></div>

						<strong>Product Code</strong>:
						<select id="productCode" type="text" value="" style="margin-right:10px;" >
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

						<strong>Email</strong>:
						<input id="username" type="text" value="" style="margin-right:10px;" />

						<strong>Password</strong>:
						<input id="password" type="password" value="" style="margin-right:10px;" />

						<button id="download" class="button action">Download</button>

						<span style="display:block; font-size:0.8em">Enter the product code, i.e, DB1LITEBIN, (the code in square bracket on your license page) and login credential for the download.</span>

						<div style="margin-top:20px;">
							<strong>Note</strong>: If you failed to download the BIN database using this automated downloading tool, please follow the below procedures to manually update the database.
							<ol style="list-style-type:circle;margin-left:30px">
								<li>Download the BIN database at <a href="http://www.ip2location.com/?r=wordpress" target="_blank">IP2Location commercial database</a> | <a href="http://lite.ip2location.com/?r=wordpress" target="_blank">IP2Location LITE database (free edition)</a>.</li>
								<li>Decompress the zip file and update the BIN database to ' . dirname( __FILE__ ) . '.</li>
								<li>Once completed, please refresh the information by reloading the setting page.</li>
							</ol>
						</div>
						<p>&nbsp;</p>
					</div>
				</p>
				<p>
					<label><input id="use-ws" type="radio" name="lookupMode" value="ws"' . ( ( $lookup_mode == 'ws' ) ? ' checked' : '' ) . '> IP2Location Web Service</label>

					<div id="ws-mode" style="margin-left:50px;display:none;background:#d7d7d7;padding:20px">
						<p>Please insert your IP2Location <a href="http://www.ip2location.com/web-service" target="_blank">Web service</a> API key.</p>
						<p>
							<strong>API Key</strong>:
							<input name="apiKey" type="text" value="' . $api_key . '" style="margin-right:10px;" />
						</p>
					</div>
				</p>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  />
				</p>
			</form>

			<p>&nbsp;</p>

			<div style="border-bottom:1px solid #ccc;">
				<h3 id="ip-lookup">Query IP</h3>
			</div>
			<p>
				Enter a valid IP address for checking.
			</p>';

		$ipAddress = ( isset( $_POST['ipAddress'] ) ) ? $_POST['ipAddress'] : '';

		if ( isset( $_POST['lookup'] ) ) {
			if ( !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
				echo '
				<div id="message" class="error">
					<p><strong>ERROR</strong>: Invalid IP address.</p>
				</div>';
			}
			else {
				$response = $this->get_location( $ipAddress );

				if ( $response['countryName'] ) {
					if ( $response['countryCode'] != '??' && strlen( $response['countryCode'] ) == 2 ) {
						echo '
						<div id="message" class="updated">
							<p>IP address <strong>' . $ipAddress . '</strong> belongs to <strong>' . $response['countryName'] . '</strong>.</p>
						</div>';
					}
					else{
						echo '
						<div id="message" class="error">
							<p><strong>ERROR</strong>: ' . $response['countryName'] . '</p>
						</div>';
					}
				}
				else{
					echo '
					<div id="message" class="error">
						<p><strong>ERROR</strong>: This record is not supported with this databaase.</p>
					</div>';
				}
			}
		}

		echo '
			<form action="#ip-lookup" method="post">
				<p>
					<label><b>IP Address: </b></label>
					<input type="text" name="ipAddress" value="' . $ipAddress . '" />
					<input type="submit" name="lookup" value="Lookup" class="button action" />
				</p>
			</form>

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

	function get_location( $ip ) {
		switch( get_option( 'ip2location_variables_lookup_mode' ) ) {
			case 'bin':
				// Make sure IP2Location database is exist.
				if ( !is_file( IP2LOCATION_VARIABLES_ROOT . get_option( 'ip2location_variables_database' ) ) ) {
					return false;
				}

				if ( ! class_exists( 'IP2LocationRecord' ) && ! class_exists( 'IP2Location' ) ) {
					require_once( IP2LOCATION_VARIABLES_ROOT . 'ip2location.class.php' );
				}

				// Create IP2Location object.
				$geo = new IP2Location( IP2LOCATION_VARIABLES_ROOT . get_option( 'ip2location_variables_database' ) );

				// Get geolocation by IP address.
				$response = $geo->lookup( $ip );

				return array(
					'ipAddress' => $ip,
					'countryCode' => $response->countryCode,
					'countryName' => $response->countryName,
					'regionName' => $response->regionName,
					'cityName' => $response->cityName,
					'latitude' => $response->latitude,
					'longitude' => $response->longitude,
					'isp'=> $response->isp,
					'domainName' => $response->domainName,
					'zipCode' => $response->zipCode,
					'timeZone' => $response->timeZone,
					'netSpeed' => $response->netSpeed,
					'iddCode' => $response->iddCode,
					'areaCode' => $response->areaCode,
					'weatherStationCode' => $response->weatherStationCode,
					'weatherStationName' =>$response->weatherStationName,
					'mcc' => $response->mcc,
					'mnc' => $response->mnc,
					'mobileCarrierName' => $response->mobileCarrierName,
					'elevation' => $response->elevation,
					'usageType' => $response->usageType,
				);
			break;

			case 'ws':
				if ( !class_exists( 'WP_Http' ) ) {
					include_once( ABSPATH . WPINC . '/class-http.php' );
				}

				$request = new WP_Http();
				$response = $request->request( 'http://api.ip2location.com/?' . http_build_query( array(
					'key' => get_option( 'ip2location_variables_api_key' ),
					'ip' => $ip,
					'package' => 'WS10',
					'format' => 'json',
				) ) , array( 'timeout' => 3 ) );

				if ( is_null( $json = json_decode( $response['body'] ) ) === FALSE ) {
					return array(
						'ipAddress' => $ip,
						'countryCode' => $json->country_code,
						'countryName' => $json->country_name,
						'regionName' => $json->region_name,
						'cityName' => $json->city_name,
						'latitude' => $json->latitude,
						'longitude' => $json->longitude,
						'isp'=> $json->isp,
						'domainName' => $json->domain,
						'zipCode' => $json->zip_code,
						'timeZone' => '-',
						'netSpeed' => '-',
						'iddCode' => '-',
						'areaCode' => '-',
						'weatherStationCode' => '-',
						'weatherStationName' => '-',
						'mcc' => '-',
						'mnc' => '-',
						'mobileCarrierName' => '-',
						'elevation' => '-',
						'usageType' => '-',
					);
				}
			break;
		}
	}

	function download() {
		try {
			$productCode = ( isset( $_POST['productCode'] ) ) ? $_POST['productCode'] : '';
			$username = ( isset( $_POST['username'] ) ) ? $_POST['username'] : '';
			$password = ( isset( $_POST['password'] ) ) ? $_POST['password']: '';

			if ( !class_exists( 'WP_Http' ) ) {
				include_once( ABSPATH . WPINC . '/class-http.php' );
			}

			// Remove existing database.zip.
			if ( file_exists( IP2LOCATION_VARIABLES_ROOT . 'database.zip' ) ) {
				@unlink( IP2LOCATION_VARIABLES_ROOT . 'database.zip' );
			}

			// Start downloading BIN database from IP2Location website.
			$request = new WP_Http();
			$response = $request->request( 'http://www.ip2location.com/download?' . http_build_query( array(
				'productcode' => $productCode,
				'login' => $username,
				'password' => $password,
			) ) , array( 'timeout' => 120 ) );

			if ( ( isset( $response->errors ) ) || ( !( in_array( '200', $response['response'] ) ) ) ) {
				die( 'Connection error.' );
			}

			// Save downloaded package into plugin directory.
			$fp = fopen( IP2LOCATION_VARIABLES_ROOT . 'database.zip', 'w' );

			fwrite( $fp, $response['body'] );
			fclose( $fp );

			// Decompress the package.
			$zip = zip_open( IP2LOCATION_VARIABLES_ROOT . 'database.zip' );

			if ( !is_resource( $zip ) ) {
				die( 'Downloaded file is corrupted.' );
			}

			while( $entries = zip_read( $zip ) ) {
				// Extract the BIN file only.
				$file_name = zip_entry_name($entries);

				if ( substr( $file_name, -4 ) != '.BIN' ) {
					continue;
				}

				// Remove existing BIN files before extrac the latest BIN file.
				$files = scandir( IP2LOCATION_VARIABLES_ROOT );

				foreach( $files as $file ){
					if ( substr( $file, -4 ) == '.bin' || substr( $file, -4 ) == '.BIN' ){
						@unlink( IP2LOCATION_VARIABLES_ROOT . $file );
					}
				}

				$handle = fopen( IP2LOCATION_VARIABLES_ROOT . $file_name, 'w+' );
				fwrite( $handle, zip_entry_read( $entries, zip_entry_filesize($entries) ) );
				fclose( $handle );

				if ( !file_exists( IP2LOCATION_VARIABLES_ROOT . $file_name ) ) {
					die( 'ERROR' );
				}

				@unlink( IP2LOCATION_VARIABLES_ROOT . 'database.zip' );

				die( 'SUCCESS' );
			}
		}
		catch( Exception $e ) {
			die( 'ERROR' );
		}

		die( 'ERROR' );
	}

	function get_country_name( $code ) {
		$countries = array( 'AF' => 'Afghanistan','AL' => 'Albania','DZ' => 'Algeria','AS' => 'American Samoa','AD' => 'Andorra','AO' => 'Angola','AI' => 'Anguilla','AQ' => 'Antarctica','AG' => 'Antigua and Barbuda','AR' => 'Argentina','AM' => 'Armenia','AW' => 'Aruba','AU' => 'Australia','AT' => 'Austria','AZ' => 'Azerbaijan','BS' => 'Bahamas','BH' => 'Bahrain','BD' => 'Bangladesh','BB' => 'Barbados','BY' => 'Belarus','BE' => 'Belgium','BZ' => 'Belize','BJ' => 'Benin','BM' => 'Bermuda','BT' => 'Bhutan','BO' => 'Bolivia','BA' => 'Bosnia and Herzegovina','BW' => 'Botswana','BV' => 'Bouvet Island','BR' => 'Brazil','IO' => 'British Indian Ocean Territory','BN' => 'Brunei Darussalam','BG' => 'Bulgaria','BF' => 'Burkina Faso','BI' => 'Burundi','KH' => 'Cambodia','CM' => 'Cameroon','CA' => 'Canada','CV' => 'Cape Verde','KY' => 'Cayman Islands','CF' => 'Central African Republic','TD' => 'Chad','CL' => 'Chile','CN' => 'China','CX' => 'Christmas Island','CC' => 'Cocos (Keeling) Islands','CO' => 'Colombia','KM' => 'Comoros','CG' => 'Congo','CK' => 'Cook Islands','CR' => 'Costa Rica','CI' => 'Cote D\'Ivoire','HR' => 'Croatia','CU' => 'Cuba','CY' => 'Cyprus','CZ' => 'Czech Republic','CD' => 'Democratic Republic of Congo','DK' => 'Denmark','DJ' => 'Djibouti','DM' => 'Dominica','DO' => 'Dominican Republic','TP' => 'East Timor','EC' => 'Ecuador','EG' => 'Egypt','SV' => 'El Salvador','GQ' => 'Equatorial Guinea','ER' => 'Eritrea','EE' => 'Estonia','ET' => 'Ethiopia','FK' => 'Falkland Islands (Malvinas)','FO' => 'Faroe Islands','FJ' => 'Fiji','FI' => 'Finland','FR' => 'France','FX' => 'France, Metropolitan','GF' => 'French Guiana','PF' => 'French Polynesia','TF' => 'French Southern Territories','GA' => 'Gabon','GM' => 'Gambia','GE' => 'Georgia','DE' => 'Germany','GH' => 'Ghana','GI' => 'Gibraltar','GR' => 'Greece','GL' => 'Greenland','GD' => 'Grenada','GP' => 'Guadeloupe','GU' => 'Guam','GT' => 'Guatemala','GN' => 'Guinea','GW' => 'Guinea-bissau','GY' => 'Guyana','HT' => 'Haiti','HM' => 'Heard and Mc Donald Islands','HN' => 'Honduras','HK' => 'Hong Kong','HU' => 'Hungary','IS' => 'Iceland','IN' => 'India','ID' => 'Indonesia','IR' => 'Iran (Islamic Republic of)','IQ' => 'Iraq','IE' => 'Ireland','IL' => 'Israel','IT' => 'Italy','JM' => 'Jamaica','JP' => 'Japan','JO' => 'Jordan','KZ' => 'Kazakhstan','KE' => 'Kenya','KI' => 'Kiribati','KR' => 'Korea, Republic of','KW' => 'Kuwait','KG' => 'Kyrgyzstan','LA' => 'Lao People\'s Democratic Republic','LV' => 'Latvia','LB' => 'Lebanon','LS' => 'Lesotho','LR' => 'Liberia','LY' => 'Libyan Arab Jamahiriya','LI' => 'Liechtenstein','LT' => 'Lithuania','LU' => 'Luxembourg','MO' => 'Macau','MK' => 'Macedonia','MG' => 'Madagascar','MW' => 'Malawi','MY' => 'Malaysia','MV' => 'Maldives','ML' => 'Mali','MT' => 'Malta','MH' => 'Marshall Islands','MQ' => 'Martinique','MR' => 'Mauritania','MU' => 'Mauritius','YT' => 'Mayotte','MX' => 'Mexico','FM' => 'Micronesia, Federated States of','MD' => 'Moldova, Republic of','MC' => 'Monaco','MN' => 'Mongolia','MS' => 'Montserrat','MA' => 'Morocco','MZ' => 'Mozambique','MM' => 'Myanmar','NA' => 'Namibia','NR' => 'Nauru','NP' => 'Nepal','NL' => 'Netherlands','AN' => 'Netherlands Antilles','NC' => 'New Caledonia','NZ' => 'New Zealand','NI' => 'Nicaragua','NE' => 'Niger','NG' => 'Nigeria','NU' => 'Niue','NF' => 'Norfolk Island','KP' => 'North Korea','MP' => 'Northern Mariana Islands','NO' => 'Norway','OM' => 'Oman','PK' => 'Pakistan','PW' => 'Palau','PA' => 'Panama','PG' => 'Papua New Guinea','PY' => 'Paraguay','PE' => 'Peru','PH' => 'Philippines','PN' => 'Pitcairn','PL' => 'Poland','PT' => 'Portugal','PR' => 'Puerto Rico','QA' => 'Qatar','RE' => 'Reunion','RO' => 'Romania','RU' => 'Russian Federation','RW' => 'Rwanda','KN' => 'Saint Kitts and Nevis','LC' => 'Saint Lucia','VC' => 'Saint Vincent and the Grenadines','WS' => 'Samoa','SM' => 'San Marino','ST' => 'Sao Tome and Principe','SA' => 'Saudi Arabia','SN' => 'Senegal','SC' => 'Seychelles','SL' => 'Sierra Leone','SG' => 'Singapore','SK' => 'Slovak Republic','SI' => 'Slovenia','SB' => 'Solomon Islands','SO' => 'Somalia','ZA' => 'South Africa','GS' => 'South Georgia And The South Sandwich Islands','ES' => 'Spain','LK' => 'Sri Lanka','SH' => 'St. Helena','PM' => 'St. Pierre and Miquelon','SD' => 'Sudan','SR' => 'Suriname','SJ' => 'Svalbard and Jan Mayen Islands','SZ' => 'Swaziland','SE' => 'Sweden','CH' => 'Switzerland','SY' => 'Syrian Arab Republic','TW' => 'Taiwan','TJ' => 'Tajikistan','TZ' => 'Tanzania, United Republic of','TH' => 'Thailand','TG' => 'Togo','TK' => 'Tokelau','TO' => 'Tonga','TT' => 'Trinidad and Tobago','TN' => 'Tunisia','TR' => 'Turkey','TM' => 'Turkmenistan','TC' => 'Turks and Caicos Islands','TV' => 'Tuvalu','UG' => 'Uganda','UA' => 'Ukraine','AE' => 'United Arab Emirates','GB' => 'United Kingdom','US' => 'United States','UM' => 'United States Minor Outlying Islands','UY' => 'Uruguay','UZ' => 'Uzbekistan','VU' => 'Vanuatu','VA' => 'Vatican City State (Holy See)','VE' => 'Venezuela','VN' => 'Viet Nam','VG' => 'Virgin Islands (British)','VI' => 'Virgin Islands (U.S.)','WF' => 'Wallis and Futuna Islands','EH' => 'Western Sahara','YE' => 'Yemen','YU' => 'Yugoslavia','ZM' => 'Zambia','ZW' => 'Zimbabwe' );

		return ( isset( $countries[$code] ) ) ? $countries[$code] : '';
	}
}

// For legacy support
function ip2location_get_vars() {
	global $ip2location_variables;

	return $ip2location_variables->get_variables();
}

// Initial class
$ip2location_variables = new IP2LocationVariables();
$ip2location_variables->init();

register_activation_hook( __FILE__, array( $ip2location_variables, 'set_defaults' ) );
register_uninstall_hook( __FILE__, array( $ip2location_variables, 'uninstall' ) );

add_action( 'wp_ajax_update_ip2location_variables_database', array( $ip2location_variables, 'download' ) );
add_action( 'wp', array( $ip2location_variables, 'add_variables' ) );
?>