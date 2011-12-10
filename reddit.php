<?php
/*******************************************************************************
 * Class Name: Reddit PHP SDK
 * Description: Provides a SDK for accessing the Reddit APIs
 * Useage: 
 *   $reddit = new reddit();
 *   $reddit->login("USERNAME", "PASSWORD");
 *   $user = $reddit->getUser();
 ******************************************************************************/
class reddit{
    private $apiHost = "http://www.reddit.com/api";
    private $modHash = null;
    private $session = null;
    
    /***************************************************************************
     * Function: Login
     * Description: Logs user into Reddit and stores session data
     * API: https://github.com/reddit/reddit/wiki/API%3A-login
     * Params: username (string): The username to be logged into
     *         password (string): The password to be used to log in
     **************************************************************************/
    public function login($username = null, $password = null){
        $urlLogin = "{$this->apiHost}/login/$username";
        
        $postData = sprintf("api_type=json&user=%s&passwd=%s",
                            $username,
                            $password);
        $response = $this->runCurl($urlLogin, $postData);
        
        if (strlen($response->errors) > 0){
            return "login error";    
        } else {
            $this->modHash = $response->json->data->modhash;   
            $this->session = $response->json->data->cookie;
            return $this->modHash;
        }
    }
    
    /***************************************************************************
     * Function: Create New Story
     * Description: Creates a new story on a particular subreddit
     * API: https://github.com/reddit/reddit/wiki/API%3A-submit
     * Params: title (string): The title of the story 
     *         link (string): The link that the story should forward to
     *         subreddit (string): The subreddit where the story should be added
     **************************************************************************/
    public function createStory($title = null, $link = null, $subreddit = null){
        $urlSubmit = "{$this->apiHost}/submit";
        
        //data checks and pre-setup
        if ($title == null){ return null; }
        if ($subreddit == null){ $subreddit = "reddit.com"; }
        $kind = ($link == null) ? "self" : "link";
        
        $postData = sprintf("uh=%s&kind=%s&sr=%s&title=%s&r=%s&renderstyle=html",
                            $this->modHash,
                            $kind,
                            $subreddit,
                            urlencode($title),
                            $subreddit);
        
        //if link was present, add to POST data             
        if ($link != null){ $postData .= "&url=" . urlencode($link); }
    
        $response = $this->runCurl($urlSubmit, $postData);
        
        if ($response->jquery[18][3][0] == "that link has already been submitted"){
            return $response->jquery[18][3][0];
        }
    }
    
    /***************************************************************************
     * Function: Get user
     * Description: Get data for the current user
     * API: https://github.com/reddit/reddit/wiki/API%3A-me.json
     **************************************************************************/
    public function getUser(){
        $urlUser = "{$this->apiHost}/me.json";
        return $this->runCurl($urlUser);
    }
    
    /***************************************************************************
     * Function: Get User Subscriptions
     * Description: Get the subscriptions that the user is subscribed to
     * API: https://github.com/reddit/reddit/wiki/API%3A-mine.json
     **************************************************************************/
    public function getSubscriptions(){
        $urlSubscriptions = "http://www.reddit.com/reddits/mine.json";
        return $this->runCurl($urlSubscriptions);
    }
    
    /***************************************************************************
     * Function: Get Page Information
     * Description: Get information on a URLs submission on Reddit
     * API: https://github.com/reddit/reddit/wiki/API%3A-info.json
     * Params: url (string): The URL to get information for
     **************************************************************************/
    public function getPageInfo($url){
        $response = null;
        if ($url){
            $urlInfo = "{$this->apiHost}/info.json?url=" . urlencode($url);
            $response = $this->runCurl($urlInfo);
        }
        return $response;
    }
    
    /***************************************************************************
     * Function: Save Post
     * Description: Save a post to your account.  Save feeds:
     *              http://www.reddit.com/saved/.xml
     *              http://www.reddit.com/saved/.json
     * API: https://github.com/reddit/reddit/wiki/API%3A-save
     * Params: name (string): The full name of the post to save (name parameter
     *                        in the getSubscriptions() return value)
     **************************************************************************/
    public function savePost($name){
        $response = null;
        if ($name){
            $urlSave = "{$this->apiHost}/save";
            $postData = sprintf("id=%s&uh=%s", $name, $this->modHash);
            $response = $this->runCurl($urlSave, $postData);
        }
        return $response;
    }
    
    /***************************************************************************
     * Function: Unsave Post
     * Description: Unsave a saved post from your account
     * API: https://github.com/reddit/reddit/wiki/API%3A-unsave
     * Params: name (string): The full name of the post to save (name parameter
     *                        in the getSubscriptions() return value)
     **************************************************************************/
    public function unsavePost($name){
        $response = null;
        if ($name){
            $urlUnsave = "{$this->apiHost}/unsave";
            $postData = sprintf("id=%s&uh=%s", $name, $this->modHash);
            $response = $this->runCurl($urlUnsave, $postData);
        }
        return $response;
    }
    
    /***************************************************************************
     * Function: Hide Post 
     * Description: Hide a post on your account
     * API: https://github.com/reddit/reddit/wiki/API%3A-hide
     * Params: name (string): The full name of the post to save (name parameter
     *                        in the getSubscriptions() return value)
     **************************************************************************/
    public function hidePost($name){
        $response = null;
        if ($name){
            $urlHide = "{$this->apiHost}/hide";
            $postData = sprintf("id=%s&uh=%s", $name, $this->modHash);
            $response = $this->runCurl($urlHide, $postData);
        }
        return $response;
    }
    
