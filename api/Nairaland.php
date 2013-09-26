<?php
/**
 * This file contains the Nairaland class
 *
 * @package api
 * @author Elvis Chidera <Elvis.chidera@gmail.com>
*/
  include("config.php");
  include("Read.php");

/**
 * This class contains all the methods used to access Nairaland
 *
 * @package api
 * @author Elvis Chidera <Elvis.chidera@gmail.com>
*/
  class Nairaland {

  private $username = NULL;
  private $errorMsg = NULL;

/**
 * sets the username
 *
 * @param string $username
*/
     function __construct($username) {
$this->username = $username;
}

/**
 * Arranges an array in a key=value pair for a POST request
 *
 * @param array $fields containing the values
 * @return string
*/
     public function postData($fields) {
$postdata = '';
foreach($fields as $key => $value) {
$postdata .= $key.'='.$value.'&';
}
return rtrim($postdata, '&');
}

/**
 * Login into a Nairaland account
 *
 * The username given to the constructor will be used with the provided password to login
 * @param string $password
 * @param string $redirect (optional), the url to redirect to after a successfull login
 * @return string|bool the redirected page on success or FALSE on error.
*/
     public function login($password, $redirect = '/') {
if(empty($this->username) || empty($password)) {
return $this->setErrorMsg('Empty username and/or password');
}
$fields = array('name' => urlencode($this->username),
'password' => urlencode($password), 'redirect' => urlencode($redirect));
return $this->send(LOGINURL, ($this->postData($fields)));
}

/**
 * edits the profile of the currently logged in user.
 *
 * @param int $birthDay
 * @param string $birthMonth
 * @param int $birthYear
 * @param string $gender
 * @param string $personalText
 * @param string $signature
 * @param string $websiteTitle
 * @param string $websiteUrl
 * @param string $location
 * @param string $yim
 * @ param string $twitter
 * @param bool $removeAvatar
 * @param string $avatar the path to the image.
 * @return string|bool FALSE if no active session is found
*/
     public function editProfile($birthDay, $birthMonth, $birthYear, $gender, $personalText, $signature, $websiteTitle, $websiteUrl, $location, $yim, $twitter, $removeAvatar = FALSE, $avatar = FALSE) {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
$fields = array('birthday' => $birthDay, 'birthmonth' => $birthMonth, 'birthyear' => $birthYear, 'gender' => $gender, 'personaltext' => $personalText, 'signature' => $signature, 'websitetitle' => $websiteTitle, 'websiteurl' => $websiteUrl, 'location' => $location, 'yim' => $yim, 'twitter' => $twitter, 'session' => $cookie, 'member' => $this->username);
if($removeAvatar) {
$fields['removeavatar'] = 1;
}
if($avatar !== FALSE) {
$fields['avatar'] = '@'.$avatar;
}
return $this->send(EDITPROFILEURL, $fields);
}
/**
 * changes the Email of the current logged in user.
 *
 * @param string $password
 * @param string $newEmail
 * @return string|bool FALSE if there is no active session
*/
     public function changeEmail($password, $newEmail) {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
$fields = array('password' => $password, 'email' => $newEmail, 'email2' => $newEmail, 'session' => $cookie);
return $this->send(EDITEMAILURL, ($this->postData($fields)));
}

/**
 * changes the Password of the current logged in user.
 *
 * @param string $oldPassword
 * @param string $newPassword
 * @return string|bool FALSE if there is no active session
*/
     public function changePassword($oldPassword, $newPassword) {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
$fields = array('oldpassword' => $oldPassword, 'password' => $newPassword, 'password2' => $newPassword, 'session' => $cookie);
return $this->send(EDITPASSWORDURL, ($this->postData($fields)));
}

/**
 * Logs out the current logged in user.
 *
 * @return string|bool FALSE if there is no active session
*/
     public function logout() {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
unlink(COOKIEFILE);
return $this->get((LOGOUTURL.'?session='.$cookie));
}

/**
 * Remove the unwanted part when getting frontpage threads, board threads and threads posts.
 * @param string $data
 * @param string $for
 * @return string
*/
     private function stripHead($data, $for) {
switch($for) {
case 'THREADS':
$start = strpos($data, '<img src="/icons/');
$last = strpos($data, '</table>', $start);
return substr($data, $start, $last - $start);
break;
case 'FRONTPAGE':
$start = strpos($data, '»');
$end = strpos($data, '<td>', $start);
return substr($data, $start, $end - $start);
break;
case 'POSTS':
$start = strpos($data, '<table summary="posts">');
$end = strpos($data, '</table>', $start);
return substr($data, $start, $end - $start);
break;
}
}

/**
 * gets the threads on nairaland frontpage
 *
 * @param string $data the content of the whole page including html
 * @return Read
 */
     public function frontPage($data) {
if(empty($data)) { return $data;
}
$data = $this->stripHead($data, 'FRONTPAGE');
$Read = new Read;
$i = 0;
$dom = new DOMDocument;
libxml_use_internal_errors(true);
$dom->loadHTML($data);
//get each <a> tag
//there are 64 threads on frontpage
foreach($dom->getElementsByTagName('a') as $node) {
if($i > 64) {
break;
}
$Read->addElement('link', $node->getAttribute('href'));
$Read->addElement('title', $node->nodeValue);
++$i;
}
return $Read;
}

/**
 * get the threads and their corresponding attributes from a BOARD
 *
 * @param string $data the content of the board
 * @return Read
 */
     public function threads($data) {
if(empty($data)) {
return $data;
}
$data = $this->stripHead($data, 'THREADS');
$keys = array('author', 'posts', 'views', 'date', 'lastposter');
$data = explode('<tr><td class="', strip_tags($data, '<b><a><tr><td>'));
$Read = new Read;
$dom = new DOMDocument;
libxml_use_internal_errors(true);
$first = TRUE;
foreach($data as $value) {
$dom->loadHTML($value);
$i = 0;
foreach($dom->getElementsByTagName('a') as $node)
{
if($i == 1 || $first) {
$Read->addElement('link', $node->getAttribute('href'));
$Read->addElement('title', $node->nodeValue);
$first = FALSE;
break;
}
++$i;
}
$k = 0;
$m = 0;
foreach($dom->getElementsByTagName('b') as $node)
{
if($k < 1) {
++$k;
continue;
}
else if($k > 6) {
break;
}
if($m >= 4) {
$m = 4;
if($this->check($node->nodeValue)) {
$Read->appendElement('date', ' '.$node->nodeValue);
}
else {
$Read->addElement($keys[$m], $node->nodeValue);
}
}
else {
$Read->addElement($keys[$m], $node->nodeValue);
++$m;
}
++$k;
}
}
return $Read;
}

/**
 * check if date is in thread attribute
 *
 * @param string $s the attribute value
 * @return bool
*/
     private function check($s) {
if(strtotime($s) && 1 === preg_match('~[0-9]~', $s)) {
  return TRUE;
}
else {
  return FALSE;
}
}

/**
 * get the posts in a thread and their corresponding attribute
 *
 * @param string $data the contents of the thread (posts)
 * @return Read
*/
     public function readPosts($data) {
if(empty($data)) {
return $data;
}
$data = $this->stripHead($data, "POSTS");
$Read = new Read;
$data = explode('<tr><td class="bold l pu">', $data);
$dom = new DOMDocument;
libxml_use_internal_errors(true);
$t = 0;
foreach($data as $value) {
if($t == 0) {
++$t;
continue;
}
$dom->loadHTML($value);
$g = 0;
foreach($dom->getElementsByTagName('a') as $node)
{
if($g == 0) {
$Read->addElement('id', $node->getAttribute('name'));
}
else if($g == 4) {
$Read->addElement('poster', $node->nodeValue);
break;
}
++$g;
}
foreach($dom->getElementsByTagName('span') as $node)
{
if($node->getAttribute('class') == 's') {
$Read->addElement('date', $node->nodeValue);
break;
}
}
foreach($dom->getElementsByTagName('div') as $node)
{
if($node->getAttribute('class') == 'narrow') {
$Read->addElement('post', $node->nodeValue);
break;
}
}
$down = explode('<p class=', $value);
$bottom = $down[1];
$dom->loadHTML($bottom);
foreach($dom->getElementsByTagName('b') as $node)
{
$likes = $node->nodeValue;
break;
}
$Read->addElement('likes', (isset($likes) ? $likes : 'No Likes'));
unset($likes);
foreach($dom->getElementsByTagName('img') as $node)
{
if(isset($attachment)) {
$attachment .= $node->getAttribute('src').',';
}
else {
$attachment = $node->getAttribute('src').',';
}
}
$Read->addElement('attachment', (isset($attachment) ? rtrim($attachment, ',') : 'No Attachment'));
unset($attachment);
}
return $Read;
}

/**
 * Creates a thread
 *
 * @param string $title
 * @param string $body
 * @param string $board the board name
 * @param string $attachment path to attachment
 * @return string|bool the contents of the thread or FALSE on error
*/
     public function createThread($title, $body, $board, $attachment = FALSE) {
if(strlen($title) > 80) {
return $this->setErrorMsg('Thread title length is greater than 80');
}
if(($board = $this->getBoardId($board)) === FALSE) {
return $this->setErrorMsg('Invalid board name');
}
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
$fields = array('title' => $title, 'body' => $body, 'session' => $cookie, 'board' => $board);
if($attachment !== FALSE) {
$fields['attachment'] = '@'.$attachment;
}
return $this->send(CREATETHREADURL, $fields);
}

/**
 * sends data to a url using the POST method
 *
 * @param string $url
 * @param string|array $data data to send
 * @return string
*/
     private function send($url, $data) {
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
curl_setopt($ch, CURLOPT_COOKIEJAR,  COOKIEFILE);
curl_setopt($ch, CURLOPT_USERAGENT,  USERAGENT);
curl_setopt($ch,
CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$result = curl_exec($ch);
curl_close($ch);
return $result;
}

/**
 * send data to a url using the GET method or request a url
 *
 * @param string $url
 * @return string
*/
     public function get($url) {
if(empty($url)) {
return $this->setErrorMsg('No url parameter passed to the get($url) method');
}
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIEFILE);
curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEFILE);
curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
$result = curl_exec($ch);
curl_close($ch);
return $result;
}

