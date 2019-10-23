# oauth_demo
Simple oauth 1.0A client implementation in PHP

## Setup/Use
  * Add your consumer key and secret
  * Set appropriate endpoints (flickr and twitter examples are included)
  * Run from commandline to authorize the app. Subsequent calls will use the credentials cached in the `$CREDENTIALS_CACHE` file

## Examples: Flickr
### Upload
```
// example POST upload call to flickr
$test = OauthUtils::make_request(
    'https://up.flickr.com/services/upload',
    array (
        'title' => 'test upload @ ' . date("Y-m-d H:i:s"),
        'is_public' => 0,
        'photo' => new CURLFile('test.png'),

        'oauth_consumer_key' => $CONSUMER_KEY,
        'oauth_consumer_secret' => $CONSUMER_SECRET,
        'oauth_token' => $access_token['oauth_token'],
        'oauth_token_secret' => $access_token['oauth_token_secret'],
    ),
    'POST'
);
```
