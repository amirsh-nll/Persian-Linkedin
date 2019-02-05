<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created : 07/11/2018
 * Author : A.shokri
 * Mail : amirsh.nll@gmail.com
 
 */

class User extends CI_Controller
{
	/* Private */
	private function parser($view_name, $my_data = null)
	{
		if(empty($view_name) || is_null($view_name))
		{
			show_404();
			return false;
		}

		$this->load->helper('security');
		$this->load->library('parser');

		$view_name = "ui/" . xss_clean($view_name);
		$data = array(
	        'base'	=>	$this->base_url()
		);
		if(!is_null($my_data))
			$data = array_merge($data, $my_data);

		$this->parser->parse($view_name, $data);
		return true;
	}

	private function self_set_url($url)
	{
		if(is_numeric(strpos($url, "/index.php")))
			show_404();
	}

	private function current_url()
	{
		return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}

	private function base_url()
	{
		$this->load->helper('url');
        return base_url();
	}

	private function is_login()
	{
		if(!$this->session->has_userdata('user_login') || $this->session->has_userdata('user_login')!==true || !$this->session->has_userdata('user_id'))
		{
			$this->load->helper('url');
			redirect($this->base_url() . "login");
			exit(0);
		}

		$this->load->model('user_model');
		if(!$this->user_model->user_enable($this->session->userdata('user_id')))
		{
			$this->session->unset_userdata('user_id');
			$this->session->unset_userdata('user_login');
			$this->load->helper('url');
			redirect($this->base_url() . "login");
			exit(0);
		}

		/* Type 1 : Admin, Type 2 : User */
		if($this->user_model->get_type_by_id($this->session->userdata('user_id'))!=2)
		{
			$this->session->unset_userdata('user_id');
			$this->session->unset_userdata('user_login');
			$this->load->helper('url');
			redirect($this->base_url() . "login");
			exit(0);
		}
	}

    private function time()
    {
        return time();
    }

    private function user_agent()
    {
        $this->load->library('user_agent');
        return $this->agent->agent_string();
    }

    private function user_ip()
    {
        return $this->input->ip_address();
    }


	/* Public */
	public function index()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		$this->load->model('avatar_model');
		$user_current_avatar = $this->avatar_model->user_current_avatar($this->session->userdata('user_id'));

		$this->load->model('person_model');
		$user_person = $this->person_model->read_user_person($this->session->userdata('user_id'));
		$user_full_name = $user_person['firstname'] . " " . $user_person['lastname'];

		$form_newpost_open = form_open_multipart($this->base_url() . "user/form/newpost");
		$write_post_content = form_textarea(
			array(
				'id'			=>	'post_content',
				'name'			=>	'post_content',
				'maxlength'		=>	10000,
				'rows'			=> 	4,
				'placeholder'	=>	'چیزی بنویسید....',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$file_post_content = '<input type="file" id="file-upload" name="post_file" accept="image/*" />';
		$post_submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'اشتراک گذاری',
				'class'			=>	'btn bg-success text-light float-left'
			)
		);

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('connections_model');
		$user_connection_count = $this->connections_model->user_connection_count($this->session->userdata('user_id'));
		if($user_connection_count===false)
			$user_connection_count = 0;

		$this->load->model('profile_view_model');
		$user_view_profile = $this->profile_view_model->viewed_profile_count($this->session->userdata('user_id'));
		if($user_view_profile===false)
			$user_view_profile = 0;

		$this->load->model('contact_model');
		$user_contact = $this->contact_model->user_all_contact($this->session->userdata('user_id'));

