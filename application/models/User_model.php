<?php

class User_model extends CI_Model
{

    public function get_user($id = null)
    {
        if ($id === null) {
            return $this->db->get('users')->result_array();
        }
        return $this->db->get_where('users', ['id' => $id])->result_array();
    }

    public function delete_user($id)
    {
        $this->db->delete('users', ['id' => $id]);
        return $this->db->affected_rows();
    }

    public function register_user($data)
    {
        $this->db->insert('users', $data);
        return $this->db->affected_rows();
    }

    public function update_user($data, $id)
    {
        $this->db->update('users', $data, ['id' => $id]);
        return $this->db->affected_rows();
    }

    public function get_user_by_username($username)
    {
        return $this->db->get_where('users', ['username' => $username])->row_array();
    }

}
