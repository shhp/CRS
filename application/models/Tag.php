<?php
class Tag{
	public $name;
	public $recommended_num;
	public $read_num;
	
	public function __construct($name,$recommended_num,$read_num){
		$this->name = $name;
		$this->recommended_num = $recommended_num;
		$this->read_num = $read_num;
	}
}