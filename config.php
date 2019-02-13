<?php
class redditConfig{
    //standard, oauth token fetch, and api request endpoints
    static $ENDPOINT_STANDARD = 'http://www.reddit.com';
    static $ENDPOINT_OAUTH = 'https://oauth.reddit.com';
    static $ENDPOINT_OAUTH_AUTHORIZE = 'https://www.reddit.com/api/v1/authorize';
    static $ENDPOINT_OAUTH_TOKEN = 'https://www.reddit.com/api/v1/access_token';
    static $ENDPOINT_OAUTH_REDIRECT = 'http://localhost/reddit/test.php';
    
    //access token configuration from https://ssl.reddit.com/prefs/apps
    static $CLIENT_ID = 'YOUR CLIENT ID';
    static $CLIENT_SECRET = 'YOUR SECRET';
    
    //access token request scopes
    //full list at http://www.reddit.com/dev/api/oauth
    static $SCOPES = 'save,modposts,identity,edit,flair,history,modconfig,modflair,modlog,modposts,modwiki,mysubreddits,privatemessages,read,report,submit,subscribe,vote,wikiedit,wikiread';
        
    // for permanent token - refresh token
    
    // https://www.reddit.com/r/redditdev/comments/3g8u2t/how_to_get_permanent_access_token_for_reddit/
    static $GRANT_TYPE = 'authorization_code';
    static $GRANT_TYPE_REFRESH = 'refresh_token';
    static $DURATION = 'permanent';
}
?>
