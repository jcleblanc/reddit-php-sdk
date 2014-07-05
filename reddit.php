<?php
require_once("config.php");

/**
* Reddit PHP SDK
*
* Provides a SDK for accessing the Reddit APIs
* Useage: 
*   $reddit = new reddit();
*   $user = $reddit->getUser();
*/
class reddit{
    private $access_token;
    private $token_type;
    private $auth_mode = "basic";
    
    /**
    * Class Constructor
    *
    * Construct the class and simultaneously log a user in.
    * @link https://github.com/reddit/reddit/wiki/API%3A-login
    */
    public function __construct(){
        if (isset($_GET['code'])){
            //capture code from auth
            $code = $_GET["code"];
            
            //construct POST object for access token fetch request
            $postvals = sprintf("code=%s&redirect_uri=%s&grant_type=authorization_code&client_id=%s",
                                $code,
                                ENDPOINT_OAUTH_REDIRECT,
                                CLIENT_ID);
            
            //get JSON access token object (with refresh_token parameter)
            $token = self::runCurl(ENDPOINT_OAUTH_TOKEN, $postvals, null, true);
            
            //store token and type
            if (isset($token->access_token)){
                $this->access_token = $token->access_token;
                $this->token_type = $token->token_type;
            }
            
            //set API endpoint
            $this->apiHost = ENDPOINT_OAUTH;
            
            //set auth mode for requests
            $this->auth_mode = 'oauth';
        } else {
            $state = rand();
            $urlAuth = sprintf("%s?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&state=%s",
                               ENDPOINT_OAUTH_AUTHORIZE,
                               CLIENT_ID,
                               ENDPOINT_OAUTH_REDIRECT,
                               SCOPES,
                               $state);
                
            //forward user to PayPal auth page
            header("Location: $urlAuth");
        }
    }
    
    /**
    * Create new story
    *
    * Creates a new story on a particular subreddit
    * @link http://www.reddit.com/dev/api/oauth#POST_api_submit
    * @param string $title The title of the story
    * @param string $link The link that the story should forward to
    * @param string $subreddit The subreddit where the story should be added
    */
    public function createStory($title = null, $link = null, $subreddit = null){
        $urlSubmit = "{$this->apiHost}/api/submit";
        
        //data checks and pre-setup
        if ($title == null || $subreddit == null){ return null; }
        $kind = ($link == null) ? "self" : "link";
        
        $postData = sprintf("kind=%s&sr=%s&title=%s&r=%s",
                            $kind,
                            $subreddit,
                            urlencode($title),
                            $subreddit);
        
        //if link was present, add to POST data             
        if ($link != null){ $postData .= "&url=" . urlencode($link); }
    
        $response = $this->runCurl($urlSubmit, $postData);
        
        /*if ($response->jquery[18][3][0] == "that link has already been submitted"){
            return $response->jquery[18][3][0];
        }*/
    }
    
    /**
    * Get user
    *
    * Get data for the current user
    * @link http://www.reddit.com/dev/api#GET_api_v1_me
    */
    public function getUser(){
        $urlUser = "{$this->apiHost}/api/v1/me";
        return self::runCurl($urlUser, null, true);
    }
    
    /**
    * Get messages
    *
    * Get messages (inbox / unread / sent) for the current user
    * @link http://www.reddit.com/dev/api/oauth#GET_message_inbox
    * @param string $where The message type to return. One of inbox, unread, or sent
    */
    public function getMessages($where = "inbox"){
        $urlMessages = "{$this->apiHost}/message/$where";
        return self::runCurl($urlMessages);
    }
    
    /**
    * Send message
    *
    * Send a message to another user, from the current user
    * @link http://www.reddit.com/dev/api/oauth#POST_api_compose
    * @param string $to The name of a existing user to send the message to
    * @param string $subject The subject of the message, no longer than 100 characters
    * @param string $text The content of the message, in raw markdown
    */
    public function sendMessage($to, $subject, $text){
        $urlMessages = "{$this->apiHost}/api/compose";
        
        $postData = sprintf("to=%s&subject=%s&text=%s",
                            $to,
                            $subject,
                            $text);
        
        return self::runCurl($urlMessages, $postData);
    }
    
    /**
    * Set read / unread message state
    *
    * Sets the read and unread state of a comma separates list of messages
    * @link http://www.reddit.com/dev/api/oauth#POST_api_read_message
    * @link http://www.reddit.com/dev/api/oauth#POST_api_unread_message
    * @param string $state The state to set the messages to, either read or unread
    * @param string $subject A comma separated list of message fullnames (t4_ and the message id - e.g. t4_1kuinv)
    */
    public function setMessageState($state = "read", $ids){
        $urlMessageState = "{$this->apiHost}/api/{$state}_message";
        $postData = "id=$ids";
        return self::runCurl($urlMessageState, $postData);
    }
    
