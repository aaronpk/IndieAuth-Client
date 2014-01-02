<meta name="viewport" content="width=device-width,initial-scale=1">

<form action="/auth/start" method="get">
  <label for="me">me:</label><br>
  <input type="text" name="me" placeholder="http://me.example.com" size="40" value=""><br>

  <label for="client_id">client_id:</label><br>
  <input type="text" name="client_id" placeholder="https://app.example.com" size="40" value=""><br>

  <label for="redirect_uri">redirect_uri:</label><br>
  <input type="text" name="redirect_uri" placeholder="https://app.example.com/auth" size="40" value=""><br>
  
  <input type="submit" value="Begin Authorization">
</form>

<p>This service acts as an IndieAuth client, performing discovery on the user's website to 
  find their authorization server and token endpoint. After the user authorizes your 
  application, they are redirected back to this website, which then redirects them back
  to the redirect URI you specified with all the information your app needs in the query string.</p>

<p>This service can be used to quickly develop an application using IndieAuth, since it
  handles all the discovery and token exchange for you. It will often be quicker to let
  this service handle auth for you than implement all the discovery/token logic in a 
  native application for example.</p>

<p>This form is here to help demonstrate the sign-in flow. In a real application, you 
  would create your own "<a href="http://indiewebcamp.com/Web_sign-in">web sign-in</a>" 
  interface prompting the user for their web address. Then you can open a browser 
  window to the address this form redirects to.</p>

<h3>Parameters</h3>
<p><b>me:</b> the user's web address. They should enter this in your application, and you can include the value when building the auth URL.</p>
<p><b>client_id:</b> the website and unique identifier for your application. On this website, you will need to whitelist the valid redirect_uris for your application.</p>
<p><b>redirect_uri:</b> where this IndieAuth client should redirect to after authorization is complete.</p>

<h3>Example URL</h3>
<p>https://client.indieauth.com/auth/start?me=<?=urlencode('http://example.com')?>&amp;client_id=<?=urlencode('http://your-great-app.com')?>&amp;redirect_uri=<?=urlencode('http://your-great-app.com/auth')?></p>
