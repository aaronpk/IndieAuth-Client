<!doctype html>
<html lang="en">
  <head>
    <title><?= $this->title ?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="pingback" href="https://webmention.io/indiewebcamp/xmlrpc" />
    <link rel="webmention" href="https://webmention.io/indiewebcamp/webmention" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/style.css">

    <script src="/js/jquery-1.7.1.min.js"></script>
  </head>

<body role="document">
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?= Config::$gaid ?>']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<div class="navbar navbar-inverse navbar-fixed-top">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/">IndieAuth Sign-In</a>
    </div>
    <div class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
        <? if(session('me')) { ?>
          <li><a href="/dashboard">Dashboard</a></li>
        <? } ?>
        <li><a href="/docs">Docs</a></li>
        <!-- <li><a href="/contact">Contact</a></li> -->
      </ul>
    </div>
  </div>
</div>

<div class="page">

  <div class="container">
    <?= $this->fetch($this->page . '.php') ?>
  </div>

  <div class="footer">
    <p class="credits">&copy; <?=date('Y')?> by <a href="http://aaronparecki.com">Aaron Parecki</a>.
      This code is <a href="https://github.com/aaronpk/IndieAuth-Client">open source</a>. 
      Feel free to send a pull request, or <a href="https://github.com/aaronpk/IndieAuth-Client/issues">file an issue</a>.</p>
  </div>
</div>

</body>
</html>