    /**
    * Get user subscriptions
    *
    * Get the subscriptions that the user is subscribed to, has contributed to, or is moderator of
    * @link http://www.reddit.com/dev/api#GET_subreddits_mine_contributor
    * @param string $where The subscription content to obtain. One of subscriber, contributor, or moderator
    */
    public function getSubscriptions($where = "subscriber"){
        $urlSubscriptions = "{$this->apiHost}/subreddits/mine/$where";
        return self::runCurl($urlSubscriptions);
    }
    
    /**
    * Get listing
    *
    * Get the listing of submissions from a subreddit
    * @link http://www.reddit.com/dev/api#GET_listing
    * @param string $sr The subreddit name. Ex: technology, limit (integer): The number of posts to gather
    * @param int $limit The number of listings to return
    */
    public function getListing($sr, $limit = 5){
        $limit = (isset($limit)) ? "?limit=".$limit : "";
        if($sr == 'home' || $sr == 'reddit' || !isset($sr)){
            $urlListing = "http://www.reddit.com/.json{$limit}";
        } else {
            $urlListing = "http://www.reddit.com/r/{$sr}/.json{$limit}";
        }
        return self::runCurl($urlListing);
    }
    
    /**
    * Get page information
    *
    * Get information on a URLs submission on Reddit
    * @link http://www.reddit.com/dev/api#GET_api_info
    * @param string $url The URL to get information for
    */
    public function getPageInfo($url){
        $response = null;
        if ($url){
            $urlInfo = "{$this->apiHost}/api/info?url=" . urlencode($url);
            $response = self::runCurl($urlInfo);
        }
        return $response;
    }
    
    /**
    * Get Raw JSON
    *
    * Get Raw JSON for a reddit permalink
    * @param string $permalink permalink to get raw JSON for
    */
    public function getRawJSON($permalink){
        $urlListing = "http://www.reddit.com/{$permalink}.json";
        return self::runCurl($urlListing);
    }  
         
    /**
    * Save post
    *
    * Save a post to your account.  Save feeds:
    * http://www.reddit.com/saved/.xml
    * http://www.reddit.com/saved/.json
    * @link http://www.reddit.com/dev/api#POST_api_save
    * @param string $name the full name of the post to save (name parameter
    *                     in the getSubscriptions() return value)
    * @param string $category the categorty to save the post to                   
    */
    public function savePost($name, $category = null){
        $response = null;
        $cat = (isset($category)) ? "&category=$category" : "";
        
        if ($name){
            $urlSave = "{$this->apiHost}/api/save";
            $postData = "id=$name$cat";
            $response = self::runCurl($urlSave, $postData);
        }
        return $response;
    }
    
    /**
    * Unsave post
    *
    * Unsave a saved post from your account
    * @link http://www.reddit.com/dev/api#POST_api_unsave
    * @param string $name the full name of the post to unsave (name parameter
    *                     in the getSubscriptions() return value)
    */
    public function unsavePost($name){
        $response = null;
        
        if ($name){
            $urlUnsave = "{$this->apiHost}/api/unsave";
            $postData = "id=$name";
            $response = self::runCurl($urlUnsave, $postData);
        }
        return $response;
    }
    
    /**
    * Get historical user data
    *
    * Get the historical data of a user
    * @link http://www.reddit.com/dev/api/oauth#scope_history
    * @param string $username the desired user. Must be already authenticated.
    * @param string $where the data to retrieve. One of overview,submitted,comments,liked,disliked,hidden,saved,gilded
    */
    public function getHistory($username, $where = "saved"){
        $urlHistory = "{$this->apiHost}/user/$username/$where";
        return self::runCurl($urlHistory);
    }
    
    /**
    * Hide post
    *
    * Hide a post on your account
    * @link http://www.reddit.com/dev/api/oauth#POST_api_hide
    * @param string $name The full name of the post to hide (name parameter
    *                     in the getSubscriptions() return value)
    */
    public function hidePost($name){
        $response = null;
        if ($name){
            $urlHide = "{$this->apiHost}/api/hide";
            $postData = "id=$name";
            $response = self::runCurl($urlHide, $postData);
        }
        return $response;
    }
    
    /**
    * Unhide post
    *
    * Unhide a hidden post on your account
    * @link http://www.reddit.com/dev/api/oauth#POST_api_unhide
    * @param string $name The full name of the post to unhide (name parameter
    *                     in the getSubscriptions() return value)
    */
    public function unhidePost($name){
        $response = null;
        if ($name){
            $urlUnhide = "{$this->apiHost}/api/unhide";
            $postData = "id=$name";
            $response = self::runCurl($urlUnhide, $postData);
        }
        return $response;
    }
    
