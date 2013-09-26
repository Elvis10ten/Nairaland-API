<?php
/*
This is a very simple one page demo showing the use of the Nairaland api.
The page knows what to do next by the action value that is sent via the GET method
The link to work on is also passed through the GET method
Other neccesary information are also passed as GET request to make everything to work in one file.
Look more closely at the while loop with the Read class fetch method in its conditional part and also watch the Elements keys being used.
All major methods are used.
*/
//reports all errors
error_reporting(E_ALL);
//the time the script starts
$timeStart = time();
session_start();
//include the api
include("../api/Nairaland.php");
//checks if a username is given, if yes store it in the session
if(isset($_GET['username'])) {
$_SESSION['username'] = $_GET['username'];
}
//checks if a password is given, if yes store it in the session
if(isset($_GET['password'])) {
$_SESSION['password'] = $_GET['password'];
}
//if a session exist store it else store the default value 'grandtheft'
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'grandtheft';
//if a session exist store it else store the default 'test'
$password = isset($_SESSION['password']) ? $_SESSION['password'] : 'test';
//Instantiate the Nairaland class
$nl = new Nairaland($username);
//This is a simple demo so only login is called
//but it is better to also use setCookie
$result = $nl->login($password);
//checks for error
if($result === FALSE) {
die($nl->getErrorMsg());
}
//We are going to be using only one file as the demo
//so we know what to do next by a GET variable 'action'
if(!isset($_GET['action'])) {
//echoes a link to your profile
echo '<a href="demo.php?action=PROFILE&link='.urlencode('http://www.nairaland.com/'.$nl->getUsername()).'">My profile</a><br>';
//echoes a link to logout
echo '<a href="demo.php?action=LOGOUT">Logout '.$nl->getUsername().'</a>';
//echoes a link to the 'programming' board
echo '<br> <a href="demo.php?action=BOARD&link='.urlencode('http://www.nairaland.com/programming').'"><b>PROGRAMMING</b></a><br><hr>';
//calls the frontPage method and get an object of the Read class
$Read = $nl->frontPage($result);
//iterate through each Element of the returned object of the Read class
//the fetch methods returns an array of the Elements or FALSE if there are no more Elements
while(($row = $Read->fetch())) {
echo '<a href="demo.php?action=POSTS&title='.urlencode($row['title']).'&link='.urlencode($row['link']).'">'.$row['title'].'</a><hr>';
}
}
else if($_GET['action'] == 'PROFILE') {
//view a profile
//gets the contents of the profile
$result = $nl->get($_GET['link']);
//calls the viewProfile method, the $result above is passed as a parameter
//it returns an array containing two objects of the Read class
$profile = $nl->viewProfile($result);
//check if the first array 'threads' value is NULL
if(isset($profile['threads'])) {
//gets the value of the first array
$threads = $profile['threads'];
//iterate through each Element
while(($row = $threads->fetch())) {
echo '<a href="demo.php?action=POSTS&title='.urlencode($row['title']).'&link='.urlencode('http://www.nairaland.com'.$row['link']).'" style="color:green; display:block;">'.$row['title'].'</a> <b>by</b> <a href="demo.php?action=PROFILE&link='.urlencode('http://nairaland.com/'.$row['author']).'">'.$row['author'].'</a> '.$row['views'].' views <br> '.$row['posts'].' posts '.$row['date'].' <a href="demo.php?action=PROFILE&link='.urlencode('http://nairaland.com/'.$row['lastposter']).'">'.$row['lastposter'].'</a><hr>';
}
}
//gets the second array value
$info = $profile['profile'];
//iterate through each Element
while(($row = $info->fetch())) {
echo (isset($row['avatar']) ? '<img src="'.$row['avatar'].'"><br>' : 'No profile pics. <br>');
echo (isset($row['follow']) ? '<a href="demo.php?action=GET&link='.urlencode($row['follow']).'"><b>Follow Me</b></a><br>' : '');
echo '<b>MY DETAILS</b><br> '.$row['details'].'<br> <b>FRIENDS:</b> <br> '.$row['following'];
}
}
else if($_GET['action'] == "LOGOUT") {
//logs out
$nl->logout();
//request new login details
echo '<b>LOGIN</b><br> <form action="demo.php" method="GET"> <input type="text" name="username"> <br> <input type="text" name="password"> <input type="submit" value="Login"> </form>';
}
else if($_GET['action'] == "BOARD") {
//view a Nairaland board
//gets the board content and pass it to the threads method
$Read = $nl->threads($nl->get($_GET['link']));
//a link to create thread and follow board  
echo '<a href="demo.php?action=CREATETHREAD&board=programming">CREATE NEW THREAD</a> | <a href="demo.php?action=FOLLOWBOARD&board=programming">Follow Board</a><br>';
//iterate through each element
while(($row = $Read->fetch())) {
echo '<a href="demo.php?action=POSTS&title='.urlencode($row['title']).'&link='.urlencode('http://www.nairaland.com'.$row['link']).'" style="color:green; display:block;">'.$row['title'].'</a> <b>by</b> <a href="demo.php?action=PROFILE&link='.urlencode('http://nairaland.com/'.$row['author']).'">'.$row['author'].'</a> '.$row['views'].' views <br> '.$row['posts'].' posts '.$row['date'].' <a href="demo.php?action=PROFILE&link='.urlencode('http://nairaland.com/'.$row['lastposter']).'">'.$row['lastposter'].'</a><hr>';
}
}
else if($_GET['action'] == "GET") {
//sends data to a url with the GET method or request a url
$data = $nl->get($_GET['link']);
echo "DONE";
}
else if($_GET['action'] == "FOLLOWBOARD") {
//follows a Nairaland board
$nl->followBoard($_GET["board"], "http://nairaland.com/programming");
echo "followed";
}
else if($_GET['action'] == "CREATETHREAD") {
//a form to input thread information
echo '<form method="GET" action="demo.php"> <input type="hidden" name="action" value="CREATETHREAD2"> <input type="hidden" name="board" value="'.$_GET["board"].'"> <b>THREAD TITLE</b><br> <input type="text" name="title"><br> <input type="text" name="body"> <br> <input type="submit" value="Create"> </form>';
}
else if($_GET['action'] == "CREATETHREAD2") {
//creates a thread by calling the createThread method
$result = $nl->createThread($_GET[ "title"], $_GET["body"], $_GET["board"]);
echo "Thread created";
}
else if($_GET['action'] == 'POSTS') {
//view a thread posts
//gets the thread content
$data = $nl->get($_GET['link']);
//used for pagination
//check if any previously pagination details was stored
if(isset($_SESSION['link'])) {
//checks if the previously stored pagination link is related to the current link
if($_SESSION['link'] != substr($_GET['link'], 0, strlen($_SESSION['link']))) {
$_SESSION['link'] = $_GET['link'];
$_SESSION['cur'] = 0;
}
}
else {
//if no previously pagination details was stored, store them
$_SESSION['link'] = $_GET['link'];
$_SESSION['cur'] = 0;
}
//checks if there are more pages to the thread
//the method returns FALSE if there are no more pages or the next page value if there are more pages
$_SESSION['cur'] = $nl->hasNextPage($data, $_SESSION['cur']);
if($_SESSION['cur'] !== FALSE) {
echo '<a href="demo.php?action=POSTS&link='.urlencode($_SESSION['link'].'/'.$_SESSION['cur']).'&title='.urlencode($_GET['title']).'">next page('.$_SESSION['cur'].')</a> <b>|</b> ';
}
$link2 = $_GET['link'];
//removes the http:// from the link so it can be used by getThreadId method
$_GET['link'] = substr($_GET['link'], 8);
//call the readPost method which returns an object of the Read class
$Read = $nl->readPosts($data);
//echoes a link to reply to a thread
echo '<a href="demo.php?action=REPLY&topic='.$nl->getThreadId($_GET['link']).'&title='.$_GET['title'].'">Reply</a><hr>';
//iterate through each Element
while(($row = $Read->fetch())) {
echo '<a href="demo.php?action=PROFILE&link='.urlencode('http://www.nairaland.com/'.$row['poster']).'">'.$row['poster'].'</a> | '.$row['date'].' <div> '.$row['post'].'</div> <b>'.$row['likes'].'</b> <br>'.$row['attachment'];
//echoes a link to link post
echo '<br> <a href="demo.php?action=LIKE&postId='.$row['id'].'&redirect='.urlencode($link2).'">Like this</a>';
echo '<hr>';
}
}
else if($_GET['action'] == "LIKE") {
//likes a post
$result = $nl->likePost($_GET["postId"], $_GET["redirect"]);
echo "LIKED";
}
else if($_GET['action'] == "REPLY") {
//echoes a form to input the reply
echo '<form method="GET" action="demo.php"> <input type="hidden" name="topic" value="'.$_GET["topic"].'"> <input type="hidden" name="title" value="'.$_GET["title"].'"> <input type="hidden" name="action" value="REPLY2"> <b>POST:</b><br> <input type="text" name="body"> <br> <input type="submit" value="Post"> </form>';
}
else if($_GET['action'] == "REPLY2") {
//replies to a thread with the info gotten from the reply form
$posted = $nl->reply($_GET["topic"], $_GET["title"], $_GET["body"]);
echo "posted";
}
//the time the script stop executing
$timeEnd = time();
$time = ($timeEnd - $timeStart) / 1000;
//echoes the time taken to run the code
echo $time;
?>