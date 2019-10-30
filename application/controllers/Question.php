<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Question extends CI_Controller {

	function __construct() {
        parent::__construct();
        //$this->load->helper('search');
        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->library('pagination');
        $this->load->model("question_model");
        $this->load->library('session');
    }
	
	public function index()
	{
		$this->load->view('welcome_message');
	}
	
	public function headers($result = "")
	{
		$data = "*";
		$where = Null;
		$res = $this->question_model->selectRecord("users",$data,$where);
		$result['all_vendors'] = $this->question_model->selectRecord("vendors",'*',NULL)->result();
		$result['user'] = $res->row();
		$result['obj'] = $this;
		$this->load->view("header",$result);
	}
	
	// public function index() {
        // $id = $this->session->userdata("user_id");
        // if ($id == "") {
            // $this->load->view('login-register');
        // } else {
            // $this->dashboard();
        // }
    // }
	
	public function register($message)
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            $this->load->view('register',$message);
        } else {
            $this->dashboard();
        }
	}
	
	public function login($message)
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            $this->load->view('login',$message);
        } else {
            $this->dashboard();
        }
	}

    public function sign_in() {
        $rss = $this->question_model->check_user_status($this->input->post('email'), $this->input->post('password'));
		
		if($rss->status==0){
			$message = array("class" => "danger", "msg" => "Your account is not activated");
            $this->login($message);
		}else{
			$res = $this->question_model->login_user($this->input->post('email'), $this->input->post('password'));
			if (!empty($res)) {
				redirect(site_url("stocks/dashboard"));
			} else {
				$message = array("class" => "danger", "msg" => "Invalid login credentials");
				$this->login($message);
			}
		}
        
    }
	
	public function signup()
	{
		$rules = array(
                array('field' => 'name', 'label' => "Name", 'rules' => 'required'),
                array('field' => 'email', 'label' => "Email", 'rules' => 'required'),
                array('field' => 'password', 'label' => 'Password', 'rules' => 'trim|required|min_length[6]|max_length[20]'),
                array('field' => 'cpassword', 'label' => 'Confirm Password', 'rules' => 'trim|required|matches[password]'),
            );
		$this->form_validation->set_rules($rules);
		if ($this->form_validation->run() == False) {
			$message['class'] = "danger";
			$this->register($message);
		}else{
			$data = "*";
			$where = array("email" => $this->input->post("email"));
			$res = $this->question_model->selectRecord("users",$data,$where);
			
			if( $res->num_rows() > 0 ) {
				$message = array("class" => "danger", "msg" => "Email already exist");
				$this->register($message);
			}else{
				$data = array(
							"name" => $this->input->post("name"),
							"email" => $this->input->post("email"),
							"password" => md5($this->input->post("password")),
							"role" => 0,
							"status" => 0,
							"read" => 0,
							"write" => 0,
						);
				$result = $this->question_model->insert("users",$data);
				if ($result == 0) {
					$message = array("class" => "danger", "msg" => "Failed to register new member");
					$this->register($message);
				}
				if ($result == 1) {
					$message = array("class" => "success", "msg" => "Your registration has been successfull. You will get a notification email after Super Admin will approve.");
					$this->register($message);
				}
			}
		}
	}
	
	public function Forgot($message)
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            $this->load->view('forgot',$message);
        } else {
            $this->dashboard();
        }
	}
	
	public function ForgotEmailSubmit()
	{
		$data = "*";
		$where = array("email" => $this->input->post("email"));
		$res = $this->question_model->selectRecord("users",$data,$where);
		
		if( $res->num_rows() <= 0 ) {
			$message = array("class" => "danger", "msg" => "Email does not exist");
			$this->Forgot($message);
		}else{
			$res = $res->row();
			$to = $this->input->post('email');
			$subject = "Forgot Password";
			$msg = "Hello,";
			$msg.="<br/> Here is the link to reset your password.<br/> Please click on the below link:";
			$msg.="<table>";
			$msg.="<tr><td> ---> </td><td>" . site_url("stocks/ResetPassword") . "/?id=".$res->id."&code=". $res->password . "</td><tr>";
			$msg.="</table>";
			$msg.="<br/><br/>Regards,<br/>RichardHarris Team";
			$header = "From: info@richardharris.com\r\n";
			$header.= "MIME-Version: 1.0\r\n";
			$header.= "Content-Type: text/html; charset=utf-8\r\n";
			$header.= "X-Priority: 1\r\n";
			$result1 = mail($to, $subject, $msg, $header);
			if($result1){
				$message = array("class" => "success", "msg" => "Please check your email");
				$this->Forgot($message);
			}
		}
	}
	
	public function ResetPassword($result,$uid,$code)
	{	
		$id = $this->session->userdata("user_id");
        if ($id == "") {
			if(isset($_GET['id'])&&(isset($_GET['code']))){
				$result['id'] = $_GET['id'];
				$result['code'] = $_GET['code'];
			}else{
				$result['id'] = $uid;
				$result['code'] = $code;
			}
			
            $this->load->view('reset',$result);
        } else {
            $this->dashboard();
        }
	}
	
	public function ChangePassword()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
            $rules = array(
					array('field' => 'new_pwd', 'label' => 'Password', 'rules' => 'trim|required|min_length[6]|max_length[20]'),
					array('field' => 'confirm_pwd', 'label' => 'Confirm Password', 'rules' => 'trim|required|matches[new_pwd]'),
				);
			$this->form_validation->set_rules($rules);
			if ($this->form_validation->run() == False) {
				$message['class'] = "danger";
				$this->ResetPassword($message,$this->input->post("id"),$this->input->post("code"));
			} else {
				
				$data = array("password"=>md5($this->input->post("new_pwd")));
				$where = array( "id" => $this->input->post("id"), "password" => $this->input->post("code") );
				$result = $this->question_model->update("users",$data,$where);
				
				if ($result == 0) {
					$message = array("class" => "danger", "msg" => "Failed to update password");
					$this->ResetPassword($message,$this->input->post("id"),$this->input->post("code"));
				}
				if ($result == 1) {
					$message = array("class" => "success", "msg" => "Password Update Successfully");
					$this->ResetPassword($message,$this->input->post("id"),$this->input->post("code"));
				}
			}
        }
		
	}

    public function dashboard() {

        $id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$rss = $this->question_model->selectRecord("rh_orders","count(id) as cnt",NULL);
			$rss1 = $this->question_model->selectRecord("rh_products","count(id) as cnt",NULL);
			$rss2 = $this->question_model->selectRecord("rh_users","count(id) as cnt",NULL);
			
			$res['orders'] = $rss->row();
			$res['products'] = $rss1->row();
			$res['users'] = $rss2->row();
			$res['pending'] = $this->question_model->GetPendingOrdersIndex();
			
			$result['dash'] = "active";
            $this->headers();
            $this->load->view("sidebar",$result);
            $this->load->view("index",$res);
            $this->load->view("footer");
        }
    }
	
	public function ActiveUsers()
	{
        $id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$data = "*";
			$where = array( "role" => 0, "status" => 1 );
			$result['data'] = $this->question_model->selectRecord("users",$data,$where);
			$result['adm_usrs'] = "active";
			$result['act_usrs'] = "active";
			$result['adm_usrs_menu'] = "menu-open";
            $this->headers();
            $this->load->view("sidebar",$result);
            $this->load->view("stocks-users");
            $this->load->view("footer");
        }
    }
	
	public function InactiveUsers()
	{
        $id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$data = "*";
			$where = array( "role" => 0, "status" => 0 );
			$result['data'] = $this->question_model->selectRecord("users",$data,$where);
			$result['adm_usrs'] = "active";
			$result['inact_usrs'] = "active";
			$result['adm_usrs_menu'] = "menu-open";
            $this->headers();
            $this->load->view("sidebar",$result);
            $this->load->view("stocks-users");
            $this->load->view("footer");
        }
    }
	
	public function Users()
	{
        $id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$data = "*";
			$where = Null;
			$result['data'] = $this->question_model->selectRecord("rh_users",$data,$where);
			$res['users'] = "active";
            $this->headers();
            $this->load->view("sidebar",$res);
            $this->load->view("users",$result);
            $this->load->view("footer");
        }
    }
	
	public function StatusUpdate()
	{
		$id = $this->input->post("id");
		$status = $this->input->post("status");
		
		$data1 = "*";
		$where1 = array( "id" => $id );
		$res = $this->question_model->selectRecord("users",$data1,$where1);
		$res = $res->row();
		$data = array("status"=>$status);
		$where = array( "id" => $this->input->post("id") );
		$result = $this->question_model->update("users",$data,$where);
		print_r($res);
		if($result){
			if($status==0){
				$to = $res->email;
				$subject = "Account Deactivated";
				$msg = "Hello, ".$res->name;
				$msg.="<br/> Your account has been deactivated.<br/> Please contact the super stocks for further information";
				$msg.="<br/><br/>Regards,<br/>RichardHarris Team";
				$header = "From: info@richardharris.com\r\n";
				$header.= "MIME-Version: 1.0\r\n";
				$header.= "Content-Type: text/html; charset=utf-8\r\n";
				$header.= "X-Priority: 1\r\n";
				$result1 = mail($to, $subject, $msg, $header);
				
				echo $result;
			}else{
				$to = $res->email;
				$subject = "Account Activated";
				$msg = "Hello, ".$res->name;
				$msg.="<br/> Your account has been activated now.<br/> You can now login to your stocks account";
				$msg.="<br/><br/>Regards,<br/>RichardHarris Team";
				$header = "From: info@richardharris.com\r\n";
				$header.= "MIME-Version: 1.0\r\n";
				$header.= "Content-Type: text/html; charset=utf-8\r\n";
				$header.= "X-Priority: 1\r\n";
				$result1 = mail($to, $subject, $msg, $header);
				
				echo $result;
			}
			
		}
	}
	
	public function ReadUpdate()
	{
		$id = $this->input->post("id");
		$read = $this->input->post("read");
		
		$data = array("read"=>$read);
		$where = array( "id" => $this->input->post("id") );
		$result = $this->question_model->update("users",$data,$where);
		
		echo $result;
	}

	public function WriteUpdate()
	{
		$id = $this->input->post("id");
		$write = $this->input->post("write");
		
		$data = array("write"=>$write);
		$where = array( "id" => $this->input->post("id") );
		$result = $this->question_model->update("users",$data,$where);
		
		echo $result;
	}
	
	public function Profile($message=NULL)
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$this->headers();
			$this->load->view("sidebar");
			$this->load->view("profile",$message);
			$this->load->view("footer");
        }
		
	}
	
	public function UpdateProfile()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$rules = array(
                array('field' => 'name', 'label' => "Name", 'rules' => 'required'),
                array('field' => 'email', 'label' => 'Email', 'rules' => 'required'),
            );
            $this->form_validation->set_rules($rules);
            if ($this->form_validation->run() == False) {
                //$message['class'] = "danger";
				$message = array("class" => "danger", "msg" => validation_errors());
                $this->Profile($message);
            } else {
                $data = array( "name" => $this->input->post("name"), "email" => $this->input->post("email") );
				$where = array( "id" => $id );
				$result = $this->question_model->update("users",$data,$where);
				
                if ($result == 0) {
                    $message = array("class" => "danger", "msg" => "Failed to update your profile");
                    $this->Profile($message);
                }
                if ($result == 1) {
					$this->session->unset_userdata('a_name');
					$this->session->unset_userdata('a_email');
					
					$userdata = array(
						'a_name' => $this->input->post("name"),
						'a_email' => $this->input->post("email"),
					);

					$this->session->set_userdata($userdata);
                    $message = array("class" => "success", "msg" => "Profile Updated Successfully");
                    $this->Profile($message);
                }
            }
        }
	}
	
    //upload image
    public function upload($filename,$file) {
        $id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			
			$this->load->library('image_lib');
            //set preferences
            $config['upload_path'] = './uploads/originals';
            $config['allowed_types'] = 'jpg|jpeg|gif|png';
            $config['max_size'] = '8000';
            $config['file_name'] = time() . '_' .$file;
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload($filename)) {
                $upload_error = array('error' => $this->upload->display_errors());
                return $upload_error;
            } else {
                $image_data = $this->upload->data();
				
				//your desired config for the resize() function
				$config1 = array(
					'image_library' => 'GD2',
					'source_image'      => $image_data['full_path'], //path to the uploaded image
					'new_image'         => './uploads/small', //path to
					'maintain_ratio'    => true,
					'width'             => 90,
					'height'            => 130,
					);
			 
				//this is the magic line that enables you generate multiple thumbnails
				//you have to call the initialize() function each time you call the resize()
				//otherwise it will not work and only generate one thumbnail
				$this->image_lib->initialize($config1);
				$this->image_lib->resize();
				$this->image_lib->clear();
				
				//your desired config for the resize() function
				$config2 = array(
					'image_library' => 'GD2',
					'source_image'      => $image_data['full_path'], //path to the uploaded image
					'new_image'         => './uploads/medium', //path to
					'maintain_ratio'    => true,
					'width'             => 285,
					'height'            => 434,
					);
			 
				//this is the magic line that enables you generate multiple thumbnails
				//you have to call the initialize() function each time you call the resize()
				//otherwise it will not work and only generate one thumbnail
				$this->image_lib->initialize($config2);
				$this->image_lib->resize();
				$this->image_lib->clear();
			 
				$config3 = array(
					'image_library' => 'GD2',
					'source_image'      => $image_data['full_path'],
					'new_image'         => './uploads/large',
					'maintain_ratio'    => true,
					'width'             => 470,
					'height'            => 670,
					);
				//here is the second thumbnail, notice the call for the initialize() function again
				$this->image_lib->initialize($config3);
				$this->image_lib->resize();
				$this->load->library('upload', $config3);
				$this->upload->do_upload($filename);
				
                return $image_data['file_name'];
            }
        }
    }
	
    //Active Status
    public function ActiveStatus() {
        $id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
            $uid = $this->input->post("id");
            $table = $this->input->post("table");
            $st = $this->question_model->Activestatus($uid, $table);
            print_R($st);
        }
    }

    //Inactive
    public function InActiveStatus() {
        $id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
            $uid = $this->input->post("id");
            $table = $this->input->post("table");
            $st = $this->question_model->InActivestatus($uid, $table);
            print_R($st);
        }
    }

    //Delete
    public function Delete() {
        // $id = $this->session->userdata("user_id");
        // if ($id == "") {
            // redirect(site_url("stocks"));
        // } else {
            $uid = $this->input->post("id");
            $table = $this->input->post("table");
            $st = $this->question_model->delete($uid, $table);
            print_R($st);
        // }
    }
	
	//check old password
    function OldPassword() {
        $id = $this->session->userdata("user_id");
        if ($id == "") {
            $this->index();
        } else {
            $rules = array(
                array('field' => 'old_pwd', 'label' => "Old Password", 'rules' => 'required'),
                array('field' => 'new_pwd', 'label' => 'Password', 'rules' => 'trim|required|min_length[6]|max_length[20]'),
                array('field' => 'confirm_pwd', 'label' => 'Confirm Password', 'rules' => 'trim|required|matches[new_pwd]'),
            );
            $this->form_validation->set_rules($rules);
            if ($this->form_validation->run() == False) {
                //$message['class'] = "danger";
				$message = array("class1" => "danger", "msg1" => validation_errors());
                $this->Profile($message);
            } else {
                $result = $this->question_model->CheckOldPassword();
                if ($result == 0) {
                    $message = array("class1" => "danger", "msg1" => "Old Password not match");
                    $this->Profile($message);
                }
                if ($result == 1) {
                    $message = array("class1" => "success", "msg1" => "Password Update Successfully");
                    $this->Profile($message);
                }
            }
        }
    }
	
	public function Categories()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$data = "*";
			$res['data'] = $this->question_model->selectRecord("rh_categories",$data,$where=NULL);
			
			$res['cate'] = "active";
			$res['show_cate'] = "active";
			$res['cate_menu'] = "menu-open";
			$this->headers();
			$this->load->view("sidebar",$res);
			$this->load->view("categories",$res);
			$this->load->view("footer");
        }
	}
	
	public function AddCategories()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			//$data1 = "*";
			//$where1 = array( "parent" => 0 );
			//$res['cate'] = $this->question_model->selectRecord("rh_categories",$data1,$where1);
			$res['cate'] = "active";
			$res['add_cate'] = "active";
			$res['cate_menu'] = "menu-open";
			$res['pcategory'] = $this->ParentCategory();
			// echo "<pre>";
			// print_r($res['pcategory']);
			// echo "</pre>";exit;
			$this->headers();
			$this->load->view("sidebar",$res);
			$this->load->view("add-categories",$res);
			$this->load->view("footer");
        }
	}
	
	public function ParentCategory($parent = 0, $child = '') {
        $arr = array();
        $result = $this->question_model->getParentCategory($parent, $child);
        foreach ($result as $key => $val) {
            $arr[$val->id] = $val;
            $arr[$val->id]->subcat = $this->ParentCategory($val->id, $child . '--');
        }
        return $arr;
    }
	
	public function SaveCategories()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			if($_FILES['image']['name']!=''){
				$name = time() . '_' . $_FILES['image']['name'];
				move_uploaded_file($_FILES["image"]["tmp_name"][$key], $dir . "uploads/" . $name);
			}else{
				$name = "";
			}
			if($this->input->post("parent")!=""){
				$parent = $this->input->post("parent");
			}else{
				$parent = 0;
			}
			
            $data = array(
						"name" => $this->input->post("name"),
						"parent" => $parent,
						"image" => $name,
					);
			$res = $this->question_model->insert("rh_categories",$data);
			if($res){
				redirect(site_url("stocks/Categories"));
			}else{
				redirect(site_url("stocks/Categories"));
			}
        }
	}
	
	public function EditCategories($cid)
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$res['cate'] = "active";
			$res['add_cate'] = "active";
			$res['cate_menu'] = "menu-open";
            $data = "*";
			$where = array( "id" => $cid );
			$res['data'] = $this->question_model->selectRecord("rh_categories",$data,$where);
			$res['pcategory'] = $this->ParentCategory();
			$this->headers();
			$this->load->view("sidebar",$res);
			$this->load->view("add-categories",$res);
			$this->load->view("footer");
        }
	}
	
	public function UpdateCategories()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			if($_FILES['image']['name']!=''){
				$name = time() . '_' . $_FILES['image']['name'];
				move_uploaded_file($_FILES["image"]["tmp_name"][$key], $dir . "uploads/" . $name);
			}else{
				$name = $this->input->post("img");
			}
			if($this->input->post("parent")!=""){
				$parent = $this->input->post("parent");
			}else{
				$parent = 0;
			}
			
            $data = array(
						"name" => $this->input->post("name"),
						"parent" => $parent,
						"image" => $name,
					);
			
			$where = array( "id" => $this->input->post("id") );
			$res = $this->question_model->update("rh_categories",$data,$where);
			if($res){
				redirect(site_url("stocks/Categories"));
			}else{
				redirect(site_url("stocks/Categories"));
			}
        }
	}
	
	public function Products()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$res['data'] = $this->question_model->selectProducts();
		
			$res['prod'] = "active";
			$res['show_prod'] = "active";
			$res['prod_menu'] = "menu-open";
			
			$this->headers();
			$this->load->view("sidebar",$res);
			$this->load->view("products",$res);
			$this->load->view("footer");
        }
		
		
	}

	public function AddProduct($err=null)
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$res['err'] = $err;
			$res['prod'] = "active";
			$res['add_prod'] = "active";
			$res['prod_menu'] = "menu-open";
			//$data = "*";
			//$res['coll'] = $this->question_model->selectRecord("rh_collections",$data,$where=NULL);

			//$data1 = "*";
			//$res['fab'] = $this->question_model->selectRecord("rh_fabrics",$data1,$where=NULL);
			
			$res['pcategory'] = $this->ParentCategory();
			
			$this->headers();
			$this->load->view("sidebar",$res);
			$this->load->view("add-products",$res);
			$this->load->view("footer");
        }
		
		
	}

	public function SaveProduct()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$color_name = $this->input->post("color_name");
			$color_val = $this->input->post("colval");
			
			$attr_name = $this->input->post("attr_name");
			$attr_value = $this->input->post("attr_value");
			$manage_stock = $this->input->post("manage_stock");
			
			$feat = $this->upload("feat",$_FILES['feat']['name']);
			if(is_array($feat))
			{
				$this->AddProduct($feat);
			}else{
				$charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
				$prod_code = "FI-".rand(10,9978)."-".substr(str_shuffle($charset), 0, 2);
				
				$dta1 = array( 
							"prod_code" => $prod_code,
							"image" => $feat,
							"featured" => 1,
						);
				$this->question_model->insert("rh_products_images",$dta1);
				
				if($_FILES['gallery']['name'][0]!=''){
					$this->load->library('upload');
					$files = $_FILES;
					$aantal = count($_FILES['gallery']['name']);
					for($i=0; $i<$aantal; $i++)
					{

						$_FILES['gallery']['name']= $files['gallery']['name'][$i];
						$_FILES['gallery']['type']= $files['gallery']['type'][$i];
						$_FILES['gallery']['tmp_name']= $files['gallery']['tmp_name'][$i];
						$_FILES['gallery']['error']= $files['gallery']['error'][$i];
						$_FILES['gallery']['size']= $files['gallery']['size'][$i];    



						$config['upload_path'] = base_url().'/uploads/originals';

						$config['allowed_types'] = 'jpg|jpeg|gif|png';
						$config['max_size'] = '10000';
						$config['file_name']    = time() . '_' .$_FILES['gallery']['name'];
						$config['overwrite'] = FALSE;

						$this->load->library('upload', $config);
						if ( ! $this->upload->do_upload('gallery'))
						{
							$data['error'] .= $this->upload->display_errors();
							echo $data['error'];
							var_dump(is_dir( $config['upload_path'])); 
							return;
						}
						else
						{ 
							$imgdata = $this->upload->data();
							$filename = $imgdata['file_name'];
							$fullpath = $imgdata['full_path'];

							$dta = array( 
									"prod_code" => $prod_code,
									"image" => $filename,
									"sort" => 0,
									"featured" => 0,
								);
							$this->question_model->insert("rh_products_images",$dta);

							$this->load->library('image_lib');
							$config['source_image'] = $fullpath;
							$config['new_image'] = './uploads/large';
							$config['image_library'] = 'gd2';
							$config['maintain_ratio'] = true;
							$config['width']     = 470; 
							$config['height']   = 670;
							$this->image_lib->initialize($config);

							if ( ! $this->image_lib->resize())
							{
								echo $config['source_image'];
								echo $this->image_lib->display_errors();
							}

							$this->image_lib->clear();

							$this->load->library('image_lib');
							$config['source_image'] = $fullpath;
							$config['image_library'] = 'gd2';
							$config['maintain_ratio'] = true;
							$config['width']     = 285; 
							$config['new_image'] = './uploads/medium';
							$config['height']   = 434;
							$this->image_lib->initialize($config);
							if ( ! $this->image_lib->resize())
							{
								echo $config['source_image'];
								echo $this->image_lib->display_errors();
							}

							$this->image_lib->clear();    

							$this->load->library('image_lib');
							$config['source_image'] = $fullpath;
							$config['image_library'] = 'gd2';
							$config['maintain_ratio'] = true;
							$config['width']     = 90; 
							$config['new_image'] = './uploads/small';
							$config['height']   = 130;
							$this->image_lib->initialize($config);
							if ( ! $this->image_lib->resize())
							{
								echo $config['source_image'];
								echo $this->image_lib->display_errors();
							}

							$this->image_lib->clear();    
						}


					}
				}
				if(!empty($color_name)){
					foreach($color_name as $k=>$v)
					{
						if(!empty($color_val)){
							$field = "color_img".$color_val[$k];
						}else{
							$field = "color_img";
						}
						
						if($_FILES[$field]['name']!=''){
							$col = $this->upload($field,$_FILES[$field]['name']);
						
							if(is_array($col))
							{
								$this->AddProduct($col);
							}else{
								$dta = array( 
										"prod_code" => $prod_code,
										"color_name" => $v,
										"color_image" => $col,
									);
								$this->question_model->insert("rh_products_colors",$dta);
							}
						}else{
							$dta = array( 
									"prod_code" => $prod_code,
									"color_name" => $v,
								);
							$this->question_model->insert("rh_products_colors",$dta);
						}
					}
				}
				
				// if($_FILES['gall']['name'][0]!=''){
					// foreach ($_FILES['gall']['name'] as $key => $val) {
						// $gall = $this->upload($key,$val);
						// $dta = array( 
									// "prod_code" => $prod_code,
									// "image" => $gall,
									// "featured" => 0,
								// );
						// $this->question_model->insert("rh_products_images",$dta);
					// }
				// }
				
				$data = array(
							"prod_code" => $prod_code,
							"prod_title" => $this->input->post("title"),
							"regular_price" => $this->input->post("regular_price"),
							"sale_price" => $this->input->post("sale_price"),
							"features" => $this->input->post("features"),
							"description" => $this->input->post("description"),
							"category_id" => $this->input->post("category"),
							"sold_by" => $this->input->post("sold_by"),
						);
				$res = $this->question_model->insert("rh_products",$data);
				
				$data1 = array(
							"prod_code" => $prod_code,
							"weight" => $this->input->post("weight"),
							"sku" => $this->input->post("sku"),
							/*"qty" => $this->input->post("st_qty"),*/
							"status" => $this->input->post("st_status"),
						);
				$res1 = $this->question_model->insert("rh_products_inventory",$data1);
				
				if((!empty($attr_name))&&($attr_name[0]!="")){
					foreach($attr_name as $k=>$v)
					{
						$data3 = array("prod_code" => $prod_code, "attribute_name" => $v, "attribute_values" => $attr_value[$k], "manage_stock" => $manage_stock[$k]);
						$rss = $this->question_model->last_insert("rh_products_attributes",$data3);
						
						if($manage_stock[$k]==1){
							$arr[] = $rss;
						}
					}
					if(!empty($arr)){
						$data = $arr;
						$query = http_build_query(array('mydata' => $data));
						$query=urlencode($query);
						
						redirect(site_url("stocks/EditProduct")."/".$prod_code."/?attrs=".$query);
					}
				}
				
				if($res){
					redirect(site_url("stocks/Products"));
				}else{
					redirect(site_url("stocks/Products"));
				}
			}
        }
	}
	
	public function EditProduct($pcode,$err=null)
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$res['err'] = $err;
			
			$side['prod'] = "active";
			$side['add_prod'] = "active";
			$side['prod_menu'] = "menu-open";
			
			// $data66 = "*";
			// $res['fab'] = $this->question_model->selectRecord("rh_fabrics",$data66,$where=NULL);
			
			// $data4 = "*";
			// $res['coll'] = $this->question_model->selectRecord("rh_collections",$data4,$where4=NULL);
			$res['pcategory'] = $this->ParentCategory();
			
            $data = "*";
			$where = array( "prod_code" => $pcode );
			$res['data'] = $this->question_model->selectRecord("rh_products",$data,$where);
			
			$data1 = "*";
			$where1 = array( "prod_code" => $pcode );
			$res['attr'] = $this->question_model->selectRecord("rh_products_attributes",$data1,$where1);
			
			$data2 = "*";
			$where2 = array( "prod_code" => $pcode, "featured" => 0 );
			$res['images'] = $this->question_model->selectGallery($where2);
			
			$data5 = "*";
			$where5 = array( "prod_code" => $pcode, "featured" => 1 );
			$res['feat'] = $this->question_model->selectRecord("rh_products_images",$data5,$where5);
			
			$data3 = "*";
			$where3 = array( "prod_code" => $pcode );
			$res['inv'] = $this->question_model->selectRecord("rh_products_inventory",$data3,$where3);
			
			$data77 = "*";
			$where77 = array( "prod_code" => $pcode );
			$res['col'] = $this->question_model->selectRecord("rh_products_colors",$data77,$where77);
			
			if((isset($_GET['attrs']))&&($_GET['attrs']!="")){
				parse_str($_GET['attrs']);
				foreach($mydata as $v){
					$rs[] = $this->question_model->selectRecord("rh_products_attributes","*",array("id"=>$v))->row();
				}
			}else{
				foreach($res['attr']->result() as $k=>$v){
					if($v->manage_stock==1){
						$rs[] = $this->question_model->selectRecord("rh_products_attributes","*",array("id"=>$v->id))->row();
					}
				}
			}
			$res['stocks'] = $rs;
			$res['ths'] = $this;
			
			$this->headers();
			$this->load->view("sidebar",$side);
			$this->load->view("add-products",$res);
			$this->load->view("footer");
        }
	}
	
	public function UpdateProduct()
	{
		$id = $this->session->userdata("user_id");
        if ($id == "") {
            redirect(site_url("stocks"));
        } else {
			$color_name = $this->input->post("color_name");
			$color_val = $this->input->post("colval");
			
            $attr_name = $this->input->post("attr_name");
			$attr_value = $this->input->post("attr_value");
			$attr_id = $this->input->post("attr_id");
			$manage_stock = $this->input->post("manage_stock");
			$prod_code = $this->input->post("prod_code");
			
			// echo "<pre>";
			// print_r($_FILES['gallery']);
			// echo "</pre>";exit;
			if((!empty($attr_name))&&($attr_name[0]!="")){
				foreach($attr_name as $k=>$v)
				{
					$attr[] = array( "id" => $attr_id[$k], "name" => $v, "value" => $attr_value[$k], "manage_stock" => $manage_stock[$k] );
				}
			}
			if($_FILES['feat']['name']!=""){
				$data = "*";
				$where = array("prod_code"=>$prod_code);
				$res = $this->question_model->selectRecord("rh_products_images",$data,$where);
				$res = $res->result();
				
				if(empty($res)){
					$feat = $this->upload("feat",$_FILES['feat']['name']);
				}else{
					foreach($res as $k=>$v){
						$dat = array("featured"=>0);
						$wher = array("id"=>$v->id);
						$rss = $this->question_model->update("rh_products_images",$dat,$wher);
					}
					$feat = $this->upload("feat",$_FILES['feat']['name']);
				}
				
			}
			
			if(is_array($feat))
			{
				$this->EditProduct($prod_code,$feat);
			}else{
				if($_FILES['feat']['name']!=""){
					$dta1 = array( 
								"prod_code" => $prod_code,
								"image" => $feat,
								"featured" => 1,
							);
					$this->question_model->insert("rh_products_images",$dta1);
				}
				
				if($_FILES['gallery']['name'][0]!=''){
					$this->load->library('upload');
					$files = $_FILES;
					$aantal = count($_FILES['gallery']['name']);
					for($i=0; $i<$aantal; $i++)
					{

						$_FILES['gallery']['name']= $files['gallery']['name'][$i];
						$_FILES['gallery']['type']= $files['gallery']['type'][$i];
						$_FILES['gallery']['tmp_name']= $files['gallery']['tmp_name'][$i];
						$_FILES['gallery']['error']= $files['gallery']['error'][$i];
						$_FILES['gallery']['size']= $files['gallery']['size'][$i];    


						$target_dir = "uploads/originals/";
						$target_file = $target_dir .time()."_".basename($_FILES['gallery']['name']);
						$image=time()."_".basename($_FILES['gallery']['name']);
						if(move_uploaded_file($_FILES["gallery"]["tmp_name"], $target_file)){
							$dta = array( 
									"prod_code" => $prod_code,
									"image" => $image,
									"featured" => 0,
								);
							$this->question_model->insert("rh_products_images",$dta);
						}else{
							$data['error'] = "Failed to upload images";
							$this->EditProduct($prod_code,$data);
						}
					}
				}
				
				if(!empty($color_name)){
					foreach($color_name as $k=>$v)
					{
						if(!empty($color_val)){
							$field = "color_img".$color_val[$k];
						}else{
							$field = "color_img";
						}
						
						if($_FILES[$field]['name']!=''){
							$col = $this->upload($field,$_FILES[$field]['name']);
						
							if(is_array($col))
							{
								$this->AddProduct($col);
							}else{
								$dta = array( 
										"prod_code" => $prod_code,
										"color_name" => $v,
										"color_image" => $col,
									);
								$this->question_model->insert("rh_products_colors",$dta);
							}
						}else{
							$dta = array( 
									"prod_code" => $prod_code,
									"color_name" => $v,
								);
							$this->question_model->insert("rh_products_colors",$dta);
						}
					}
				}
				
				$data = array(
							"prod_title" => $this->input->post("title"),
							"regular_price" => $this->input->post("regular_price"),
							"sale_price" => $this->input->post("sale_price"),
							"features" => $this->input->post("features"),
							"description" => $this->input->post("description"),
							"category_id" => $this->input->post("category"),
							"sold_by" => $this->input->post("sold_by"),
						);
				$where = array("prod_code"=>$prod_code);
				$res = $this->question_model->update("rh_products",$data,$where);
				
				$data1 = array(
							"prod_code" => $prod_code,
							"weight" => $this->input->post("weight"),
							"sku" => $this->input->post("sku"),
							/*"qty" => $this->input->post("st_qty"),*/
							"status" => $this->input->post("st_status"),
						);
				$res1 = $this->question_model->update("rh_products_inventory",$data1,$where);
				
				if(!empty($attr)){
					foreach($attr as $k=>$v)
					{
						if($v['id']!=""){
							$data3 = array("attribute_name" => $v['name'], "attribute_values" => $v['value'], "manage_stock" => $v['manage_stock']);
							$where3 = array("id"=>$v['id']);
							$this->question_model->update("rh_products_attributes",$data3,$where3);
						}else{
							$data3 = array("prod_code" => $prod_code,  "attribute_name" => $v['name'], "attribute_values" => $v['value'], "manage_stock" => $v['manage_stock']);
							$this->question_model->insert("rh_products_attributes",$data3);
						}
					}
				}
				
				if($this->input->post('sort')!=""){
					$arr = explode(",",$this->input->post('sort'));
					foreach($arr as $k=>$v)
					{
						$rr[] = explode("=",$v);
					}
					
					foreach($rr as $k=>$v){
						$img = $v[0];
						$ord = $v[1];
						$this->question_model->updateImageOrder($img, $ord);
					}
				}
				
				if($res){
					redirect(site_url("stocks/Products"));
				}else{
					redirect(site_url("stocks/Products"));
				}
			}
        }
	}
	
	public function SaveManageStock()
	{
		$man_st_attr = $this->input->post("man_st_attr");
		$man_st_qty = $this->input->post("man_st_qty");
		$man_st_attr_id = $this->input->post("man_st_attr_id");
		$prodcode = $this->input->post("prodcode");
		
		if(!empty($man_st_attr)){
			foreach($man_st_attr as $k=>$v)
			{
				$res = $this->question_model->selectRecord("rh_products_attr_stocks","*",array("prod_code" => $prodcode,"attr_name" => $v))->row();
				
				$data = array(
							"prod_code" => $prodcode,
							"attr_id" => $man_st_attr_id[$k],
							"attr_name" => $v,
							"qty" => $man_st_qty[$k]
						);
				if(($man_st_qty[$k]!="")&&($v!="")){
					if(empty($res)){
						$rss = $this->question_model->insert("rh_products_attr_stocks",$data);
					}else{
						$rss = $this->question_model->update("rh_products_attr_stocks",$data,array("id"=>$res->id));
					}
				}
				
			}
			
			if($rss){
				redirect(site_url("stocks/EditProduct").'/'.$prodcode);
			}else{
				redirect(site_url("stocks/EditProduct").'/'.$prodcode);
			}
		}
	}
	
	public function OrderStatusUpdate()
	{
		$id = $this->input->post("id");
		$status = $this->input->post("status");
		
		$data = array( "status" => $status );
		$where = array( "order_id" => $id );
		
		$res = $this->question_model->update("rh_orders_status",$data,$where);
		
		$orders = $this->question_model->GetOrder($id);
		$addr = $this->question_model->GetUserAddressDetails($id);
		$items = $this->question_model->GetOrderItems($id);
		$user = $this->question_model->GetUserDetails($orders->user_id);
		
		$to = $user->email;
		$subject = "Order Details";
		$msg = "Hello," . $addr->fname . " " . $addr->lname;
		
		$msg.="<br/><table>";
		$msg.="<tr>
				<td>Order ID : </td>
				<td>".$orders->id."</td>
			<tr>";
		$msg.="<tr>
				<td>Order Date : </td>
				<td>".date("d M. Y",strtotime($orders->order_date))."</td>
			<tr>";
		$msg.="<tr>
				<td>Order Status : </td>
				<td>";if($status==0){
						$msg.="Pending";
					}else if($status==1){
						$msg.="Processing";
					}else if($status==2){
						$msg.="Completed";
					}else if($status==3){
						$msg.="Cancelled";
					}
		$msg.="</td>
			<tr>";
		$msg.="</table>";
		$msg.="<br/> Shipping Details:";
		$msg.="<br/><table>";
		$msg.="<tr>
				<td>Name : </td>
				<td>".$addr->fname." ".$addr->lname."</td>
			<tr>";
		$msg.="<tr>
				<td>Address : </td>
				<td>".$addr->address." ".$addr->locality.",<br/> ".$addr->state." ".$addr->country."-".$addr->pincode."</td>
			<tr>";
		$msg.="<tr>
				<td>Phone : </td>
				<td>".$addr->phone."</td>
			<tr>";
		$msg.="<tr>
				<td>Email : </td>
				<td>".$addr->email."</td>
			<tr>";
		$msg.="</table>";
		$msg.="<br/> Products Detail Show below:";
		$msg.="<table>";
		$msg.="<tr>
				<td>Qty</td>
				<td>Product</td>
				<td>Product Code #</td>
				<td>Description</td>
				<td>Subtotal</td>
			<tr>";
		$sub = 0;
		foreach($items as $key => $val){
			$attr = array();
			$attr[] = json_decode($val->prod_attributes);
			$msg.="<tr>
					<td>".$val->qty."</td>
					<td>".$val->prod_title."</td>
					<td>".$val->prod_code."</td>";
					"<td>";
						if(!is_array($attr[0])){
					foreach($attr as $key1=>$val1){
				$msg.="<div class='size-clot oder-size'>
						<label>".$val1->title.":</label>".$val1->value."
					</div>";
					}
					}else{
					foreach($attr[0] as $key1=>$val1){
				$msg.="<div class='size-clot oder-size'>
						<label>".$val1->title.":</label>".$val1->value."
					</div>";
						}
					}
				$msg.="</td>
					<td> $ ".number_format($val->amount,2)."</td>
					<tr>";
				$sub = $sub + $val->amount;
		}
		$msg.="</table>";
		
		$msg.="<br/> Payment Details:";
		$msg.="<br/><table>";
		$msg.="<tr>
				<td>Subtotal : </td>
				<td> $ ".number_format($sub,2)."</td>
			<tr>";
		$msg.="<tr>
				<td>Gateway Charge (".$orders->payment_method=='Stripe'?'3.4%':'4.6%'.") : </td>
				<td>";
				if($orders->payment_method=="Stripe"){
					$msg.= "$ ".number_format($sub*3.4/100,2);
				}elseif($orders->payment_method=="Paypal"){
					$msg.= "$ ".number_format($sub*4.6/100,2);
				}
			$msg.="</td>
			<tr>";
		$msg.="<tr>
				<td>Shipping : </td>
				<td>".$orders->shipping_charges==0?"Free Shipping":"$ ".number_format($orders->shipping_charges,2)."</td>
			<tr>";
		$msg.="<tr>
				<td>Total : </td>
				<td> $ ".number_format($orders->total_amount,2)."</td>
			<tr>";
		$msg.="</table>";
		
		$msg.="<br/><br/>Regards,<br/>Fitinc Team";
		$header = "From: info@fitinc.com\r\n";
		$header.= "MIME-Version: 1.0\r\n";
		$header.= "Content-Type: text/html; charset=utf-8\r\n";
		$header.= "X-Priority: 1\r\n";
		$result1 = mail($to, $subject, $msg, $header);
		
		echo $res;
	}
	
	public function not_found(){
		$this->load->view("404");
	}
	
	//logout
    public function logout() {
        $this->session->unset_userdata('user_id');
        $this->session->unset_userdata('a_name');
        $this->session->unset_userdata('a_email');
        $this->session->unset_userdata('a_role');
		redirect(site_url("stocks"));
    }
	
	public function getpdf(){
		
	}
}
?>
