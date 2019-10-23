<?

/**
 * Very simple oauth 1.0A client implementation
 */

$CONSUMER_KEY = '';
$CONSUMER_SECRET = '';
$CREDENTIALS_CACHE = '/tmp/flickr_oauth';

// example for flickr
$ENDPOINT_TOKEN_REQUEST = 'https://www.flickr.com/services/oauth/request_token';
$ENDPOINT_ACCESS_TOKEN = 'https://www.flickr.com/services/oauth/access_token';
$ENDPOINT_AUTHORIZATION = 'https://www.flickr.com/services/oauth/authorize';

// // example for twitter
// $ENDPOINT_TOKEN_REQUEST = 'https://api.twitter.com/oauth/request_token';
// $ENDPOINT_ACCESS_TOKEN = 'https://api.twitter.com/oauth/access_token';
// $ENDPOINT_AUTHORIZATION = 'https://api.twitter.com/oauth/authorize';

if (file_exists($CREDENTIALS_CACHE)) {
    // if we have cached credentials, use them and skip authentication
    $access_token = json_decode(file_get_contents($CREDENTIALS_CACHE), true);
} else {
    // otherwise, do the oauth dance to get the access tokens

    // get the request token
    $request_token_rsp = OauthUtils::make_request($ENDPOINT_TOKEN_REQUEST,
        array(
            'oauth_consumer_key' => $CONSUMER_KEY,
            'oauth_consumer_secret' => $CONSUMER_SECRET,
            'oauth_callback' => 'https://www.3thirty.net/print_verifier.php',
        )
    );
    $request_token = OauthUtils::parse_response($request_token_rsp);

    // ask the user to do the verification (this is lame and sloppy)
    echo "Go to: {$ENDPOINT_AUTHORIZATION}?oauth_token={$request_token['oauth_token']}&oauth_verifier={$request_token['oauth_token_secret']}&perms=read\n";

    echo "Enter Code and ctrl+D: ";
    $oauth_verifier = trim(file_get_contents("php://stdin"));
    echo "";

    // get the access token
    $access_token_rsp = OauthUtils::make_request($ENDPOINT_ACCESS_TOKEN,
        array(
            'oauth_consumer_key' => $CONSUMER_KEY,
            'oauth_consumer_secret' => $CONSUMER_SECRET,
            'oauth_token' => $request_token['oauth_token'],
            'oauth_token_secret' => $request_token['oauth_token_secret'],
            'oauth_verifier' => $oauth_verifier
        )
    );
    $access_token = OauthUtils::parse_response($access_token_rsp);

    // cache the access token
    $access_token_cache = array (
        'oauth_token' => $access_token['oauth_token'],
        'oauth_token_secret' => $access_token['oauth_token_secret'],
    );

    file_put_contents($CREDENTIALS_CACHE, json_encode($access_token_cache));
}


// example api call with oauth
$test = OauthUtils::make_request(
    'https://api.flickr.com/services/rest',
     array (
        'method' => 'flickr.photosets.getList',
        'user_id' => '49743098@N00',
        'oauth_consumer_key' => $CONSUMER_KEY,
        'oauth_consumer_secret' => $CONSUMER_SECRET,
        'oauth_token' => $access_token['oauth_token'],
        'oauth_token_secret' => $access_token['oauth_token_secret'],
	)
);

var_dump($test);

/**
 * class to house methods for making Oauth calls
 */
class OauthUtils {
    /**
     * Make an oauth request
     *
     * @param string $url       The URL to make the request to
     * @param array  $user_args Arguments specific to this API request in format KEY => VALUE
     * @param string $method    The HTTP method to use. One of GET or POST
     *
     * @return string response from $url
     */
    public static function make_request ($url, $user_args, $method = 'GET') {
        $standard_args = array (
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0',
        );

        $request_args = array (
            'oauth_timestamp' => time(),
            'oauth_nonce' => rand(0, 9999),
        );

        $args = array_merge($standard_args, $request_args, $user_args);

        $signature = self::generate_signature($url, $args, $method);
        $args['oauth_signature'] = $signature;

        if ($method == 'GET') {
            if (substr($url, -1) != '?') {
                $url .= "?";
            }

            $url = $url . self::build_args_string($args, true);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        }

        $ret = curl_exec($ch);

        curl_close($ch);

        return $ret;
    }

    /**
     * Parse an oauth response into an associative array
     *
     * @param string $s The response string
     * @return array Data from $s in KEY => VALUE format
     */
    public static function parse_response ($s) {
        foreach (explode('&', $s) as $piece) {
            list ($k, $v) = explode('=', $piece);

            $ret[$k] = $v;
        }

        return $ret;
    }


    /**
     * Generate an API signature for the given url and arguments
     * @param string $url    The URL for the request
     * @param array  $args   Arguments in format KEY => VALUE
     * @param string $method The HTTP method to use. One of GET or POST
     *
     * @return string signature to attach to the request
     */
    private static function generate_signature ($url, $args, $method = 'GET') {
        $base_string = $method . "&" . rawurlencode($url) . "&" . rawurlencode(self::build_args_string($args, true));

        $key = $args['oauth_consumer_secret'] . "&";

        if ($args['oauth_token_secret']) {
            $key .= $args['oauth_token_secret'];
        }

        $ret = base64_encode(hash_hmac('sha1', $base_string, $key, true));

        return $ret;
    }

    /**
     * Construct a string of the given arguments. This can be used to construct a GET API call or to sign a call
     *
     * @param array $args      Arguments in format KEY => VALUE
     * @param bool  $urlencode If true, urlencode the values
     *
     * @return string of arguments
     */
    private static function build_args_string ($args, $urlencode = false) {
        ksort($args);

        $kv_args = array();

        foreach ($args as $k => $v) {
            // non-scalar values (such as CURLFile objects) are valid, but are not part of the signature
            if (!is_scalar($v)) {
                continue;
            }

            if ($urlencode) {
                $v = rawurlencode($v);
            }

            $kv_args[] = "{$k}={$v}";
        }

        return implode('&', $kv_args);
    }
}