/**
 * sets a cookie using contents of a netscape cookie generated by curl or gotten from getCookie method
 *
 * @param string $cookie
 * @return bool FALSE on error or TRUE on success
 */
     public function setCookie($cookie) {
if(is_writable(COOKIEFILE)) {
unlink(COOKIEFILE);
}
$f = fopen(COOKIEFILE, 'w');
if($f === FALSE) {
return $this->setErrorMsg('Unable to load cookie file');
}
fwrite($f, $cookie);
fclose($f);
return TRUE;
}

/**
 * gets the cookie of the current user from the cookie file stored by curl
 *
 * @return string|bool cookie on success or FALSE on error
*/
     public function getCookie() {
if(is_readable(COOKIEFILE)) {
return file_get_contents(COOKIEFILE);
}
else {
return $this->setErrorMsg('Unable to read cookie file');
}
}

/**
 * extract the cookies in a netscape file generated by curl
 *
 * @param string $string the file contents
 * @return array the cookie values
*/
     public function extractCookies($string) {
$cookies= array();    
    $lines= explode("\n",$string); 
foreach($lines as $line){ 
if(isset($line[0]) && substr_count($line,"\t") == 6) { 
$tokens = explode("\t",$line); 
$tokens = array_map('trim',$tokens);
$cookie= array(); 
$cookie['domain'] = $tokens[0];     $cookie['flag'] = $tokens[1];
$cookie['path'] = $tokens[2];  $cookie['secure'] = $tokens[3];
$cookie['expiration'] = date('Y-m-d h:i:s',$tokens[4]); 
$cookie['name'] = $tokens[5];     $cookie['value'] = $tokens[6];
$cookies[]=$cookie;
}
}
return$cookies;
}

