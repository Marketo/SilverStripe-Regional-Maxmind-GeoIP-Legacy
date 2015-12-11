## Maintainer Contact

Kirk Mayo

<kirk (dot) mayo (at) solnet (dot) co (dot) nz>

## Requirements

* SilverStripe 3.2
* SilverStripe-Regional Module

# SilverStripe-Regional-Maxmind-GeoIP-Legacy

Driver allowing the SilverStripe-Regional module to pull data from Maxmind using GeoIP


## Composer Installation

  composer require marketo/silverstripe-regional-maxmind-geoip-legacy

## Config

You will need to set the location of the database files if they are in the default locations.
This can be done by adding a couple of lines to the config.yml file as per the example below.

```
IPInfoCache:
  GeoPathCity: '/usr/share/GeoIP/GeoIPCity.dat'
  GeoPathISP: '/usr/share/GeoIP/GeoIPISP.dat'
```

## GeoIP database

You will neeed to retrive a databse for the module to work with this will need to be stored
on the server and you may need to set the location of GeoPath under IPInfoCache in your config yml file.
The free databases can be downloaded from here <http://dev.maxmind.com/geoip/legacy/geolite>

## API endpoints

The curent endpoint returns a JSON object giving location details for the IP address.
The results default to json but they can also be returned as jsonp if this has been defined under
the config for IPInfoCache

```
http://YOURSITE/geoip/IPADDRESS
http://YOURSITE/geoip/IPADDRESS.json
http://YOURSITE/geoip/IPADDRESS.jsonp
```

## TODO

Add tests
Split up conection methods make it easy to use other connectors and dbs
