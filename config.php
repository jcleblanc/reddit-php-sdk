<?php
//api request endpoints for oauth and basic auth
define("ENDPOINT_BASIC", "https://ssl.reddit.com/api");
define("ENDPOINT_OAUTH", "https://oauth.reddit.com");

//oauth token fetch endpoints
define('ENDPOINT_OAUTH_AUTHORIZE', 'https://ssl.reddit.com/api/v1/authorize');
define('ENDPOINT_OAUTH_TOKEN', 'https://ssl.reddit.com/api/v1/access_token');
define('ENDPOINT_OAUTH_REDIRECT', 'http://localhost/reddit/test.php');

//access token configuration
define('CLIENT_ID', 'YOUR CLIENT ID');
define('CLIENT_SECRET', 'YOUR CLIENT SECRET');

//access token request scopes
//full list at http://www.reddit.com/dev/api/oauth
define('SCOPES', 'modposts,identity,edit,flair,history,modconfig,modflair,modlog,modposts,modwiki,mysubreddits,privatemessages,read,report,save,submit,subscribe,vote,wikiedit,wikiread');                                           
?>