    /**
    * Add new comment
    *
    * Add a new comment to a story
    * @link http://www.reddit.com/dev/api/oauth#POST_api_comment
    * @param string $name The full name of the post to comment (name parameter
    *                     in the getSubscriptions() return value)
    * @param string $text The comment markup
    */
    public function addComment($name, $text){
        $response = null;
        if ($name && $text){
            $urlComment = "{$this->apiHost}/api/comment";
            $postData = sprintf("thing_id=%s&text=%s",
                                $name,
                                $text);
            $response = self::runCurl($urlComment, $postData);
        }
        return $response;
    }
    
    /**
    * Vote on a story
    *
    * Adds a vote (up / down / neutral) on a story
    * @link http://www.reddit.com/dev/api/oauth#POST_api_vote
    * @param string $name The full name of the post to vote on (name parameter
    *                     in the getSubscriptions() return value)
    * @param int $vote The vote to be made (1 = upvote, 0 = no vote,
    *                  -1 = downvote)
    */
    public function addVote($name, $vote = 1){
        $response = null;
        if ($name){
            $urlVote = "{$this->apiHost}/api/vote";
            $postData = sprintf("id=%s&dir=%s", $name, $vote);
            $response = self::runCurl($urlVote, $postData);
        }
        return $response;
    }
    
    /**
    * Set flair
    *
    * Set or clear a user's flair in a subreddit
    * @link http://www.reddit.com/dev/api/oauth#POST_api_flair
    * @param string $subreddit The subreddit to use
    * @param string $user The name of the user
    * @param string $text Flair text to assign
    * @param string $cssClass CSS class to assign to the flair text
    */
    public function setFlair($subreddit, $user, $text, $cssClass){
        $urlFlair = "{$this->apiHost}/r/$subreddit/api/flair";
        $postData = sprintf("name=%s&text=%s&css_class=%s",
                            $user,
                            $text,
                            $cssClass);
        $response = self::runCurl($urlFlair, $postData);
        return $response;
    }
    
    /**
    * Get flair list
    *
    * Download the flair assignments of a subreddit
    * @link http://www.reddit.com/dev/api/oauth#GET_api_flairlist
    * @param string $subreddit The subreddit to use
    * @param int $limit The maximum number of items to return (max 1000)
    * @param string $after Return entries starting after this user
    * @param string $before Return entries starting before this user
    */
    public function getFlairList($subreddit, $limit = 100, $after, $before){
        $urlFlairList = "{$this->apiHost}/r/$subreddit/api/flairlist";
        $postData = sprintf("limit=%s&after=%s&before=%s",
                            $limit,
                            $after,
                            $before);
        $response = self::runCurl($urlFlairList, $postData);
        return $response;
    }
    
    /**
    * Set flair CSV file
    *
    * Post a CSV file of flair settings to a subreddit
    * @link http://www.reddit.com/dev/api/oauth#POST_api_flaircsv
    * @param string $subreddit The subreddit to use
    * @param string $flairCSV CSV file contents, up to 100 lines
    */
    public function setFlairCSV($subreddit, $flairCSV){
        $urlFlairCSV = "{$this->apiHost}/r/$subreddit/api/flaircsv";
        $postData = "flair_csv=$flairCSV";
        $response = self::runCurl($urlFlairCSV, $postData);
        return $response;
    }
    
    /**
    * cURL request
    *
    * General cURL request function for GET and POST
    * @link URL
    * @param string $url URL to be requested
    * @param string $postVals NVP string to be send with POST request
    */
    private function runCurl($url, $postVals = null, $headers = null, $auth = false){
        $ch = curl_init($url);
        
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3
        );
        
        if (!empty($_SERVER['HTTP_USER_AGENT'])){
            $options[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        if ($postVals != null){
            $options[CURLOPT_POSTFIELDS] = $postVals;
            $options[CURLOPT_CUSTOMREQUEST] = "POST";
        }
        
        if ($this->auth_mode == 'oauth'){
            $headers = array("Authorization: {$this->token_type} {$this->access_token}");
            $options[CURLOPT_HEADER] = false;
            $options[CURLINFO_HEADER_OUT] = false;
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
        
        if ($auth){
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_USERPWD] = CLIENT_ID . ":" . CLIENT_SECRET;
            $options[CURLOPT_SSLVERSION] = 3;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = 2;
        }
        
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $response = json_decode($response);
        curl_close($ch);
        
        return $response;
    }
}
?>