/**
 * gets username
 *
 * @return string
*/
     public function getUsername() {
return $this->username;
}

/**
 * sets the username
 *
 * @param string $username
*/
     public function setUsername($username) {
$this->username = $username;
}

/**
 * gets the value of a cookie from a netscape cookie file generated by curl
 *
 * @param string $name the cookie name
 * @return string|bool cookie value on success or FALSE on error
 */
     public function getCookieValue($name) {
$cookie = $this->getCookie();
$cookie = $this->extractCookies($cookie);
if($name == 'session') {
return $cookie[1]['value'];
}
else if($name == 'member') {
return $cookie[0]['value'];
}
else {
return $this->setErrorMsg('Invalid cookie name');
}
}

/**
 * gets the id of a nairaland board
 *
 * @param string $board
 * @return string|bool the board id on success or FALSE on error
 */
     private function getBoardId($s) {
$nairaland_boards = array('investment' => 81, 'technology' => 8, 'programming' => 34, 'software_programmer_market' => 76, 'webmasters' => 30, 'web_market' => 52, 'computers' => 22, 'computer_market' => 74, 'phones' => 16, 'phone_internet_market' => 75, 'graphics_video' => 45, 'graphics_video_market' => 51, 'technology_market' => 54, 'entertainment' => 12, 'jokes' => 15, 'tv_movies' => 4, 'satelite_tv_tech' => 58, 'music_radio' => 3, 'rap_battles' => 60, 'music_business' => 59, 'celebrities' => 46, 'fashion' => 37, 'fashion_clothing_market' => 39, 'events' => 7, 'sports' => 14, 'european_football' => 66, 'gaming' => 10, 'video_games_and_gadgets_for_sale' => 71, 'forum_games' => 33, 'literature_writing' => 11, 'poems_for_review' => 36, 'pictures' => 81, 'nairaland_general' => 9, 'foreign_affairs' => 61, 'ethnic_racial_or_sectarian_politics' => 40, 'violent_disgusting_non_celebrity_crimes' => 1, 'romance' => 21, 'dating_and_meeting_zone' => 38, 'business' => 24, 'business_to_business' => 49, 'adverts' => 32, 'jobs_vacancies' => 29, 'career' => 35, 'certification_and_training_adverts' => 62, 'nysc' => 79, 'education' => 13, 'educational_services' => 57, 'autos' => 26, 'cartalk' => 78, 'properties' => 47, 'health' => 19, 'travel' => 2, 'travel_ads' => 77, 'family' => 5, 'culture' => 55, 'religion' => 17, 'islam_for_muslims' => 44, 'food' => 41, 'nairaland_ads' => 80);
return isset($nairaland_boards[$s]) ? $nairaland_boards[$s] : FALSE;
}

