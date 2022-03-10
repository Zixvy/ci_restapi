<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Mahasiswa extends REST_Controller
{
    public function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->methods['index_get']['limit'] = 300;
        $this->methods['index_post']['limit'] = 300;
        $this->methods['index_delete']['limit'] = 300;
        $this->methods['index_put']['limit'] = 300;

        $this->load->model('Mahasiswa_model');

    }

    public function index_get()
    {
        $id = $this->get('id');

        if ($id === null) {
            $mahasiswa = $this->Mahasiswa_model->get_mahasiswa();
        } else {
            $mahasiswa = $this->Mahasiswa_model->get_mahasiswa($id);
        }

        if ($mahasiswa) {
            $this->response([
                'status' => true,
                'data' => $mahasiswa,
            ], REST_CONTROLLER::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Mahasiswa tidak ditemukan',
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
            if ($this->Mahasiswa_model->delete_mahasiswa($id) > 0) {
                $this->response([
                    'status' => false,
                    'id' => $id,
                    'message' => 'Data Terhapus',
                ], REST_CONTROLLER::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'ID tidak ditemukan',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }
        }
    }

    public function index_post()
    {
        try {
            $this->form_validation->set_rules('nama', 'Nama', 'trim|required');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|is_unique[mahasiswa.email]|valid_email');
            $this->form_validation->set_rules('jurusan', 'Jurusan', 'trim|required');
            $this->form_validation->set_rules('nrp', 'NRP', 'trim|required|numeric|is_unique[mahasiswa.nrp]|min_length[10]|max_length[10]');

            if (!$this->form_validation->run()) {
                throw new Exception(validation_errors());
            }

            $data = [
                'nrp' => $this->post('nrp'),
                'nama' => $this->post('nama'),
                'email' => $this->post('email'),
                'jurusan' => $this->post('jurusan'),
            ];

            if ($this->Mahasiswa_model->create_mahasiswa($data) > 0) {
                $this->response([
                    'status' => true,
                    'message' => 'Mahasiswa berhasil ditambahkan',
                ], REST_CONTROLLER::HTTP_CREATED);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Gagal menambahkan mahasiswa',
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
            $this->form_validation->set_rules('email', 'Email', 'trim|required|is_unique[mahasiswa.email]|valid_email');
            $this->form_validation->set_rules('jurusan', 'Jurusan', 'trim|required');
            $this->form_validation->set_rules('nrp', 'NRP', 'trim|required|numeric|is_unique[mahasiswa.nrp]|min_length[10]|max_length[10]');

            if (!$this->form_validation->run()) {
                throw new Exception(validation_errors());
            }

            $id = $this->put('id');
            $data = [
                'nrp' => $this->put('nrp'),
                'nama' => $this->put('nama'),
                'email' => $this->put('email'),
                'jurusan' => $this->put('jurusan'),
            ];

            if ($id === null) {
                $this->response([
                    'status' => false,
                    'message' => 'Provide an ID',
                ], REST_CONTROLLER::HTTP_BAD_REQUEST);
            }

            if ($this->Mahasiswa_model->update_mahasiswa($data, $id) > 0) {
                $this->response([
                    'status' => true,
                    'message' => 'Mahasiswa berhasil diubah',
                ], REST_CONTROLLER::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Gagal mengubah mahasiswa',
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
