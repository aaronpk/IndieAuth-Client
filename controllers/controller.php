<?php

function buildRedirectURI() {
  return 'http://' . $_SERVER['SERVER_NAME'] . '/auth/callback';
}

$app->get('/', function() use($app) {
  $html = render('index', array('title' => 'IndieAuth Client'));
  $app->response()->body($html);
});

$app->get('/signin', function() use($app) {
  $html = render('signin', array('title' => 'Sign In'));
  $app->response()->body($html);
});

$app->get('/docs', function() use($app) {
  $html = render('docs', array('title' => 'Docs'));
  $app->response()->body($html);
});

$app->get('/creating-a-token-endpoint', function() use($app) {
  $html = render('creating-a-token-endpoint', array('title' => 'Creating a Token Endpoint'));
  $app->response()->body($html);
});

$app->get('/creating-a-micropub-endpoint', function() use($app) {
  $html = render('creating-a-micropub-endpoint', array('title' => 'Creating a Micropub Endpoint'));
  $app->response()->body($html);
});

$app->get('/auth/start', function() use($app) {
  $req = $app->request();

  $params = $req->params();
  
  if(!array_key_exists('me', $params) || $params['me'] == '') {
    $html = render('auth_error', array(
      'title' => 'Sign In',
      'error' => 'Missing "me" parameter',
      'errorDescription' => 'No "me" parameter was specified in the request.'
    ));
    $app->response()->body($html);
    return;
  }

  if(!array_key_exists('me', $params) || !($me = normalizeMeURL($params['me']))) {
    $html = render('auth_error', array(
      'title' => 'Sign In',
      'error' => 'Invalid "me" Parameter',
      'errorDescription' => 'The ID you entered, <strong>' . $params['me'] . '</strong> is not valid.'
    ));
    $app->response()->body($html);
    return;
  }

  if(!array_key_exists('client_id', $params) || $params['client_id'] == '') {
    $html = render('auth_error', array(
      'title' => 'Sign In',
      'error' => 'Missing "client_id" Parameter',
      'errorDescription' => 'No "client_id" parameter was specified in the request. Every IndieAuth request must include a client_id indicating the app that is signing the user in.'
    ));
    $app->response()->body($html);
    return;
  }

  if(!array_key_exists('redirect_uri', $params) || $params['redirect_uri'] == '') {
    $html = render('auth_error', array(
      'title' => 'Sign In',
      'error' => 'Missing "redirect" Parameter',
      'errorDescription' => 'No "redirect_uri" parameter was specified in the request. Every IndieAuth request must include a redirect_uri otherwise the client doesn\'t know where to direct the user back to after signing in.'
    ));
    $app->response()->body($html);
    return;
  }

  $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);
  $tokenEndpoint = IndieAuth\Client::discoverTokenEndpoint($me);
  $micropubEndpoint = IndieAuth\Client::discoverMicropubEndpoint($me);

  // Default to indieauth.com if they don't specify their own authorization endpoint
  if(!$authorizationEndpoint)
    $authorizationEndpoint = 'https://indieauth.com/auth';

  if($tokenEndpoint && $micropubEndpoint && $authorizationEndpoint) {
    // Generate a "state" parameter for the request
    $state = IndieAuth\Client::generateStateParameter();
    $_SESSION['auth_state'] = $state;
    $_SESSION['client_id'] = $params['client_id'];
    $_SESSION['redirect_uri'] = $params['redirect_uri']; // Store the redirect_uri so we can redirect the browser there later

    $scope = 'post';
    $authorizationURL = IndieAuth\Client::buildAuthorizationURL($authorizationEndpoint, $me, buildRedirectURI(), $_SESSION['client_id'], $state, $scope);
  } else {
    $authorizationURL = false;
  }

  // If the user has already signed in before and has a micropub access token, skip 
  // the debugging screens and redirect immediately to the auth endpoint.
  // This will still generate a new access token when they finish logging in.
  // $user = ORM::for_table('users')->where('url', $me)->find_one();
  if(false && $user && $user->micropub_access_token && !array_key_exists('restart', $params)) {

    $user->micropub_endpoint = $micropubEndpoint;
    $user->authorization_endpoint = $authorizationEndpoint;
    $user->token_endpoint = $tokenEndpoint;
    $user->save();

    $app->redirect($authorizationURL, 301);

  } else {

    // if(true || !$user)
    //   $user = ORM::for_table('users')->create();
    // $user->url = $me;
    // $user->date_created = date('Y-m-d H:i:s');
    // $user->micropub_endpoint = $micropubEndpoint;
    // $user->authorization_endpoint = $authorizationEndpoint;
    // $user->token_endpoint = $tokenEndpoint;
    // $user->save();

    $html = render('auth_start', array(
      'title' => 'Sign In',
      'me' => $me,
      'authorizing' => $me,
      'meParts' => parse_url($me),
      'tokenEndpoint' => $tokenEndpoint,
      'micropubEndpoint' => $micropubEndpoint,
      'authorizationEndpoint' => $authorizationEndpoint,
      'authorizationURL' => $authorizationURL
    ));
    $app->response()->body($html);
  }

});

$app->get('/auth/callback', function() use($app) {
  $req = $app->request();

  $params = $req->params();

  // Required params:
  // me, code, state

  if(!array_key_exists('code', $params))
    die("Missing code parameter");

  // Verify the state matches what we set in the initial request
  if(!array_key_exists('state', $params))
    die("Missing state");

  if($params['state'] != $_SESSION['auth_state'])
    die("State did not match");

  // Now discover the token endpoint for the "me" parameter and exchange the auth code for an access token
  $tokenEndpoint = IndieAuth\Client::discoverTokenEndpoint($params['me']);

  if(!$tokenEndpoint) 
    die("Unable to discover token endpoint"); // TODO: Return a real web page here, or redirect back to the app with an error, it will be a common error since it requires people add a rel link to their token endpoint

  $token = IndieAuth\Client::getAccessToken($tokenEndpoint, $params['code'], $params['me'], buildRedirectURI(), $_SESSION['client_id'], $_SESSION['auth_state']);

  if($token && array_key_exists('me', $token) && array_key_exists('access_token', $token)) {
    // User logged in!

    // Find the micropub endpoint to pass back to the application
    $micropubEndpoint = IndieAuth\Client::discoverMicropubEndpoint($params['me']);
    if(!$micropubEndpoint)
      die("No micropub endpoint declared"); // TODO: Return a real web page here, or redirect back to the app with an error

    $token['micropub_endpoint'] = $micropubEndpoint;

    $redirect_uri = parse_url($_SESSION['redirect_uri']);
    $redirect_params = array();
    if(array_key_exists('query', $redirect_uri)) {
      parse_str($redirect_uri['query'], $redirect_params);
    }
    $redirect_params = array_merge($redirect_params, $token);
    $redirect_uri['query'] = http_build_query($redirect_params);

    #echo IndieAuth\Client::build_url($redirect_uri);
    $app->redirect(IndieAuth\Client::build_url($redirect_uri));

  } else {
    // Error!
    echo '<h3>Error!</h3>';
    echo '<pre>';
    print_r($token);
  }

});

$app->get('/auth/complete', function() use($app) {
  $req = $app->request();
  $params = $req->params();

  echo '<h3>Signed in successfully!</h3>';
  echo '<p>The token info is below. In a real app, you would use your own redirect URI and get the token directly.</p>';
  echo '<pre>';
  print_r($params);
  echo '</pre>';

});
