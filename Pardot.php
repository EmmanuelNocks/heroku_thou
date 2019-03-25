<?php
require_once dirname(__FILE__).'/config.php';

class Pardot{
    
    private $api_key;
    function __construct(){
        global $config;
        $data = array(
            'email' =>  $config["pardot"]["email"],
            'password' =>$config["pardot"]["password"],
            'user_key' => $config["pardot"]["user_key"],
            'format'=>'json'
        );
        $this->api_key = $this->ApiCall($config["pardot"]["baseEndpoint"].'login/version/4',$data,'POST');
        $this->api_key = $this->api_key['res']->api_key;
    }

   public function ApiCall($url, $data, $method = 'GET')
    {
        // build out the full url, with the query string attached.
        $queryString = http_build_query($data, null, '&');
        
        if (strpos($url, '?') !== false) {
            $url = $url . '&' . $queryString;
        } else {
            $url = $url . '?' . $queryString;
        }
    
        $curl_handle = curl_init($url);
    
        // wait 5 seconds to connect to the Pardot API, and 30
        // total seconds for everything to complete
        // curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
        // curl_setopt($curl_handle, CURLOPT_TIMEOUT, 30);
    
        // https only, please!
        curl_setopt($curl_handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    
        // ALWAYS verify SSL - this should NEVER be changed. 2 = strict verify
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
    
        // return the result from the server as the return value of curl_exec instead of echoing it
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    
        if (strcasecmp($method, 'POST') === 0) {
            curl_setopt($curl_handle, CURLOPT_POST, true);
        } elseif (strcasecmp($method, 'GET') !== 0) {
            // perhaps a DELETE?
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        }
    
        $pardotApiResponse = curl_exec($curl_handle);
        if ($pardotApiResponse === false) {
            // failure - a timeout or other problem. depending on how you want to handle failures,
            // you may want to modify this code. Some folks might throw an exception here. Some might
            // log the error. May you want to return a value that signifies an error. The choice is yours!
    
            // let's see what went wrong -- first look at curl
            $humanReadableError = curl_error($curl_handle);
    
            // you can also get the HTTP response code
            $httpResponseCode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    
            // make sure to close your handle before you bug out!
            curl_close($curl_handle);
    
            throw new Exception("Unable to successfully complete Pardot API call to $url -- curl error: \"".
                                    "$humanReadableError\", HTTP response code was: $httpResponseCode");
        }
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        // make sure to close your handle before you bug out!
        curl_close($curl_handle);
    
        return  array('code'=>$httpcode,"res"=>json_decode($pardotApiResponse,false));
    }
    
    public function getApiKey(){
        
        return $this->api_key;
    }

    

    public function runUpdate($pID,$data){
      global $config;
      return $this->ApiCall($config["pardot"]["baseEndpoint"].'prospect/version/4/do/update/id/'.$pID,$data,'GET');
    }
    public function queryByDateTime($data){
        global $config;
        return $this->ApiCall($config["pardot"]["baseEndpoint"].'prospect/version/4/do/query',$data,'GET');
      }
}
// $pardot = new Pardot();
// $data = array(
  
//     'Clearbit_HQ_City'=>'PE',
//     'api_key' =>$pardot->getApiKey(),
//     'user_key' => $config["pardot"]["user_key"],
//     'format'=>'json'
// );
// $r= $pardot->runUpdate(4605904,$data);

// print_r($r);