		$this->load->model('post_model');
		$timeline_posts = $this->post_model->post_timeline($this->session->userdata('user_id'), 100);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'user_current_avatar'	=>	$user_current_avatar,
			'user_full_name'		=>	$user_full_name,
			'form_newpost_open'		=>	$form_newpost_open,
			'write_post_content'	=>	$write_post_content,
			'file_post_content'		=>	$file_post_content,
			'post_submit_input'		=>	$post_submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'user_connection_count'	=>	$user_connection_count,
			'user_view_profile'		=>	$user_view_profile,
			'user_contact'			=>	$user_contact,
			'timeline_posts'		=>	$timeline_posts
		);

		$this->parser('user/panel', $data);
	}

	public function logout()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->session->unset_userdata('user_id');
		$this->session->unset_userdata('user_login');
		$this->load->helper('url');
		redirect($this->base_url() . "login");
		exit(0);
	}

	public function setting()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('user_option_model');
		$form_setting_open = form_open($this->base_url() . "user/form/setting");
		$user_private_page = $this->user_option_model->get_option($this->session->userdata('user_id'), 'private_page');
		$dropdown_1_options = array (
			'false'	=>	'بازدید توسط همگان',
			'true'	=>	'بازدید توسط دوستان'
		);
		$dropdown_1 = form_dropdown('private_page', $dropdown_1_options, $user_private_page, 'class="form-control"');
		
		$user_private_contact = $this->user_option_model->get_option($this->session->userdata('user_id'), 'private_contact');
		$dropdown_2_options = array (
			'false'	=>	'بازدید توسط همگان',
			'true'	=>	'بازدید توسط دوستان'
		);
		$dropdown_2 = form_dropdown('private_contact', $dropdown_2_options, $user_private_contact, 'class="form-control"');

		$user_private_avatar = $this->user_option_model->get_option($this->session->userdata('user_id'), 'private_avatar');
		$dropdown_3_options = array (
			'false'	=>	'بازدید توسط همگان',
			'true'	=>	'بازدید توسط دوستان'
		);
		$dropdown_3 = form_dropdown('private_avatar', $dropdown_3_options, $user_private_avatar, 'class="form-control"');

		$password_input = form_input(
			array(
				'type'			=>	'password',
				'name'			=>	'password',
				'maxlength'		=>	40,
				'placeholder'	=>	'رمز عبور فعلی',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$new_password_input = form_input(
			array(
				'type'			=>	'password',
				'name'			=>	'new_password',
				'maxlength'		=>	40,
				'placeholder'	=>	'رمز عبور تازه',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$new_repassword_input = form_input(
			array(
				'type'			=>	'password',
				'name'			=>	'new_repassword',
				'maxlength'		=>	40,
				'placeholder'	=>	'تکرار رمز عبور تازه',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت  تغییرات',
				'class'			=>	'btn bg-success text-light float-left'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_setting_open'		=>	$form_setting_open,
			'dropdown_1'			=>	$dropdown_1,
			'dropdown_2'			=>	$dropdown_2,
			'dropdown_3'			=>	$dropdown_3,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'password_input'		=>	$password_input,
			'new_password_input'	=>	$new_password_input,
			'new_repassword_input'	=>	$new_repassword_input
		);

		$this->parser('user/setting', $data);
	}

	public function profile()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		$this->load->model('person_model');
		$user_person = $this->person_model->read_user_person($this->session->userdata('user_id'));

		$this->load->model('user_item_model');
		$experience	= $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 1);
		$education	= $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 2);
		$skills		= $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 3);
		$project	= $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 4);

		$this->load->model('connections_model');
		$user_connection = $this->connections_model->user_connection($this->session->userdata('user_id'), 0);

		$this->load->model('avatar_model');
		$user_current_avatar = $this->avatar_model->user_current_avatar($this->session->userdata('user_id'));

		$this->load->model('person_model');
		$this->load->model('country_model');
		$user_person 				= $this->person_model->read_user_person($this->session->userdata('user_id'));
		$user_person['country_id'] 	= $this->country_model->get_country_name($user_person['country_id']);

		$this->load->model('contact_model');
		$user_contact = $this->contact_model->user_all_contact($this->session->userdata('user_id'));
		$twitter_value  = "";
		$linkedin_value = "";
		$telegram_value = "";
		$skype_value  	= "";
		foreach ($user_contact as $ucs) {
			if($ucs['type']==1)
				$linkedin_value = $ucs['content'];
			if($ucs['type']==2)
				$twitter_value = $ucs['content'];
			if($ucs['type']==3)
				$telegram_value = $ucs['content'];
			if($ucs['type']==4)
				$skype_value = $ucs['content'];
		}

		$linkedin = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'linkedin',
				'maxlength'		=>	500,
				'placeholder'	=>	'linkedin',
				'class'			=>	'social-profile-input',
				'value'			=>	$linkedin_value
			)
		);
		$twitter = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'twitter',
				'maxlength'		=>	500,
				'placeholder'	=>	'twitter',
				'class'			=>	'social-profile-input',
				'value'			=>	$twitter_value
			)
		);
		$telegram = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'telegram',
				'maxlength'		=>	500,
				'placeholder'	=>	'telegram',
				'class'			=>	'social-profile-input',
				'value'			=>	$telegram_value
			)
		);
		$skype = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'skype',
				'maxlength'		=>	500,
				'placeholder'	=>	'skype',
				'class'			=>	'social-profile-input',
				'value'			=>	$skype_value
			)
		);

		$social_form_open   = form_open($this->base_url() . "user/form/editsocial");
		$social_submit 		= form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'✎',
				'class'			=>	'btn bg-success text-light float-right'
			)
		);

		if($this->session->has_userdata('social_success')) {
			$social_success = $this->session->userdata('social_success');
			$this->session->unset_userdata('social_success');
		}
		else {
			$social_success="";
		}

		if($this->session->has_userdata('social_error')) {
			$social_error = $this->session->userdata('social_error');
			$this->session->unset_userdata('social_error');
		}
		else {
			$social_error="";
		}
		
		$this->load->model('connections_model');
		$user_connection_count = $this->connections_model->user_connection_count($this->session->userdata('user_id'));
		if($user_connection_count===false)
			$user_connection_count = 0;

		$this->load->model('profile_view_model');
		$user_view_profile = $this->profile_view_model->viewed_profile_count($this->session->userdata('user_id'));
		if($user_view_profile===false)
			$user_view_profile = 0;

		$this->load->model('contact_model');
		$user_contact = $this->contact_model->user_all_contact($this->session->userdata('user_id'));


		$form_avatar_open = form_open_multipart($this->base_url() . "user/form/newavatar");
		$file_avatar_content = '<input type="file" id="file-upload" name="avatar_file" class="avatar-file" accept="image/png, image/jpeg, image/jpg" />';
		$avatar_submit 		= form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'✔',
				'class'			=>	'btn bg-success text-light d-inline'
			)
		);
		if($this->session->has_userdata('avatar_success')) {
			$avatar_success = $this->session->userdata('avatar_success');
			$this->session->unset_userdata('avatar_success');
		}
		else {
			$avatar_success="";
		}

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'user_person'			=>	$user_person,
			'experience'			=>	$experience,
			'education'				=>	$education,
			'skills'				=>	$skills,
			'project'				=>	$project,
			'user_current_avatar'	=>	$user_current_avatar,
			'user_person'			=>	$user_person,
			'social_form_open'		=>	$social_form_open,
			'social_submit'			=>	$social_submit,
			'linkedin'				=>	$linkedin,
			'twitter'				=>	$twitter,
			'telegram'				=>	$telegram,
			'skype'					=>	$skype,
			'social_success'		=>	$social_success,
			'social_error'			=>	$social_error,
			'user_connection_count'	=>  $user_connection_count,
			'user_view_profile'		=>	$user_view_profile,
			'form_avatar_open'		=>	$form_avatar_open,
			'file_avatar_content'	=>	$file_avatar_content,
			'avatar_submit'			=>	$avatar_submit,
			'avatar_success'		=>	$avatar_success
		);

		$this->parser('user/profile', $data);
	}

	public function edit_person()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('person_model');
		$user_person = $this->person_model->read_user_person($this->session->userdata('user_id'));

		$form_editperson_open = form_open($this->base_url() . "user/form/editperson");

		$firstname_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'firstname',
				'maxlength'		=>	100,
				'placeholder'	=>	'نام',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_person['firstname']
			)
		);
		$lastname_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'lastname',
				'maxlength'		=>	100,
				'placeholder'	=>	'نام خانوادگی',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_person['lastname']
			)
		);

		$this->load->model('country_model');
		$country = $this->country_model->select_all();
		foreach ($country as $mc) {
			$dropdown_1_options[$mc['id']] = $mc['name'];
		}
		$dropdown_1 = form_dropdown('country_id', $dropdown_1_options, $user_person['country_id'], 'class="form-control"');

		$zip_code_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'zip_code',
				'maxlength'		=>	20,
				'placeholder'	=>	'کدپستی',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_person['zip_code']
			)
		);
		$birthday_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'birthday',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ تولد',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_person['birthday']
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت  تغییرات',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_editperson_open'	=>	$form_editperson_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'firstname_input'		=>	$firstname_input,
			'lastname_input'		=>	$lastname_input,
			'dropdown_1'			=>	$dropdown_1,
			'zip_code_input'		=>	$zip_code_input,
			'birthday_input'		=>	$birthday_input
		);

		$this->parser('user/editperson', $data);
	}

	public function edit_bio()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('person_model');
		$user_bio = $this->person_model->read_user_biography($this->session->userdata('user_id'));
		$user_bio = $user_bio['biography'];

		$form_editbio_open = form_open($this->base_url() . "user/form/editbio");

		$bio_input = form_textarea(
			array(
				'name'			=>	'biography',
				'maxlength'		=>	255,
				'rows'			=> 	4,
				'placeholder'	=>	'بیوگرافی',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_bio
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت  تغییرات',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_editbio_open'		=>	$form_editbio_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'bio_input'				=>	$bio_input
		);

		$this->parser('user/editbio', $data);
	}

	public function edit_experience()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		if($this->session->has_userdata('database_action')) {
			$database_action = $this->session->userdata('database_action');
			$this->session->unset_userdata('database_action');
		}
		else {
			$database_action="";
		}

		$this->load->model('user_item_model');
		$user_experience = $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 1);

		$form_addexperience_open = form_open($this->base_url() . "user/form/add_experience");

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_addexperience_open'=>	$form_addexperience_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_experience'		=>	$user_experience,
			'database_action'		=>	$database_action
		);

		$this->parser('user/editexperience', $data);
	}

	public function single_experience($experience_id)
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		if(empty($experience_id) || !is_numeric($experience_id))
        {
            redirect($this->base_url() . "panel/profile/edit/experience");
            exit(0);
        }
        else
        {
            $this->load->helper('security');
            $experience_id = xss_clean(trim($experience_id));
        }

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('user_item_model');
		$user_single_experience = $this->user_item_model->read_single_special_type_item($this->session->userdata('user_id'), 1, $experience_id);

		if($user_single_experience===false)
		{
			redirect($this->base_url() . "panel/profile/edit/experience");
            exit(0);
		}

		$form_editexperience_open = form_open($this->base_url() . "user/form/edit_experience");

		$id_input = form_hidden('id', $user_single_experience['id']);

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_experience['title']
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_experience['content']
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_experience['start_date']
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_experience['end_date']
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_editexperience_open'=>$form_editexperience_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'id_input'				=>	$id_input,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_single_experience'=>	$user_single_experience
		);

		$this->parser('user/singleexperience', $data);
	}

	public function edit_education()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		if($this->session->has_userdata('database_action')) {
			$database_action = $this->session->userdata('database_action');
			$this->session->unset_userdata('database_action');
		}
		else {
			$database_action="";
		}

		$this->load->model('user_item_model');
		$user_education = $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 2);

		$form_addeducation_open = form_open($this->base_url() . "user/form/add_education");

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_addeducation_open'=>	$form_addeducation_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_education'		=>	$user_education,
			'database_action'		=>	$database_action
		);

		$this->parser('user/editeducation', $data);
	}

	public function single_education($education_id)
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		if(empty($education_id) || !is_numeric($education_id))
        {
            redirect($this->base_url() . "panel/profile/edit/education");
            exit(0);
        }
        else
        {
            $this->load->helper('security');
            $education_id = xss_clean(trim($education_id));
        }

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('user_item_model');
		$user_single_education = $this->user_item_model->read_single_special_type_item($this->session->userdata('user_id'), 2, $education_id);

		if($user_single_education===false)
		{
			redirect($this->base_url() . "panel/profile/edit/education");
            exit(0);
		}

		$form_editeducation_open = form_open($this->base_url() . "user/form/edit_education");

		$id_input = form_hidden('id', $user_single_education['id']);

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_education['title']
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_education['content']
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_education['start_date']
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_education['end_date']
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_editeducation_open'=>$form_editeducation_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'id_input'				=>	$id_input,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_single_education'=>	$user_single_education
		);

		$this->parser('user/singleeducation', $data);
	}

	public function edit_skills()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		if($this->session->has_userdata('database_action')) {
			$database_action = $this->session->userdata('database_action');
			$this->session->unset_userdata('database_action');
		}
		else {
			$database_action="";
		}

		$this->load->model('user_item_model');
		$user_skills = $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 3);

		$form_addskills_open = form_open($this->base_url() . "user/form/add_skills");

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_addskills_open'=>	$form_addskills_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_skills'		=>	$user_skills,
			'database_action'		=>	$database_action
		);

		$this->parser('user/editskills', $data);
	}

	public function single_skills($skills_id)
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		if(empty($skills_id) || !is_numeric($skills_id))
        {
            redirect($this->base_url() . "panel/profile/edit/skills");
            exit(0);
        }
        else
        {
            $this->load->helper('security');
            $skills_id = xss_clean(trim($skills_id));
        }

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('user_item_model');
		$user_single_skills = $this->user_item_model->read_single_special_type_item($this->session->userdata('user_id'), 3, $skills_id);

		if($user_single_skills===false)
		{
			redirect($this->base_url() . "panel/profile/edit/skills");
            exit(0);
		}

		$form_editskills_open = form_open($this->base_url() . "user/form/edit_skills");

		$id_input = form_hidden('id', $user_single_skills['id']);

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_skills['title']
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_skills['content']
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_skills['start_date']
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_skills['end_date']
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_editskills_open'=>$form_editskills_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'id_input'				=>	$id_input,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_single_skills'=>	$user_single_skills
		);

		$this->parser('user/singleskills', $data);
	}

	public function edit_project()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		if($this->session->has_userdata('database_action')) {
			$database_action = $this->session->userdata('database_action');
			$this->session->unset_userdata('database_action');
		}
		else {
			$database_action="";
		}

		$this->load->model('user_item_model');
		$user_project = $this->user_item_model->read_user_special_type_item($this->session->userdata('user_id'), 4);

		$form_addproject_open = form_open($this->base_url() . "user/form/add_project");

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left'
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_addproject_open'=>	$form_addproject_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_project'		=>	$user_project,
			'database_action'		=>	$database_action
		);

		$this->parser('user/editproject', $data);
	}

	public function single_project($project_id)
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		if(empty($project_id) || !is_numeric($project_id))
        {
            redirect($this->base_url() . "panel/profile/edit/project");
            exit(0);
        }
        else
        {
            $this->load->helper('security');
            $project_id = xss_clean(trim($project_id));
        }

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		if($this->session->has_userdata('form_error')) {
			$validation_errors = $this->session->userdata('form_error');
			$this->session->unset_userdata('form_error');
		}
		else {
			$validation_errors="";
		}

		if($this->session->has_userdata('form_success')) {
			$form_success = $this->session->userdata('form_success');
			$this->session->unset_userdata('form_success');
		}
		else {
			$form_success="";
		}

		$this->load->model('user_item_model');
		$user_single_project = $this->user_item_model->read_single_special_type_item($this->session->userdata('user_id'), 4, $project_id);

		if($user_single_project===false)
		{
			redirect($this->base_url() . "panel/profile/edit/project");
            exit(0);
		}

		$form_editproject_open = form_open($this->base_url() . "user/form/edit_project");

		$id_input = form_hidden('id', $user_single_project['id']);

		$title_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'title',
				'maxlength'		=>	100,
				'placeholder'	=>	'عنوان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_project['title']
			)
		);

		$content_input = form_textarea(
			array(
				'name'			=>	'content',
				'maxlength'		=>	1000,
				'rows'			=> 	4,
				'placeholder'	=>	'توضیحات',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_project['content']
			)
		);

		$start_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'start_date',
				'maxlength'		=>	10,
				'placeholder'	=>	'تاریخ شروع',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_project['start_date']
			)
		);

		$end_date_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'end_date',
				'maxlength'		=>	100,
				'placeholder'	=>	'تاریخ پایان',
				'class'			=>	'form-control text-right right-to-left',
				'value'			=>	$user_single_project['end_date']
			)
		);

		$submit_input = form_input(
			array(
				'type'			=>	'submit',
				'name'			=>	'submit',
				'value'			=>	'ثبت',
				'class'			=>	'btn bg-success text-light'
			)
		);

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close,
			'form_editproject_open'=>$form_editproject_open,
			'submit_input'			=>	$submit_input,
			'validation_errors'		=>	$validation_errors,
			'form_success'			=>	$form_success,
			'id_input'				=>	$id_input,
			'title_input'			=>	$title_input,
			'content_input'			=>	$content_input,
			'start_date_input'		=>	$start_date_input,
			'end_date_input'		=>	$end_date_input,
			'user_single_project'=>	$user_single_project
		);

		$this->parser('user/singleproject', $data);
	}

	public function notification()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close
		);

		$this->parser('user/notification', $data);
	}

	public function message()
	{
		$this->self_set_url($this->current_url());
		$this->is_login();

		$this->load->helper('form');
		$form_search_open = form_open($this->base_url() . "user/form/search");
		$search_input = form_input(
			array(
				'type'			=>	'text',
				'name'			=>	'search',
				'maxlength'		=>	255,
				'placeholder'	=>	'تایپ + اینتر',
				'class'			=>	'form-control text-right right-to-left'
			)
		);
		$form_close 	= form_close();

		$data = array(
			'form_search_open'		=>	$form_search_open,
			'search_input'			=>	$search_input,
			'form_close'			=>	$form_close
		);

		$this->parser('user/message', $data);
	}

}
