<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';
require APPPATH . 'core/Api_controller.php';

class Auth extends REST_Controller
{
    public function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->methods['index_post']['limit'] = 300;

        $this->load->model('User_model');

    }

    public function token_get()
    {
        $tokenData = array();
        $tokenData['id'] = 1; //TODO: Replace with data for token
        $output['token'] = AUTHORIZATION::generateToken($tokenData);
        $this->set_response($output, REST_Controller::HTTP_OK);
    }

    /**
     * URL: http://localhost/CodeIgniter-JWT-Sample/auth/token
     * Method: POST
     * Header Key: Authorization
     * Value: Auth token generated in GET call
     */
    public function token_post()
    {
        $headers = $this->input->request_headers();

        if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
            $decodedToken = AUTHORIZATION::validateToken($headers['Authorization']);
            if ($decodedToken != false) {
                $this->set_response($decodedToken, REST_Controller::HTTP_OK);
                return;
            }
        }

        $this->set_response("Unauthorised", REST_Controller::HTTP_UNAUTHORIZED);
    }

    public function login_post()
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
                    $tokenData = array();
                    $tokenData['id'] = $user['id'];
                    $output['token'] = AUTHORIZATION::generateToken($tokenData);
                    $this->response([
                        'status' => true,
                        'token' => $output['token'],
                        'message' => 'Login success',
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