    /***************************************************************************
     * Function: Unhide Post
     * Description: Unhide a hidden post on your account
     * API: https://github.com/reddit/reddit/wiki/API%3A-unhide
     * Params: name (string): The full name of the post to save (name parameter
     *                        in the getSubscriptions() return value)
     **************************************************************************/
    public function unhidePost($name){
        $response = null;
        if ($name){
            $urlUnhide = "{$this->apiHost}/unhide";
            $postData = sprintf("id=%s&uh=%s", $name, $this->modHash);
            $response = $this->runCurl($urlUnhide, $postData);
        }
        return $response;
    }
    
    /***************************************************************************
     * Function: Share a Post
     * Description: E-Mail a post to someone
     * API: https://github.com/reddit/reddit/wiki/API
     * Params: name (string): The full name of the post to save (name parameter
     *                        in the getSubscriptions() return value)
     *         shareFrom (string): The name of the person sharing the story
     *         replyTo (string): The e-mail the sharee should respond to
     *         shareTo (string): The e-mail the story should be sent to
     *         message (string): The e-mail message
     **************************************************************************/
    public function sharePost($name, $shareFrom, $replyTo, $shareTo, $message){
        $urlShare = "{$this->apiHost}/share";
        $postData = sprintf("parent=%s&share_from=%s&replyto=%s&share_to=%s&message=%s&uh=%s",
                            $name,
                            $shareFrom,
                            $replyTo,
                            $shareTo,
                            $message,
                            $this->modHash);
        
        $response = $this->runCurl($urlShare, $postData);
        return $response;
    }
    
    /***************************************************************************
     * Function: Add New Comment
     * Description: Add a new comment to a story
     * API: https://github.com/reddit/reddit/wiki/API%3A-comment
     * Params: name (string): The full name of the post to save (name parameter
     *                        in the getSubscriptions() return value)
     *         text (string): The comment markup
     **************************************************************************/
    public function addComment($name, $text){
        $response = null;
        if ($name && $text){
            $urlComment = "{$this->apiHost}/comment";
            $postData = sprintf("thing_id=%s&text=%s&uh=%s",
                                $name,
                                $text,
                                $this->modHash);
            $response = $this->runCurl($urlComment, $postData);
        }
        return $response;
    }
    
    /***************************************************************************
     * Function: Vote on a story
     * Description:
     * API: https://github.com/reddit/reddit/wiki/API%3A-vote
     * Params: name (string): The full name of the post to save (name parameter
     *                        in the getSubscriptions() return value)
     *         vote (number): The vote to be made (1 = upvote, 0 = no vote,
     *                        -1 = downvote)
     **************************************************************************/
    public function addVote($name, $vote = 1){
        $response = null;
        if ($name){
            $urlVote = "{$this->apiHost}/vote";
            $postData = sprintf("id=%s&dir=%s&uh=%s", $name, $vote, $this->modHash);
            $response = $this->runCurl($urlVote, $postData);
        }
        return $response;
    }
    
    /***************************************************************************
     * Function: Set Flair
     * Description: Set or clear a user's flair in a subreddit
     * API: https://github.com/reddit/reddit/wiki/API%3A-flair
     * Params: subreddit (string): The subreddit to use
     *         user (string): The name of the user
     *         text (string): Flair text to assign
     *         cssClass (string): CSS class to assign to the flair text
     **************************************************************************/
    public function setFlair($subreddit, $user, $text, $cssClass){
        $urlFlair = "{$this->apiHost}/flair";
        $postData = sprintf("r=%s&name=%s&text=%s&css_class=%s&uh=%s",
                            $subreddit,
                            $user,
                            $text,
                            $cssClass,
                            $this->modHash);
        $response = $this->runCurl($urlFlair, $postData);
        return $response;
    }
    
    /***************************************************************************
     * Function: Get Flair List
     * Description: Download the flair assignments of a subreddit
     * API: https://github.com/reddit/reddit/wiki/API%3A-flairlist
     * Params: subreddit (string): The subreddit to use
     *         limit (number): The maximum number of items to return (max 1000)
     *         after (string): Return entries starting after this user
     *         before (string): Return entries starting before this user 
     **************************************************************************/
    public function getFlairList($subreddit, $limit = 100, $after, $before){
        $urlFlairList = "{$this->apiHost}/share";
        $postData = sprintf("r=%s&limit=%s&after=%s&before=%s&uh=%s",
                            $subreddit,
                            $limit,
                            $after,
                            $before,
                            $this->modHash);
        $response = $this->runCurl($urlFlairList, $postData);
        return $response;
    }
    
    /***************************************************************************
     * Function: Set Flair CSV File
     * Description: Post a CSV file of flair settings to a subreddit
     * API: https://github.com/reddit/reddit/wiki/API%3A-flaircsv
     * Params: subreddit (string): The subreddit to use
     *         flairCSV (string): CSV file contents, up to 100 lines
     **************************************************************************/
    public function setFlairCSV($subreddit, $flairCSV){
        $urlFlairCSV = "{$this->apiHost}/flaircsv.json";
        $postData = sprintf("r=%s&flair_csv=%s&uh=%s",
                            $subreddit,
                            $flairCSV,
                            $this->modHash);
        $response = $this->runCurl($urlFlairCSV, $postData);
        return $response;
    }
    
    /***************************************************************************
     * Function: cURL Request
     * Description: General cURL request function for GET and POST 
     * Params: url (string): URL to be requested
     *         postVals (NVP string): NVP string to be send with POST request
     **************************************************************************/
    private function runCurl($url, $postVals = null){
        $ch = curl_init($url);
        
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => "reddit_session={$this->session}",
            CURLOPT_TIMEOUT => 3
        );
        
        if ($postVals != null){
            $options[CURLOPT_POSTFIELDS] = $postVals;
            $options[CURLOPT_CUSTOMREQUEST] = "POST";  
        }
        
        curl_setopt_array($ch, $options);
        
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        
        return $response;
    }
}
?>