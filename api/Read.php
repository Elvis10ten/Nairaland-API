<?php
/**
 * This File contains the Read class
 *
 * @package api
 * @author Elvis Chidera <Elvis.Chidera@gmail.com>
*/

/**
 * Used to store and retrieve values
 *
 * @package api
 * @author Elvis Chidera <Elvis.Chidera@gmail.com>
*/
  class Read {

  private $data = array();
/**
 * keys of all the element(s) stored
 * @var array $keys
*/
  public $keys = array();
  private $pointer = 0;
  private $count = NULL;

/**
 * Adds an element
 *
 * @param string $key
 * @param string $value
*/
     public function addElement($key, $value) {
  $this->data[$key][] = $value;
  if(!isset($keys[$key])) {
  $this->keys[$key] = 0;
 }
 }

/**
 * Appends an element to the lastly added element with that key
 *
 * @param string $key
 * @param string $value
*/
     public function appendElement($key, $value) {
  end($this->data[$key]);
  $last = key($this->data[$key]);
  $this->data[$key][$last] .= $value;
  reset($this->data[$key]);
 }

/**
 * Gets the element added, one at a time.
 *
 * @return array|bool FALSE if there are no more elements to return
*/
     public function fetch() {
$temp = array();
if(!isset($this->count)) {
foreach($this->data as $value) {
$this->count = count($value);
break;
}
}
if($this->pointer < $this->count) {
foreach($this->keys as $key => $value) {
$temp[$key] = $this->data[$key][$this->pointer];
}
++$this->pointer;
return $temp;
}
else {
return FALSE;
}
}

/**
 * Sets the internal element pointer used for getting elements
 *
 * @param int $value
 * @return bool FALSE on error
*/
     public function setPointer($value) {
  if($i < count($this->data)) {
  $this->pointer = $i;
 }
  else {
  return FALSE;
 }
 }

/**
 * Gets the internal element pointer
 *
 * @return int
*/
     public function getPointer() {
  return $this->pointer;
}

/**
 * Gets the number of elements added
 *
 * @return int
*/
     public function numElements() {
  return count($this->data);
 }

 }
?>