/**
 * sets an error message
 *
 * @param string $errormsg the error that occured
 * @return bool FALSE
*/
     private function setErrorMsg($errormsg) {
$this->errorMsg = $errormsg;
return FALSE;
}

/**
 * gets the most recent error message
 *
 * @return string
 */
     public function getErrorMsg() {
return $this->errorMsg;
}

/**
 * follows a nairaland board
 *
 * @param string $board board name
 * @param string $redirect the url to redirect to
 * @return string|bool the redirected page on success or FALSE on error
*/
     public function followBoard($board, $redirect) {
if(($board = $this->getBoardId($board)) === FALSE) {
return $this->setErrorMsg('Invalid nairaland board');
}
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
return $this->get((FOLLOWBOARDURL.'?board='.$board.'&redirect='.$redirect.'&session='.$cookie));
}

/**
 * Unfollow a nairaland board
 *
 * @param string $board board name
 * @param string $redirect the url to redirect to
 * @return string|bool the redirected page on success or FALSE on error
*/
     public function unFollowBoard($board, $redirect) {
if(($board = $this->getBoardId($board)) === FALSE) {
return $this->setErrorMsg('Invalid nairaland board');
}
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
return get((UNFOLLOWBOARDURL.'?board='.$board.'&redirect='.$redirect.'&session='.$cookie));
}

