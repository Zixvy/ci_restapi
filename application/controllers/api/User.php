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

        if ($user === null) {
            $this->response([
                'status' => false,
                'message' => 'User not found',
            ], REST_CONTROLLER::HTTP_NOT_FOUND);
        }

        $this->response([
            'status' => false,
            'message' => 'User not found',
        ], REST_CONTROLLER::HTTP_NOT_FOUND);
    }

    public function index_delete()
    {
        $id = $this->delete('id');

        if ($id === null) {
            $this->response([
                'status' => false,
                'message' => 'Provide an ID',
            ], REST_CONTROLLER::HTTP_BAD_REQUEST);
        } else {
            if ($this->User_model->delete_user($id) > 0) {
                $this->response([
                    'status' => false,
                    'id' => $id,
                    'message' => 'User deleted',
                ], REST_CONTROLLER::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'ID not found',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }
        }
    }

    public function index_post()
    {
        try {
            $this->form_validation->set_rules('nama', 'Nama', 'trim|required');
            $this->form_validation->set_rules('username', 'Username', 'trim|required|is_unique[users.username]|min_length[5]', ['is_unique' => 'Username already exists']);
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[8]');

            if (!$this->form_validation->run()) {
                throw new Exception(validation_errors());
            }

            $data = [
                'nama' => $this->post('nama'),
                'username' => $this->post('username'),
                'password' => password_hash($this->post('password'), PASSWORD_BCRYPT),
            ];

            if ($this->User_model->register_user($data) > 0) {
                $this->response([
                    'status' => true,
                    'message' => 'User succesfully registered',
                ], REST_CONTROLLER::HTTP_CREATED);
            } else {
                $this->response([
                    'status' => false,
                    'message' => $this->User_model->register_user($data),
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }
        } catch (\Throwable $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage(),
            ], REST_CONTROLLER::HTTP_BAD_REQUEST);
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
                    'message' => 'Provide an ID',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }

            if ($this->User_model->update_user($data, $id) > 0) {
                $this->response([
                    'status' => true,
                    'message' => 'User succesfully updated',
                ], REST_CONTROLLER::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Failed to update user',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }
        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], REST_CONTROLLER::HTTP_BAD_REQUEST);
        }
    }

    public function user_login()
    {
        try {
            $this->form_validation->set_rules('username', 'Username', 'trim|required');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[8]');

            if (!$this->form_validation->run()) {
                throw new Exception(validation_errors());
            }

            $username = $this->post('username');
            $password = $this->post('password');

            $user = $this->User_model->get_user_by_username($username);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $this->response([
                        'status' => true,
                        'data' => $user,
                    ], REST_CONTROLLER::HTTP_OK);
                } else {
                    $this->response([
                        'status' => false,
                        'message' => 'Wrong password',
                    ], REST_CONTROLLER::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Username not found in database',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }
        } catch (\Throwable $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage(),
            ], REST_CONTROLLER::HTTP_BAD_REQUEST);
        }
    }
}
