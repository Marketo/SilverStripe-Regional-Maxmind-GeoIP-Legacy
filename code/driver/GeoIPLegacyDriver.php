<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A cache of geo data for a IP address
 */

class GeoIPLegacyDriver
{
    public $json;

    public static $statuses = array(
        'SUCCESS' => 'Success',
        'SUCCESS_CACHED' => 'Successfully found and cached response',
        'IP_ADDRESS_INVALID' => 'You have not supplied a valid IPv4 or IPv6 address',
        'IP_ADDRESS_RESERVED' => 'You have supplied an IP address which belongs to a reserved or private range',
        'IP_ADDRESS_NOT_FOUND' => 'The supplied IP address is not in the database',
        'DOMAIN_REGISTRATION_REQUIRED' => 'The domain of your site is not registered.',
        'DOMAIN_REGISTRATION_REQUIRED' => 'The domain of your site is not registered.',
        'GEOIP_EXCEPTION' => 'GEOIP_EXCEPTION [ERROR]',
        'GEOIP_MISSING' => 'GeoIP module does not exist'
    );

    public static $privateAddresses = array(
        '10.0.0.0|10.255.255.255',
        '172.16.0.0|172.31.255.255',
        '192.168.0.0|192.168.255.255',
        '169.254.0.0|169.254.255.255',
        '127.0.0.0|127.255.255.255'
    );

    public static function getStatuses($code = null)
    {
        if ($code && isset(self::$statuses[$code])) {
            return self::$statuses[$code];
        }
        return self::$statuses;
    }

    public function processIP($ip)
    {
        $status = null;

        $request['ip'] = $ip;
        $request['type'] = self::ipVersion($ip);
        if ($request['type'] == 'IPv4') {
            $isPrivate = self::isPrivateIP($ip);
            if ($isPrivate) {
                $status = self::setStatus('IP_ADDRESS_RESERVED', null, $status);
            }
            $record = geoip_record_by_name($ip);
        }

        $countryCode = null;
        if ($record && is_array($record)) {
            try {
                // fetch continent by continent_code
                $continents = Config::inst()->get(
                    'IPInfoCache',
                    'Continents'
                );
                if (array_key_exists($record['continent_code'], $continents)) {
                    $result['location']['continent_code'] = $record['continent_code'];
                }
                if (isset($record['continent_code']) && isset($continents[$record['continent_code']])) {
                    $result['location']['continent_names']['en'] = $continents[$record['continent_code']];
                }

                $countryCode = $record['country_code'];
                $result['location']['country_code'] = $countryCode;
                $result['location']['country_names']['en'] = $record['country_name'];

                $result['location']['postal_code'] = $record['postal_code'];
                $result['location']['city_names']['en'] = $record['city'];

                $result['location']['latitude'] = $record['latitude'];
                $result['location']['longitude'] = $record['longitude'];
                $result['location']['time_zone'] =
                    geoip_time_zone_by_country_and_region($record['country_code'], $record['region']);
            } catch (Exception $e) {
                $status = self::setStatus('GEOIP_EXCEPTION', $e, $status);
            }
        }

        // fetch ISP details
        if ($request['type'] == 'IPv4') {
            $record = geoip_isp_by_name($ip);
        }
        if ($record) {
            $result['organization']['name'] = $record;
            $result['organization']['isp'] = $record;
        }
        

        if ($status) {
            $statusArray['code'] = self::setStatus(null, null, $status);
            $statusArray['message'] = self::getStatusMessage($status);
            // do not cache a failure
            $this->json = json_encode(array(
                'request' => $request,
                'status' => $statusArray,
                'result' => array('maxmind-geoip-legacy' => $result)
            ));
            return null;
        } else {
            // return cached success message
            $statusArray['code'] = self::setStatus('SUCCESS_CACHED', null, $status);
            $statusArray['message'] = self::getStatusMessage($status);
            $this->json = json_encode(array(
                'request' => $request,
                'status' => $statusArray,
                'result' => array('maxmind-geoip-legacy' => $result)
            ));
        }

        // we write a different json object with a cached status to the DB
        $statusArray['code'] = self::setStatus('SUCCESS', null);
        $statusArray['message'] = self::getStatusMessage($statusArray['code']);
        $dbJson = json_encode(array(
            'request' => $request,
            'status' => $statusArray,
            //'result' => $result
            'result' => array('maxmind-geoip-legacy' => $result)
        ));

        return $dbJson;
    }

    public static function setStatus($code, $e, $status = null)
    {
        if ($status) {
            return $status;
        }
        if ($code == 'GEOIP_EXCEPTION' && $e && $e instanceof Exception) {
            self::$statuses['GEOIP_EXCEPTION'] = str_replace(
                'ERROR',
                $e->getMessage(),
                self::$statuses['GEOIP_EXCEPTION']
            );
        }
        return $code;
    }

    public static function getStatusMessage($status)
    {
        if (!$status) {
            $status = 'SUCCESS_CACHED';
        }
        return self::$statuses[$status];
    }

    public function getDetails()
    {
        return $this->Info;
    }

    public function getJSON()
    {
        return $this->json;
    }

    public function clearIPCache()
    {
        $this->write(false, false, true);
    }

    public static function ipVersion($ip = null)
    {
        return (strpos($ip, ':') === false) ? 'IPv4' : 'IPv6';
    }

    public static function isPrivateIP($ip)
    {
        $longIP = ip2long($ip);
        if ($longIP != -1) {
            foreach (self::$privateAddresses as $privateAddress) {
                list($start, $end) = explode('|', $privateAddress);
                if ($longIP >= ip2long($start) && $longIP <= ip2long($end)) {
                    return (true);
                }
            }
        }
        return false;
    }
}
