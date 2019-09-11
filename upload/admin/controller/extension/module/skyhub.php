<?php

include DIR_APPLICATION . 'controller/extension/module/skyhub/ProductOperations.php';

class ControllerExtensionModuleSkyhub extends Controller
{
    private $route = 'extension/module/skyhub';
    private $key_prefix = 'module_skyhub';

    public function index()
    {
        $data = $this->load->language($this->route);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate($data)) {
            $this->model_setting_setting->editSetting($this->key_prefix, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link($this->route, 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data[$this->key_prefix . '_email'] = $this->request->post[$this->key_prefix . '_email'] ?? $this->config->get($this->key_prefix . '_email');
        $data[$this->key_prefix . '_token'] = $this->request->post[$this->key_prefix . '_token'] ?? $this->config->get($this->key_prefix . '_token');
        $data[$this->key_prefix . '_percentage'] = $this->request->post[$this->key_prefix . '_percentage'] ?? $this->config->get($this->key_prefix . '_percentage');
        $data[$this->key_prefix . '_prazo'] = $this->request->post[$this->key_prefix . '_prazo'] ?? $this->config->get($this->key_prefix . '_prazo');
        $data[$this->key_prefix . '_status_update_product'] = $this->request->post[$this->key_prefix . '_status_update_product'] ?? $this->config->get($this->key_prefix . '_status_update_product');
        $data[$this->key_prefix . '_status'] = $this->request->post[$this->key_prefix . '_status'] ?? $this->config->get($this->key_prefix . '_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->route, $data));
    }

    /**
     * @param $data
     * @return bool
     */
    protected function validate(&$data)
    {
        if (!$this->user->hasPermission('modify', $this->route)) {
            $data['warning'] = $this->language->get('error_permission_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_email']) || filter_var($this->request->post[$this->key_prefix . '_email'], FILTER_VALIDATE_EMAIL)) {
            $data['error_email'] = $this->language->get('error_email_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_token'])) {
            $data['error_token'] = $this->language->get('error_token_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_percentage']) ||
                intval($this->request->post[$this->key_prefix . '_percentage']) < 0 || intval($this->request->post[$this->key_prefix . '_percentage']) > 100) {
            $data['error_percentage'] = $this->language->get('error_percentage_message');
            return false;
        }

        if (empty($this->request->post[$this->key_prefix . '_prazo']) ||
                intval($this->request->post[$this->key_prefix . '_prazo']) < 0 || intval($this->request->post[$this->key_prefix . '_prazo']) > 100) {
            $data['error_prazo'] = $this->language->get('error_prazo_message');
            return false;
        }

        return true;
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $this->load->model($this->route);
        $this->load->model('user/user_group');
        $this->load->model('setting/event');
        $this->load->model('extension/extension');

        $this->model_extension_extension->install('skyhub', $this->route);
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', $this->route);
        $this->model_setting_event->addEvent('product_create_skyhub', 'admin/model/catalog/product/addProduct/after', $this->route . '/addProduct');
        $this->model_setting_event->addEvent('product_update_skyhub', 'admin/model/catalog/product/editProduct/after', $this->route . '/updateProduct');
        $this->model_setting_event->addEvent('product_delete_skyhub', 'admin/model/catalog/product/deleteProduct/after', $this->route . '/deleteProduct');

        $this->load->model($this->route);

        $_ = '$this->model_' . $this->route;

        $_->criarTabelas();
    }

    public function unistall()
    {
        $this->load->model('setting/setting');
        $this->load->model($this->route);
        $this->load->model('user/user_group');
        $this->load->model('setting/event');
        $this->load->model('extension/extension');
        $this->load->model('extension/module');
        $this->load->model($this->route);

        $this->model_extension_extension->uninstall('skyhub', $this->route);
        $this->model_extension_module->deleteModulesByCode($this->route);
        $this->model_setting_setting->deleteSetting($this->route);

        $this->model_extension_event->deleteEvent('product_create_skyhub');
        $this->model_extension_event->deleteEvent('product_update_skyhub');
        $this->model_extension_event->deleteEvent('product_delete_skyhub');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', $this->route);

        $_ = '$this->model_' . $this->route;

        $_->removerTabelas();
    }

