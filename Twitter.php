<?php

Class Twitter {
  var $conn;

  public function Twitter(){
    require_once 'twitteroauth/twitteroauth.php';
    $this->conn = new TwitterOAuth(
				   CONSUMER_KEY, CONSUMER_SECRET,
				   ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
  }

  public function post($tweet_text){
    $params = array('status' => $tweet_text);
    $this->conn->post('statuses/update',$params);
  }
}