/**
 * reports a post
 *
 * @param string $postId the id of the post
 * @param string $reason the reason for reporting
 * @param string $redirect the url to redirect to
 * @return string|bool the redirected page on success or FALSE on error
 */
     public function reportPost($postId, $reason, $redirect) {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
$fields = array('reason' => $reason, 'session' => $cookie, 'post' => $postId);
return $this->send(REPORTURL, $this->postData($fields));
}

/**
 * replys a thread
 *
 * @param string $topic the thread id
 * @param string $title the thread name
 * @param string $body the post
 * @param string $attachment (optional)
 * @param string $check (optional) "on" to follow thread "off" not to follow
 * @return string|bool the thread posts on success or FALSE on error
 */
     public function reply($topic, $title, $body, $attachment = FALSE, $follow = 'on') {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
$max_post = $this->attribute(NEWPOSTURL.'?topic='.$topic, 'max_post');
$fields = array('topic' => $topic, 'title' => $title, 'body' => $body, 'session' => $cookie, 'max_post' => $max_post, 'follow' => $follow);
if($attachment !== FALSE) {
$fields['attachment'] = '@'.$attachment;
}
return $this->send(REPLYURL, $fields);
}

/**
 * used to fetch the value attribute of an <input> tag
 *
 * @param string $url
 * @param string $s the attribute to get
 * @return string the value attribute of the input tag
 */
     private function attribute($url, $s) {
$result = $this->get($url);
$dom = new DOMDocument;
libxml_use_internal_errors(true);
$dom->loadHTML($result);
foreach($dom->getElementsByTagName('input') as $node)
{
if($node->getAttribute('name') == 'max_post') {
return $node->getAttribute('value');
break;
}
}
}

/**
 * likes a post
 *
 * @param string $postId the post id
 * @param string $redirect the url to redirect to
 * @return string|bool the redirected page on success or FALSE on error
*/
     public function likePost($postId, $redirect) {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
return $this->get((LIKEURL.'?redirect='.urlencode($redirect).'&session='.$cookie.'&post='.$postId));
}

/**
 * gets a thread id by its link
 *
* @return string
 */
     public function getThreadId($link) {
if(!empty($link)) {
$id = explode('/', $link);
return $id[1];
}
}

/**
 * follow a thread
 *
 * @param string $ThreadId
 * @param string $redirect the url to redirect to
 * @return string|bool the redirected page on success or FALSE on error
*/
     public function followThread($threadId, $redirect){
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
return $this->get((FOLLOWTHREADURL.'?redirect='.$redirect.'&session='.$cookie.'&topic='.$threadId));
}

/**
 * check if a thread has more pages used for pagination.
 *
 * @param string $result the thread raw contents
 * @param string $current the current page number
 * @return bool|int FALSE if thread don't have more page or next page value if it does */
     public function hasNextPage($result, $current) {
$pos = strpos($result, '(0)');
$next = $current + 1;
$has = strpos($result, '('.$next.')', $pos);
if($has !== FALSE) {
if(($has - $pos) > 100) {
$has = FALSE;
}
}
return $has !== FALSE ? $next : FALSE;
}

/**
 * gets the thread of a user
 *
 * @param string $data
 * @return Read
*/
     private function getUserThreads($data) {
$Read = new Read;
if(empty($data)) {
return NULL;
}
$needle ='<tr><td class="';
$keys = array('author', 'posts', 'views', 'date', 'lastposter');
$data = explode($needle, strip_tags($data,
'<b><a><tr><td>'));
$dom = new DOMDocument;
libxml_use_internal_errors(true);
foreach($data as $value) {
$dom->loadHTML($value);
$i = 0;
foreach($dom->getElementsByTagName('a') as $node) {
if($i == 2) {
$Read->addElement('link', $node->getAttribute('href'));
$Read->addElement('title', $node->nodeValue);
break;
}
++$i;
}
$k = 0;
$m = 0;
foreach($dom->getElementsByTagName('b') as $node)
{
if($k < 2){
++$k;
continue;
}
else if($k > 7) {
break;
}
if($m >= 4) {
$m = 4;
if($this->check($node->nodeValue)) {
$Read->appendElement('date', ' '.$node->nodeValue);
}
else {
$Read->addElement($keys[$m], $node->nodeValue);
}
}
else {
$Read->addElement($keys[$m], $node->nodeValue);
++$m;
}
++$k;
}
}
return $Read;
}

