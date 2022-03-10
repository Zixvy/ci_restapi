<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');

class Api_controller extends REST_Controller
{
    protected $exceptions = [];
    protected $is_valid_request = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function _remap($object_called, $arguments = [])
    {
        if (!in_array($object_called, $this->exceptions)) {
            if (!$this->jwt_auth()) {
                $this->response([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], REST_Controller::HTTP_UNAUTHORIZED);
            }
        }
        // Should we answer if not over SSL?
        if ($this->config->item('force_https') && $this->request->ssl === false) {
            $this->response([
                $this->config->item('rest_status_field_name') => false,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unsupported'),
            ], self::HTTP_FORBIDDEN);
        }

        // Remove the supported format from the function name e.g. index.json => index
        $object_called = preg_replace('/^(.*)\.(?:' . implode('|', array_keys($this->_supported_formats)) . ')$/', '$1', $object_called);

        $controller_method = $object_called . '_' . $this->request->method;
        // Does this method exist? If not, try executing an index method
        if (!method_exists($this, $controller_method)) {
            $controller_method = "index_" . $this->request->method;
            array_unshift($arguments, $object_called);
        }

        // Do we want to log this method (if allowed by config)?
        $log_method = !(isset($this->methods[$controller_method]['log']) && $this->methods[$controller_method]['log'] === false);

        // Use keys for this method?
        $use_key = !(isset($this->methods[$controller_method]['key']) && $this->methods[$controller_method]['key'] === false);

        // They provided a key, but it wasn't valid, so get them out of here
        if ($this->config->item('rest_enable_keys') && $use_key && $this->_allow === false) {
            if ($this->config->item('rest_enable_logging') && $log_method) {
                $this->_log_request();
            }

            // fix cross site to option request error
            if ($this->request->method == 'options') {
                exit;
            }

            $this->response([
                $this->config->item('rest_status_field_name') => false,
                $this->config->item('rest_message_field_name') => sprintf($this->lang->line('text_rest_invalid_api_key'), $this->rest->key),
            ], self::HTTP_FORBIDDEN);
        }

        // Check to see if this key has access to the requested controller
        if ($this->config->item('rest_enable_keys') && $use_key && empty($this->rest->key) === false && $this->_check_access() === false) {
            if ($this->config->item('rest_enable_logging') && $log_method) {
                $this->_log_request();
            }

            $this->response([
                $this->config->item('rest_status_field_name') => false,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_unauthorized'),
            ], self::HTTP_UNAUTHORIZED);
        }

        // Sure it exists, but can they do anything with it?
        if (!method_exists($this, $controller_method)) {
            $this->response([
                $this->config->item('rest_status_field_name') => false,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unknown_method'),
            ], self::HTTP_METHOD_NOT_ALLOWED);
        }

        // Doing key related stuff? Can only do it if they have a key right?
        if ($this->config->item('rest_enable_keys') && empty($this->rest->key) === false) {
            // Check the limit
            if ($this->config->item('rest_enable_limits') && $this->_check_limit($controller_method) === false) {
                $response = [$this->config->item('rest_status_field_name') => false, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_time_limit')];
                $this->response($response, self::HTTP_UNAUTHORIZED);
            }

            // If no level is set use 0, they probably aren't using permissions
            $level = isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;

            // If no level is set, or it is lower than/equal to the key's level
            $authorized = $level <= $this->rest->level;
            // IM TELLIN!
            if ($this->config->item('rest_enable_logging') && $log_method) {
                $this->_log_request($authorized);
            }
            if ($authorized === false) {
                // They don't have good enough perms
                $response = [$this->config->item('rest_status_field_name') => false, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_permissions')];
                $this->response($response, self::HTTP_UNAUTHORIZED);
            }
        }

        //check request limit by ip without login
        elseif ($this->config->item('rest_limits_method') == "IP_ADDRESS" && $this->config->item('rest_enable_limits') && $this->_check_limit($controller_method) === false) {
            $response = [$this->config->item('rest_status_field_name') => false, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_address_time_limit')];
            $this->response($response, self::HTTP_UNAUTHORIZED);
        }

        // No key stuff, but record that stuff is happening
        elseif ($this->config->item('rest_enable_logging') && $log_method) {
            $this->_log_request($authorized = true);
        }

        // Call the controller method and passed arguments
        try
        {
            if ($this->is_valid_request) {
                call_user_func_array([$this, $controller_method], $arguments);
            }
        } catch (Exception $ex) {
            if ($this->config->item('rest_handle_exceptions') === false) {
                throw $ex;
            }

            // If the method doesn't exist, then the error will be caught and an error response shown
            $_error = &load_class('Exceptions', 'core');
            $_error->show_exception($ex);
        }
    }
}
