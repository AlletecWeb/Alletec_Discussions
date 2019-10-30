<?php

class Question_model extends CI_Model {

    public function __construct() {

        parent::__construct();

        $this->load->database();
    }
	
	public function insert($table,$data){
		return $this->db->insert($table,$data);
	}
	
	public function selectRecord( $table = null, $data = null, $where = null )
	{
		if(($table == null)||($data == null)){
			return false;
		}else{
			$this->db->select($data);
			$this->db->from($table);
			if($where!=""){
				$this->db->where($where);
			}
			$query = $this->db->get();
			return $query;
		}
	}
	
	public function last_insert($table,$data){
		$this->db->insert($table,$data);
		return $this->db->insert_id();
	}
	
	public function update($table,$data,$where)
	{
		$this->db->where($where);
		return $this->db->update($table,$data);
	}

	// login user

    public function login_user($email, $password) {

        $this->db->where('email', $email);

        $this->db->where('password', MD5($password));

        $query = $this->db->get('users');

        if ($query->num_rows() > 0) {

            $row = $query->row();

            $userdata = array(
                'user_id' => $row->id,
                'a_name' => $row->name,
                'a_email' => $row->email,
                'a_role' => $row->role,
                'a_read' => $row->read,
                'a_write' => $row->write,
            );

            print_R($this->session->set_userdata($userdata));

            return true;
        }

        return false;
    }

	public function check_user_status($email, $password) {

        $this->db->where('email', $email);

        $this->db->where('password', MD5($password));

        $query = $this->db->get('users');

        if ($query->num_rows() > 0) {

            $row = $query->row();
			return $row;
        }
    }

    // Active Status

    public function Activestatus($id, $table) {

        $data = array("status" => 1);

        $this->db->where('id', $id);

        $result = $this->db->update($table, $data);

        return $result;
    }

    //Inactive status

    public function InActivestatus($id, $table) {

        $data = array("status" => 0);

        $this->db->where('id', $id);

        $result = $this->db->update($table, $data);

        return $result;
    }

    //Delete

    public function delete($id, $tabel) {

        $result = $this->db->delete($tabel, array('id' => $id));

        return $result;
    }
	
	public function deleteProduct($pcode, $tabel) {

        $result = $this->db->delete($tabel, array('prod_code' => $pcode));

        return $result;
    }

    //check old paassword
    public function CheckOldPassword() {
        $id = $this->session->userdata("user_id");
        $password = $this->input->post("old_pwd");
        $new_password = $this->input->post("new_pwd");

        $this->db->where("id", $id);
        $query = $this->db->get("users");
        if ($query->num_rows() > 0) {
            $oldpassword = $query->row();
            if ($oldpassword->password == MD5($password)) {
                $data = array("password" => MD5($new_password));
                $result = $this->db->where('id', $id);
                $result = $this->db->update('users', $data);
                if ($result) {
                    return true;
                }
                return false;
            }
        }
    }

    public function UpdateUser($userid) {
        $data = array(
            "first_name" => $this->input->post("fname"),
            "last_name" => $this->input->post("lname"),
            "contact" => $this->input->post("contact"),
        );
        $this->db->where("id", $userid);
        $insert = $this->db->update("users", $data);
        $insert_id = $this->db->insert_id();
        $data1 = array(
            "user_id" => $userid,
            "bussiness" => $this->input->post("bussiness"),
            "country_id" => $this->input->post("country"),
            "state" => $this->input->post("state"),
            "city" => $this->input->post("city"),
            "address" => $this->input->post("address"),
            "zipcode" => $this->input->post("pincode"),
        );
        $this->db->where("user_id", $userid);
        $query = $this->db->get("user_data");
        $row1 = $query->num_rows();
        if ($row1 > 0) {
            $this->db->where("user_id", $userid);
            $user = $this->db->update("user_data", $data1);
        } else {
            $user = $this->db->insert("user_data", $data1);
        }

        $this->db->where('id', $userid);
        $query = $this->db->get('users');
        $row = $query->row();
        $userdata = array(
            'fname' => $row->first_name,
            'lname' => $row->last_name,
        );
        print_R($this->session->set_userdata($userdata));
        return $user;
    }
	