    public function addProduct(&$route, &$args, &$output)  {
        $skyhub_email =  $this->config->get($this->key_prefix . '_email');
        $skyhub_token = $this->config->get($this->key_prefix . '_token');
        $skyhub_percentage = $this->config->get($this->key_prefix . '_percentage');
        $skyhub_status = $this->config->get($this->key_prefix . '_status');
        $skyhub_update_product = $this->config->get($this->key_prefix . '_status_update_product');

        $_ = '$this->model_' . $this->route;

        if (!empty($output) && $skyhub_status && $skyhub_update_product) {
            $this->load->model($this->route);
            $product = $_->getProduct($output, $skyhub_percentage);
            $product['sku'] = $this->generateSkuForSkyHub($output, $_);

            $operation = new ProductOperations($product, $skyhub_email, $skyhub_token, ProductOperations::OPERATION_ADD);
            $operation->start();
        }
    }

    public function syncProducts() {
        $skyhub_email =  $this->config->get($this->key_prefix . '_email');
        $skyhub_token = $this->config->get($this->key_prefix . '_token');
        $skyhub_percentage = $this->config->get($this->key_prefix . '_percentage');
        $skyhub_status = $this->config->get($this->key_prefix . '_status');
        $skyhub_update_product = $this->config->get($this->key_prefix . '_status_update_product');

        $_ = '$this->model_' . $this->route;
        $idsProductsInSkyHub = $_->getAllProductsInSkyHub();
        $productsNotSended =  $_->getAllProductsOfStore($idsProductsInSkyHub);

        foreach ($productsNotSended as $productId) {
            if ($skyhub_status) {
                $this->load->model($this->route);
                $product = $_->getProduct($productId, $skyhub_percentage);
                $product['sku'] = $this->generateSkuForSkyHub($productId, $_);

                $operation = new ProductOperations($product, $skyhub_email, $skyhub_token, ProductOperations::OPERATION_ADD);
                $operation->start();
            }
        }
    }

    public function deleteProduct(&$route, &$args)  {
        $skyhub_email =  $this->config->get($this->key_prefix . '_email');
        $skyhub_token = $this->config->get($this->key_prefix . '_token');
        $skyhub_status = $this->config->get($this->key_prefix . '_status');
        $skyhub_update_product = $this->config->get($this->key_prefix . '_status_update_product');
        $product_id = $args[0];

        $_ = '$this->model_' . $this->route;
        $skyhub_sku = $this->getSkuForSkyHub($product_id, $_);

        if ($skyhub_sku && $skyhub_status && $skyhub_update_product) {
            $this->load->model($this->route);
            $product = ['sku' => $skyhub_sku];

            $operation = new ProductOperations($product, $skyhub_email, $skyhub_token, ProductOperations::OPERATION_REMOVE);
            $operation->start();
        }
    }

    public function updateProduct(&$route, &$args)  {
        $skyhub_email =  $this->config->get($this->key_prefix . '_email');
        $skyhub_token = $this->config->get($this->key_prefix . '_token');
        $skyhub_percentage = $this->config->get($this->key_prefix . '_percentage');
        $skyhub_status = $this->config->get($this->key_prefix . '_status');
        $skyhub_update_product = $this->config->get($this->key_prefix . '_status_update_product');
        $product_id = $args[0];

        $_ = '$this->model_' . $this->route;
        $skyhub_sku = $this->getSkuForSkyHub($product_id, $_);

        if ($skyhub_sku && $skyhub_status && $skyhub_update_product) {
            $this->load->model($this->route);
            $product = $_->getProduct($product_id, $skyhub_percentage);
            $product['sku'] = $skyhub_sku;

            $operation = new ProductOperations($product, $skyhub_email, $skyhub_token, ProductOperations::OPERATION_UPDATE);
            $operation->start();
        }
    }

    private function generateSkuForSkyHub($product_id, $model) {
        return $model->generateSku($product_id);
    }

    private function getSkuForSkyHub($product_id, $model) {
        return $model->findSku($product_id);
    }
}