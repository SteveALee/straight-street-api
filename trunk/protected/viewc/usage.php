<?php
require 'protected/config/apiconsts.php';
?>
<html>
    <head>
        <META http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Straight-Street api</title>
    </head>
	<body>
<h1>Straight-street.com API <?= VERSION ?></h1>
<p>This is the RESTful API for the <a href='http://straight-street.com'>straight-street.com</a> symbol set. It allows programs to search for and access symbols as users can do using the interactive <a href="../gallery.php">gallery web page</a>.
</p>
<p>
 A <a href='<?= APIURLBASE ?>test.html'>test web app</a> is available for exploring the api and provides an example of javascript client-side access.
 </p>
 <h2>Specification</h2>
<p>All symbols have a name and may have multiple tags associated with them. Names and tag names may contain spaces.</p>
<p>
	<table>
	 <tr><td colspan=2'>API URL base = <?= APIURLBASE ?></td></tr>
	 <tr><td colspan='2'></td></tr>

	 <tr><td>symbols/EN</td><td>all english symbols - inludes links to the symbols themselves</td></tr>
	 <tr><td>symbols/EN/{tokens}</td><td>symbols (and related tags) with a name or tag that includes 'tokens' (1 or more space separated)</td></tr>
	 <tr><td>symbol/EN/{name}</td><td>single symbol with name of 'name'</td></tr>
	 <tr><td>tags/EN</td><td>all tags</td></tr>
	 <tr><td>tags/EN/{tokens}</td><td>all tags (and related symbols) with name 'tokens' or attached to symbols with 'tokens' in their name</td></tr>
	 <tr><td>tag/EN/{name}</td><td>single tag of name</td></tr>
	 <tr><td>usage</td><td>usage information - this page</td></tr>

	 <tr><td colspan='2'></td></tr>
	 <tr><td>?appid=example.com</td><td>required application id e.g. domain. <?= APPIDMINCHARS ?> to <?= APPIDMAXCHARS ?> characters (a letter followed by letters or digits). <br/>If missing then '401 Unauthorised' is returned</td></tr>
	 <tr><td>?callback=func</td><td>Optional <a href='http://developer.yahoo.com/javascript/json.html'>function call support</a> for cross domain access (AKA JSONP)</td></tr>

	 <tr><td colspan='2'></td></tr>
	 <tr><td colspan='2'><b>Paging control</b> - when large numbers of items returned - e.g symbols</td></tr>
	 <tr><td>symbols/EN/{page}</td><td>optional page number 'page' of <?= PAGESIZEDEF ?> symbols. 1st page and default when not specified is 0</td></tr>
	 <tr><td>symbols/EN/{page}/{pagesize}</td><td>optional page number 'page' of 'pagesize' symbols. Max is <?= PAGESIZEMAX ?>, default is <?= PAGESIZEDEF ?></td></tr>
	 <tr><td>?page=n</td><td>Alternative for page number. Above syntax is prefered.</td></tr>
	 <tr><td>?pagesize=n</td><td>Alternative for page size. Above syntax is prefered</td></tr>
	</table>
 </p>
 <p>
 Responses are JSON (mime type "application/json") and to interactively explore them you can use the <a href='https://addons.mozilla.org/en-US/firefox/addon/jsonview/'>JSONView</a> Firefox add-on or view them with the <a href='http://jsonformatter.curiousconcept.com/'>JSON Formatter</a> web service. Some items include the related symbol or tag names plus associated URLs into the api. The URLs for several formats of the symbol image are also returned. When an item is not found an empty JSON object is returned rather than HTTP 404. A 404 is returned for invalid URIs.
 </p>
 <h2>Examples</h2>
 <p>
 The following links demostrate the API (appid is not shown for simplicity).
 <ul>
 <li><a href='<?= APIURLBASE ?>symbols/EN?appid=SSApiUsage'><?= APIURLBASE ?>symbols/ENs</a> - all symbols</li>
 <li><a href='<?= APIURLBASE ?>symbols/EN/sweet?appid=SSApiUsage'><?= APIURLBASE ?>symbols/EN/sweet</a> - all symbols with name or tag containing 'sweet'</li>
 <li><a href='<?= APIURLBASE ?>symbols/EN/sweet/1/3?appid=SSApiUsage'><?= APIURLBASE ?>symbols/EN/sweet/1/3</a> - second page each of 3 symbols</li>
 <li><a href='<?= APIURLBASE ?>symbol/EN/sweet?appid=SSApiUsage'><?= APIURLBASE ?>symbol/EN/sweet</a> - symbol with name 'sweet'</li>
 <li><a href='<?= APIURLBASE ?>tags/EN?appid=SSApiUsage'><?= APIURLBASE ?>tags/EN</a></li>
 <li><a href='<?= APIURLBASE ?>tags/EN/sweet?appid=SSApiUsage'><?= APIURLBASE ?>tags/EN/sweet</a></li>
 <li><a href='<?= APIURLBASE ?>tag/EN/sweet?appid=SSApiUsage'><?= APIURLBASE ?>tag/EN/sweet</a></li>
 <li><a href='<?= APIURLBASE ?>symbols/EN/sweet?appid=SSApiUsage&callback=jsonpcb'><?= APIURLBASE ?>symbols/EN/sweet?callback=jsonpcb</a></li>
 <li><a href='<?= APIURLBASE ?>symbols/EN/lever%20arch%20file?appid=SSApiUsage'><?= APIURLBASE ?>symbols/EN/lever arch file</a></li>
</ul>
</p>
 <h2>Known issues</h2>
 <p>None.</p>
	</body>
</html>