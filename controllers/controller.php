<?php

function render($page, $data) {
  global $app;
  ob_start();
  $app->render('layout.php', array_merge($data, array('page' => $page)));
  return ob_get_clean();
};

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

$app->get('/auth/start', function() use($app) {
  $req = $app->request();

  $params = $req->params();
  
  if(!array_key_exists('me', $params)) {
    die("Error"); // TODO: real HTML error to the developer explaining they need to pass in a "me" parameter
  }

  if(!array_key_exists('client_id', $params)) {
    die("No client_id specified"); // TODO: real HTML error page here explaining to the developer that they need to specify their client_id
  }

  if(!array_key_exists('redirect_uri', $params)) {
    die("No redirect_uri specified"); // TODO: real HTML error here explaining why a redirect_uri is required
  }

  // Check to see if there is a token endpoint
  $tokenEndpoint = IndieAuth\Client::discoverTokenEndpoint($params['me']);
  if(!$tokenEndpoint) {
    die("Unable to discover token endpoint"); // TODO: real HTML error page to the user with instructions on adding and creating a token endpoint
  }

  $micropubEndpoint = IndieAuth\Client::discoverMicropubEndpoint($params['me']);
  if(!$micropubEndpoint) {
    die("No micropub endpoint declared"); // TODO: real HTML error to the user with instructions on linking to their micropub endpoint
  }

  $_SESSION['client_id'] = $params['client_id'];
  $_SESSION['redirect_uri'] = $params['redirect_uri']; // Store the redirect_uri so we can redirect the browser there later

  // Find the authorization endpoint this user delegates to
  $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($params['me']);

  // Default to indieauth.com if they don't specify their own authorization endpoint
  if(!$authorizationEndpoint)
    $authorizationEndpoint = 'https://indieauth.com/auth';

  // Generate a "state" parameter for the request
  $state = IndieAuth\Client::generateStateParameter();
  $_SESSION['auth_state'] = $state;

  $scope = 'post';

  $authorizationURL = IndieAuth\Client::buildAuthorizationURL($authorizationEndpoint, $params['me'], buildRedirectURI(), $params['client_id'], $state, $scope);

  $app->redirect($authorizationURL);
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