	public function getParentCategory($parent, $child) {
        $this->db->where("parent", $parent);
        $query = $this->db->get("rh_categories");

        return $query->result();
    }
	
	public function selectProducts()
	{
		$this->db->select("p.*,ct.name as ct_name");
		$this->db->from("rh_products p");
		$this->db->join("rh_categories ct","ct.id=p.category_id");
		$query = $this->db->get();
		return $query;
	}
	
	public function GetPendingOrdersIndex()
	{
		$this->db->select("o.*,os.status");
		$this->db->from("rh_orders o");
		$this->db->join("rh_orders_status os","o.id=os.order_id");
		$this->db->where("os.status",0);
		$this->db->order_by("order_date","desc");
		$this->db->limit(10);
		$query = $this->db->get();
		return $query->result();
	}
	
	public function GetPendingOrders()
	{
		$this->db->select("o.*,os.status,a.locality,a.state");
		$this->db->from("rh_orders o");
		$this->db->join("rh_orders_status os","o.id=os.order_id");
		$this->db->join("rh_orders_address a","o.id=a.order_id","left");
		$this->db->where("os.status",0);
		$this->db->order_by("o.order_date","desc");
		$query = $this->db->get();
		return $query->result();
	}
	
	public function GetProcessingOrders()
	{
		$this->db->select("o.*,os.status,a.locality,a.state");
		$this->db->from("rh_orders o");
		$this->db->join("rh_orders_status os","o.id=os.order_id");
		$this->db->join("rh_orders_address a","o.id=a.order_id","left");
		$this->db->where("os.status",1);
		$this->db->order_by("o.order_date","desc");
		$query = $this->db->get();
		return $query->result();
	}
	
	public function GetCompletedOrders()
	{
		$this->db->select("o.*,os.status,a.locality,a.state");
		$this->db->from("rh_orders o");
		$this->db->join("rh_orders_status os","o.id=os.order_id");
		$this->db->join("rh_orders_address a","o.id=a.order_id","left");
		$this->db->where("os.status",2);
		$this->db->order_by("o.order_date","desc");
		$query = $this->db->get();
		return $query->result();
	}
	
	public function GetCancelledOrders()
	{
		$this->db->select("o.*,os.status,a.locality,a.state");
		$this->db->from("rh_orders o");
		$this->db->join("rh_orders_status os","o.id=os.order_id");
		$this->db->join("rh_orders_address a","o.id=a.order_id","left");
		$this->db->where("os.status",3);
		$this->db->order_by("o.order_date","desc");
		$query = $this->db->get();
		return $query->result();
	}
	
	public function GetOrder($order_id)
	{
		$this->db->select("o.*,os.status");
		$this->db->from("rh_orders o");
		$this->db->join("rh_orders_status os","o.id=os.order_id");
		$this->db->where("o.id",$order_id);
		$query = $this->db->get();
		return $query->row();
	}
	
	public function GetOrderReason($order_id)
	{
		$this->db->select("*");
		$this->db->from("rh_orders_cancellation");
		$this->db->where("order_id",$order_id);
		$query = $this->db->get();
		return $query->row();
	}
	
	public function GetOrderItems($order_id)
	{
		$this->db->select("items,vendor");
		$this->db->from("orders");
		$this->db->where("order_id",$order_id);
		$query = $this->db->get();
		return $query->row();
	}
	
	public function GetUserDetails($userid)
	{
		$this->db->select("*");
		$this->db->from("rh_users");
		$this->db->where("id",$userid);
		$query = $this->db->get();
		return $query->row();
	}
	
	public function GetUserAddressDetails($id)
	{
		$this->db->select("*");
		$this->db->from("rh_orders_address");
		$this->db->where("order_id",$id);
		$query = $this->db->get();
		return $query->row();
	}
	
	public function updateImageOrder($img, $ord)
	{
		$data = array("sort"=>$ord);
		$this->db->where("id",$img);
		return $this->db->update('rh_products_images',$data);
	}
	
	public function selectGallery($where2)
	{
		$this->db->select('*');
		$this->db->from('rh_products_images');
		$this->db->where($where2);
		$this->db->order_by('sort','ASC');
		$query = $this->db->get();
		return $query;
	}
}

?>