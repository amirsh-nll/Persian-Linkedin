<?php

/**
 * Created : 01/11/2018
 * Author : A.shokri
 * Mail : amirsh.nll@gmail.com
 
 */

class Avatar_model extends CI_Model {

    public function __construct()
    {
    	parent::__construct();
    	$this->load->database();
    }

    public function insert($user_id, $filename, $time, $status, $user_agent)
    {
    	if(empty($user_id) || empty($filename) || empty($time) || empty($status) || empty($user_agent))
    		return false;

    	$data = array(
    		'user_id'	=>	$user_id,
    		'filename'	=>	$filename,
    		'time'		=>	$time,
    		'status'	=>	$status,
    		'user_agent'=>	$user_agent
    	);
    	
    	if($this->db->insert('avatar', $data))
    		return true;
    	else
    		return false;
    }
    
}

?>