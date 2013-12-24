=== IP2Location Variables ===
Contributors: IP2Location
Donate link: http://www.ip2location.com
Tags: targeted content, geolocation
Requires at least: 2.0
Tested up to: 3.8
Stable tag: 1.0

Description: Easily get/display the visitor's location information based on IP address and customize the content display for different countries in pages, plugins, or themes. This plugin uses IP2Location BIN file for location queries, therefore there is no need to set up any relational database to use it.

== Description ==

IP2Location Variables provides a solution to easily get the visitor's location information based on IP address and customize the content display for different countries in pages, plugins, or themes. This plugin uses IP2Location BIN file for location queries, therefore there is no need to set up any relational database to use it. Depending on the BIN file that you are using, this plugin is able to provide you the information of country, region or state, city, latitude and longitude, US ZIP code, time zone, Internet Service Provider (ISP) or company name, domain name, net speed, area code, weather station code, weather station name, mobile country code (MCC), mobile network code (MNC) and carrier brand, elevation and usage type of origin for an IP address.

BIN file download: [IP2Location Commercial database](http://ip2location.com "IP2Location commercial database") | [IP2Location LITE database (free edition)](http://lite.ip2location.com "IP2Location LITE database (free edition)")

= Usage =

Call the function ip2location_get_vars() in any pages, plugins, or themes to retrieve IP2Location variables. The variables are returned in object.

= More Information =
Please visit us at [http://www.ip2location.com](http://www.ip2location.com/tutorials/wordpress-ip2location-variables "http://www.ip2location.com")

== Installation ==

1. Upload `ip2location` folder to the `/wp-content/plugins/` directory.
1. Upload `database.bin` to `/wp-content/ip2location/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. You can now start using IP2Location Variables to customize your contents.