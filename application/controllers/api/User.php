<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class User extends REST_Controller
{
    public function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->methods['index_get']['limit'] = 300;
        $this->methods['index_post']['limit'] = 300;
        $this->methods['index_delete']['limit'] = 300;
        $this->methods['index_put']['limit'] = 300;

        $this->load->model('User_model');

    }

    public function index_get()
    {
        $id = $this->get('id');

        if ($id === null) {
            $user = $this->User_model->get_user();
        } else {
            $user = $this->User_model->get_user($id);
        }

        if ($user) {
            $this->response([
                'status' => true,
                'data' => $user,
            ], REST_CONTROLLER::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'User tidak ditemukan',
        ], REST_CONTROLLER::HTTP_NOT_FOUND);
    }

    public function index_delete()
    {
        $id = $this->delete('id');

        if ($id === null) {
            $this->response([
                'status' => false,
                'message' => 'Tolong sediakan ID',
            ], REST_CONTROLLER::HTTP_BAD_REQUEST);
        } else {
            if ($this->User_model->delete_user($id) > 0) {
                $this->response([
                    'status' => false,
                    'id' => $id,
                    'message' => 'User berhasil di hapus',
                ], REST_CONTROLLER::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'ID tidak ditemukan',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }
        }
    }

    public function index_put()
    {
        try {
            $this->form_validation->set_data($this->put());
            $this->form_validation->set_rules('nama', 'Nama', 'trim|required');
            $this->form_validation->set_rules('username', 'Username', 'trim|required|is_unique[users.username]|min_length[5]');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[8]');

            if (!$this->form_validation->run()) {
                throw new Exception(validation_errors());
            }

            $id = $this->put('id');
            $data = [
                'nama' => $this->put('nama'),
                'username' => $this->put('username'),
                'password' => $this->put('password'),
            ];

            if ($id === null) {
                $this->response([
                    'status' => false,
                    'message' => 'Tolong sediakan ID',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }

            if ($this->User_model->update_user($data, $id) > 0) {
                $this->response([
                    'status' => true,
                    'message' => 'User berhasil di update',
                ], REST_CONTROLLER::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'User gagal di update',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }
        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], REST_CONTROLLER::HTTP_BAD_REQUEST);
        }
    }
}