/*
 * gets a user threads and profile information
 *
 * @param string $data
 * @return array containing two objects of the Read class
*/
     public function viewProfile($data) {
if(empty($data)) {
return $data;
}
if(strpos($data, '<tr><td class="w">') !== FALSE) {
$needle ='<tr><td class="w">';
$start = strpos($data, $needle) + strlen($needle);
$needle2 = '<table>';
$end = strpos($data, $needle2, $start) + strlen($needle2);
$head = substr($data, $start, $end - $start);
$needle ='<table>';
$pos = strpos($data, $needle) + strlen($needle);
$bottom = substr($data, $pos);
}
else {
$head = '';
$needle = '<table>';
$pos = strpos($data, $needle) + strlen($needle);
$bottom = substr($data, $pos);
}
$user = array();
$user['threads'] = $this->getUserThreads($head);
$Read = new Read;
$dom = new DOMDocument;
libxml_use_internal_errors(true);
$dom->loadHTML($bottom);
foreach($dom->getElementsByTagName('a') as $node) {
if(strpos($node->getAttribute('href'), 'follow') !== FALSE) {
$Read->addElement('follow', $node->getAttribute('href'));
break;
}
}
$dom->loadHTML($data);
foreach($dom->getElementsByTagName('img') as $node) {
if(strpos($node->getAttribute("src"), 'avatar') !== FALSE) {
$Read->addElement('avatar', $node->getAttribute('src'));
break;
}
}
$pos = strpos($bottom, '<b>');
$getProfile = substr($bottom, $pos);
$j = 0;
$dom->loadHTML($getProfile);
$details = '';
foreach($dom->getElementsByTagName('p') as $node) {
$detail = $node->nodeValue;
if(strpos($detail, ':') !== FALSE && strpos($detail, 'Sections:') === FALSE && strpos($detail, 'Links:') === FALSE) {
++$j;
if($j > 10) {
break;
}
$details .= $node->nodeValue.'<br>';
}
}
$Read->addElement('details', $details);
$pos = strpos($bottom, '<table summary="friends">');
$end = strpos($bottom, '</table>', $pos);
if($pos !== FALSE && $end !== FALSE) {
$friends = substr($bottom, $pos, $end- $pos);
$dom->loadHTML($friends);
$following = '';
foreach($dom->getElementsByTagName('a') as $node){
$following .= $node->nodeValue.',';
}
$Read->addElement('following', rtrim($following, ','));
}
else {
$Read->addElement('following', 'No friends');
}
$user['profile'] = $Read;
return $user;
}

/**
 * sends an email to a user
 * @param string $recipient the username
 * @param string $subject
 * @param string $body
 * @return bool|string FALSE on error
*/
 
     public function sendEmail($recipient, $subject, $body) {
$cookie = $this->getCookieValue('session');
if($cookie === FALSE) {
return $this->setErrorMsg('Empty cookie');
}
$fields = array('session' => $cookie, 'recipient_name' => $recipient, 'subject' => $subject, 'body' => $body);
$fields = $this->postData($fields);
return $this->send(EMAILURL, $fields);
}

/**
 * Deletes the stored cookie file
 *
 * This method is called when the script stop executing
 * It deletes the cookie file produced by curl
 * So the cookie is not accessed by another user
*/
     function __destruct() {
if(is_writable(COOKIEFILE)) {
unlink(COOKIEFILE);
}
}

}
?>