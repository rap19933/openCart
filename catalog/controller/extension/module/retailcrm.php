<?php

/**
 * Class ControllerModule
 *
 * @category RetailCrm
 * @package  RetailCrm
 * @author   RetailCrm <integration@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.retailcrm.ru/docs/Developers/ApiVersion3
 */
class ControllerExtensionModuleRetailcrm extends Controller
{
    /**
     * Create order on event
     *
     * @param int $order_id order identificator
     *
     * @return void
     */
    public function order_create($parameter1, $parameter2 = null, $parameter3 = null)
    {   
        $moduleTitle = $this->getModuleTitle();
        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $order_id = $parameter3;

        $data = $this->model_checkout_order->getOrder($order_id);
        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);
        foreach($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if(!empty($productOptions))
                $data['products'][$key]['option'] = $productOptions;
        }

        if (!isset($data['fromApi'])) {
            $this->load->model('setting/setting');
            $status = $this->model_setting_setting->getSetting($moduleTitle);
            if ($data['order_status_id'] > 0) {
                $data['order_status'] = $status[$moduleTitle . '_status'][$data['order_status_id']];
            }

            if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/order.php')) {
                $this->load->model('extension/retailcrm/custom/order');
                $this->model_extension_retailcrm_custom_order->sendToCrm($data, $data['order_id']);
            } else {
                $this->load->model('extension/retailcrm/order');
                $this->model_extension_retailcrm_order->sendToCrm($data, $data['order_id']);
            }
        }
    }

    /**
     * Update order on event
     *
     * @param int $order_id order identificator
     *
     * @return void
     */
    public function order_edit($parameter1, $parameter2 = null) {
        $moduleTitle = $this->getModuleTitle();
        $order_id = $parameter2[0];

        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $data = $this->model_checkout_order->getOrder($order_id);

        if($data['order_status_id'] == 0) return;

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);
        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);

        foreach($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if(!empty($productOptions))
                $data['products'][$key]['option'] = $productOptions;
        }

        if (!isset($data['fromApi'])) {
            $this->load->model('setting/setting');
            $status = $this->model_setting_setting->getSetting($moduleTitle);

            if ($data['order_status_id'] > 0) {
                $data['order_status'] = $status[$moduleTitle . '_status'][$data['order_status_id']];
            }

            if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/order.php')) {
                $this->load->model('extension/retailcrm/custom/order');
                $this->model_extension_retailcrm_custom_order->changeInCrm($data, $data['order_id']);
            } else {
                $this->load->model('extension/retailcrm/order');
                $this->model_extension_retailcrm_order->changeInCrm($data, $data['order_id']);
            }
        }
    }

    /**
     * Create customer on event
     *
     * @param int $customerId customer identificator
     *
     * @return void
     */
    public function customer_create($parameter1, $parameter2 = null, $parameter3 = null) {
        $this->load->model('account/customer');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');

        $customerId = $parameter3;
        $customer = $this->model_account_customer->getCustomer($customerId);

        if ($this->request->post) {
            $country = $this->model_localisation_country->getCountry($this->request->post['country_id']);
            $zone = $this->model_localisation_zone->getZone($this->request->post['zone_id']);

            $customer['address'] = array(
                'address_1' => $this->request->post['address_1'],
                'address_2' => $this->request->post['address_2'],
                'city' => $this->request->post['city'],
                'postcode' => $this->request->post['postcode'],
                'iso_code_2' => $country['iso_code_2'],
                'zone' => $zone['name']
            );
        }

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/customer.php')) {
            $this->load->model('extension/retailcrm/custom/customer');
            $this->model_extension_retailcrm_custom_customer->sendToCrm($customer);
        } else {
            $this->load->model('extension/retailcrm/customer');
            $this->model_extension_retailcrm_customer->sendToCrm($customer);
        }
    }

    /**
     * Update customer on event
     *
     * @param int $customerId customer identificator
     *
     * @return void
     */
    public function customer_edit($parameter1, $parameter2, $parameter3) {
        $customerId = $this->customer->getId();

        $this->load->model('account/customer');
        $customer = $this->model_account_customer->getCustomer($customerId);

        $this->load->model('account/address');
        $customer['address'] = $this->model_account_address->getAddress($customer['address_id']);

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/customer.php')) {
            $this->load->model('extension/retailcrm/custom/customer');
            $this->model_extension_retailcrm_custom_customer->changeInCrm($customer);
        } else {
            $this->load->model('extension/retailcrm/customer');
            $this->model_extension_retailcrm_customer->changeInCrm($customer);
        }
    }

    private function getModuleTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }
}
