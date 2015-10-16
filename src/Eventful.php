<?PHP

namespace realdark\eventful_web_api;

use tmhOAuth;
use HTTP_Request2;
use Exception;
use SimpleXMLElement;

// +-----------------------------------------------------------------------+
// | Copyright 2013 Eventful, Inc.                                             |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Authors: Chris Radcliff <chris@eventful.com>                          |
// |          Chuck Norris   <chuck@eventful.com>                          |
// |          David Reiter   <dreiter@eventful.com>                        |
// +-----------------------------------------------------------------------+
//

/**
 * Services_Eventful
 *
 * Client for the REST-based Web service at https://api.eventful.com
 *
 * Eventful is the world's largest collection of events, taking place in 
 * local markets throughout the world, from concerts and sports to singles
 * events and political rallies.
 * 
 * Eventful.com is built upon a unique, open platform that enables partners
 * and web applications to leverage Eventful's data, features and functionality
 * via the Eventful API or regular data feeds. 
 *
 * Services_Eventful allows you to
 * - search for Eventful items (events, venues, performers, demands, etc.)
 * - create, modify, or delete Eventful items
 * - get details for any Eventful item 
 * from PHP (5 or greater).
 * 
 * See https://api.eventful.com for a complete list of available methods.
 *
 * @author		David Reiter<dreiter@eventful.com>
 * @package		Services_Eventful
 * @version		0.9.3
 */
class Services_Eventful
{
   /**
    * URI of the REST API
    *
    * @access  public
    * @var     string
    */
    public $api_root;
    public $debug   = 0;
    public $req_url = 'https://eventful.com/oauth/request_token';
    public $authurl = 'https://eventful.com/oauth/authorize';
    public $acc_url = 'https://eventful.com/oauth/access_token';

    public $using_oauth = 0;
    public $conskey ;
    public $conssec ;
    public $oauth_token;
    public $oauth_token_secret;
        
   /**
    * Application key (as provided by https://api.eventful.com)
    *
    * @access  public
    * @var     string
    */
    public $app_key   = null;

   /**
    * Latest request URI
    *
    * @access  private
    * @var     string
    */
    private $_request_uri = null;
        
   /**
    * Latest response as unserialized data
    *
    * @access  public
    * @var     string
    */
    public $_response_data = null;
    
   /**
    * Create a new client
    *
    * @access  public
    * @param   string      app_key
    */
    function __construct($app_key, $api_url = 'https://api.eventful.com' )
    {
        $this->app_key  = $app_key;
        $this->api_root = $api_url;
    }
    
   /**
    * Setup OAuth so we can pass correct OAuth headers
    *
    * @access  public
    * @param   string      conskey
    * @param   string      conssec
    * @param   string      token
    * @param   string      token_secret
    */
    function setup_Oauth($conskey, $conssec, $oauth_token, $oauth_secret )
    {
    	$this->conskey     = $conskey;
    	$this->conssec     = $conssec;
    	$this->oauth_token = $oauth_token;
    	$this->oauth_token_secret = $oauth_secret;
    	$this->using_oauth = 1;
    	return 1;
    }

   /**
    * Turn on debug so we can trace some of the calls made to the API
    *
    * @access  public
    */
    function set_debug()
    {
    	$this->debug  = 1;
    	return 1;
    }
    
    
   /**
    * Call a method on the Eventful API.
    *
    * @access  public
    * @param   string      arguments
    */
    function call($method, $args=array(), $type='rest') 
    {
        /* Methods may or may not have a leading slash.  */
        $method = trim($method,'/ ');

        /* Construct the URL that corresponds to the method.  */
        $url = $this->api_root . '/' . $type  . '/' . $method;
        $this->_request_uri = $url;

        // Handle the OAuth request.
        if ($this->using_oauth ) {
          //create a new Oauth request.  By default this uses the HTTP AUTHORIZATION headers and HMACSHA1 signature 
          if ($this->debug) {
            echo "Checking on this consumer key/secret $this->conskey / $this->conssec <br>\n";
            echo "Checking on this token key/secret $this->oauth_token / $this->oauth_token_secret <br>\n";
            echo "Using the app_key $this->app_key <br>\n";
          }
          $config = array(
            'consumer_key'    => $this->conskey,
            'consumer_secret' => $this->conssec,
            'token'           => $this->oauth_token,
            'secret'          => $this->oauth_token_secret,
            'method'          => 'POST',
            'use_ssl'         => false,
            'user_agent'      => 'Eventful_PHP_API');
          $tmhOAuth = new tmhOauth($config);
          $multipart = false;
          $app_key_name = 'app_key';
          foreach ($args as $key => $value) {
            if ( preg_match('/_file$/', $key) ) {  // Check for file_upload
                $multipart = true;
                $app_key_name = 'oauth_app_key';  // Have to store the app_key in oauth_app_key so it gets sent over in the Authorization header
            }
          }

          $code = $tmhOAuth->user_request(array(
           'method' => 'POST',
           'url' => $tmhOAuth->url($url,''),
           'params' => array_merge( array($app_key_name => $this->{app_key}), $args),
           'multipart' => $multipart));
          if ($code == 200) {
            $resp = $tmhOAuth->response['response'];
            $this->_response_data = $resp;
            if ($type ===  "json") {
              $data = json_decode($resp, true);
              if ($data[error] > 0) {
                 throw new Exception('Invalid status : ' . $data[status]  . ' (' . $data[description] . ')');
              }
            } else {
              $data = new SimpleXMLElement($resp);
              if ($data->getName() === 'error') {
                $error = $data['string'] . ": " . $data->description;
                $code = $data['string'];
                throw new Exception($error);
              }
            }
            return ($data);
          } else { // Non 200 response code.
            throw new Exception('Invalid Response Code: ' . $code);
          }
       }


  // No OAuth just do a simple request

   $req = new HTTP_Request2($url);
   /* $req = new HTTP_Request2('https://api.eventful.com/rest/events/get'); */
   $req->setMethod(HTTP_Request2::METHOD_POST);
        
   /* Add each argument to the POST body.  */

   $req->addPostParameter( 'app_key' , $this->app_key );
   foreach ($args as $key => $value) {
      if ( preg_match('/_file$/', $key) ) {
          // Treat file parameters differently.
          $req->addUpload($key, $value);
      } elseif ( is_array($value) ) {
         foreach ($value as $instance) {
            $req->addPostParameter($key, $instance);
         }
       } else {
         $req->addPostParameter($key, $value);
      }
    }
            
    /* Send the request and handle basic HTTP errors.  */
    $response = $req->send();
    //echo " we got this status => " .  $response->getReasonPhrase() . $response->getStatus() ."\n";
    if ($response->getStatus() !== 200) {
        throw new Exception('Invalid Response Code: ' . $response->getReasonPhrase(), $response->getStatus());
    }
        
    /* Process the response XML through SimpleXML */
    $resp_data = $response->getBody();
    $this->_response_data = $resp_data;
    $data = new SimpleXMLElement($resp_data);
    
    /* Check for call-specific error messages */
        if ($data->getName() === 'error') 
        {
            $error = $data['string'] . ": " . $data->description;
            $code = $data['string'];
            throw new Exception($error);
        }
    
        return($data);
    }
}
?>
