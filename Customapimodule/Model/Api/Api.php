<?php

// app/code/local/Envato/Customapimodule/Model/Product/Api.php
class Envato_Customapimodule_Model_Api_Api extends Mage_Api_Model_Resource_Abstract
{
    public function items($method, $params)
    {
        $params = json_decode($params, true);
        $result = $this->$method($params);
        if($method == 'help') return $result;
        return json_encode($result);
    }

    public function attributeitems()
    {
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();

        foreach ($attributes as $attribute) {
            $arr[] = $attribute->toArray();
        }
        return $arr;
    }

    public function createAttribute($params)
    {

        $_attribute_data = array(
            'attribute_code' => $params['attribute_code'],
            'is_global' => $params['is_global'],
            'frontend_input' => $params['frontend_input'], //'boolean',
            'default_value_text' => $params['default_value_text'],
            'default_value_yesno' => $params['default_value_yesno'],
            'type' => $params['type'],
            'backend_type' => $params['backend'],
            'default_value_date' => $params['default_value_date'],
            'default_value_textarea' => $params['default_value_textarea'],
            'is_unique' => $params['is_unique'],
            'is_required' => $params['is_required'],
            'apply_to' => $params['apply_to'], //array('grouped')
            'is_configurable' => $params['is_configurable'],
            'is_searchable' => $params['is_searchable'],
            'is_visible_in_advanced_search' => $params['is_visible_in_advanced_search'],
            'is_comparable' => $params['is_comparable'],
            'is_used_for_price_rules' => $params['is_used_for_price_rules'],
            'is_wysiwyg_enabled' => $params['is_wysiwyg_enabled'],
            'is_html_allowed_on_front' => $params['is_html_allowed_on_front'],
            'is_visible_on_front' => $params['is_visible_on_front'],
            'used_in_product_listing' => $params['used_in_product_listing'],
            'used_for_sort_by' => $params['used_for_sort_by'],
            'frontend_label' => $params['frontend_label']
        );
        $model = Mage::getModel('catalog/resource_eav_attribute');
        $model->addData($_attribute_data);
        $model->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());
        $model->setIsUserDefined(1);

        try {
            $model->save();
            return 'attribute created';
        } catch (Exception $e) {
            return 'attribute creating error';
        }
    }

    public function deleteAttribute($params)
    {
        $attributeCode = $params['attribute_code'];
        $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
        try {
            $setup->removeAttribute('catalog_product', $attributeCode);
            return "attribute deleted";
        } catch (Exception $e) {
            return "cant delete attribute";
        }
    }

    public function updateAttribute($params)
    {
        $model = Mage::getModel('catalog/resource_eav_attribute');
        $model->loadByCode(4, $params['attribute_code']);
        if ($model) {
            try {
                $model->setIsComparable($params['is_configurable'])
                    ->setIsVisibleOnFront($params['is_visible_on_front'])
                    ->setIsHtmlAllowedOnFront($params['is_html_allowed_on_front'])
                    ->setIsUsedForPriceRules($params['is_used_for_price_rules'])
                    ->setIsFilterableInSearch($params['is_searchable'])
                    ->setUsedInProductListing($params['used_in_product_listing'])
                    ->setUsedForSortBy($params['used_for_sort_by'])
                    ->setIsConfigurable($params['is_configurable'])
                    ->setIsVisibleInAdvancedSearch($params['is_visible_in_advanced_search'])
                    ->setIsWysiwygEnabled($params['is_wysiwyg_enabled'])
                    ->setIsUsedForPromoRules($params['is_wysiwyg_enabled'])
                    ->save();
                return 'attribute updated';
            } catch (Exception $e) {
                return "cant find attribute";
            }

        }
        return "cant load attribute model";
    }

    public function addOptionToSelectAttribute($params)
    {

        try {
            $arg_attribute = $params['attribute_code'];
            $arg_value = $params['value'];

            $checkAttr =  ['attribute_code' => $params['attribute_code']];
            $attrOptions = $this->getSelectAttributeOptions($checkAttr);
            foreach($attrOptions as $attrOption) {
                if($attrOption['label'] and $attrOption['label'] == $arg_value)   return "option exist";;

            }
            $attr_model = Mage::getModel('catalog/resource_eav_attribute');
            $attr = $attr_model->loadByCode('catalog_product', $arg_attribute);
            $attr_id = $attr->getAttributeId();

            $option['attribute_id'] = $attr_id;
            $option['value'][$arg_attribute][0] = $arg_value;

            $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
            $setup->addAttributeOption($option);
            return "option added";

        } catch (Exception $e) {
            return "cant add option to attribute";
        }

    }

    public function getSelectAttributeOptions($params)
    {
        try {
            $installer = new Mage_Eav_Model_Entity_Setup('core_setup');
            $installer->startSetup();

            $attributeCode = $params['attribute_code'];
            $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attributeCode);
            return $attribute->getSource()->getAllOptions(true, true);

        } catch (Exception $e) {
            return "cant get attribute options";
        }
    }

    public function deleteOptionFromSelectAttribute($params) {
        try {
            $optionId = $params['option_id'];

            $options['delete'][$optionId] = true;
            $options['value'][$optionId] = true;
            $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
            $setup->addAttributeOption($options);
            return 'option deleted';
        }
        catch (Exception $e) {
            return "cant delete attribute options";
        }

    }

    public function startReindex($params)
    {
        $indexingProcesses = Mage::getSingleton('index/indexer')->getProcessesCollection();
        $reindex_flag = false;
        foreach ($indexingProcesses as $process) {
            if ($process->getStatus() == 'require_reindex') {
                $reindex_flag = true;
                $process->reindexEverything();
            }
        }
        if (!$reindex_flag) {
            return "index is fresh";
        }
        return "reindex done";
    }

    public function clearCache($params) {
        Mage::app()->getCacheInstance()->flush();
        return 'cache refreshed';
    }

    public function killIndexAndCache($params)
    {
        $indexingProcesses = Mage::getSingleton('index/indexer')->getProcessesCollection();
        foreach ($indexingProcesses as $process) {
            if ($process->getStatus() == 'require_reindex') {
                $process->reindexEverything();
            }
        }
            Mage::app()->getCacheInstance()->flush();
            return "cache and index is fresh";
    }

    public function updateQtySmart($params){
        $productQty = $params['qty'];
        $productSku = $params['sku'];
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$productSku);
        $checkProductId = $product->getId();
        $stockItem =Mage::getModel('cataloginventory/stock_item')->loadByProduct($checkProductId);
        $productDate =  $stockItem->getData('updated_at');
        $toDate = date('Y-m-d H:i:s', strtotime('now'));
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('created_at', array('from'=>$productDate, 'to'=>$toDate));
        $ordersIds = $orders->getAllIds();
        $sumQty = 0;
        foreach($ordersIds as $order){
           $orderObj =  Mage::getModel('sales/order')->load($order);
            $is = $orderObj->getAllItems();
            foreach($is as $i):
                echo $i->getProductId();
            if($i->getProductId() == $checkProductId){
                $sumQty+=(int)$i->getData('qty_ordered');
            }
            endforeach;
        }
        $stockItem->setData('manage_stock', 1);
        $newQty = $productQty - $sumQty;
        $stockItem->setData('qty',$newQty);
        $setObserver = new Dzensoft_Utires_Model_Observer();
        $setObserver->itemHandle($product,$newQty);
      if ( $stockItem->save())

      return  'updated';
      return "error";
   }

    public function reciveOrderSmart($params){
        $increment_id =     str_replace ("\"","" , $params['increment_id']['param']  );
      //  return
        $type =     str_replace ("\"","" , $params['increment_id']['type']);
        $status = $params['status'];
        $imported  = $params['imported'];
        $orders = Mage::getModel('sales/order')->getCollection();
        if($status)
            $orders->addFieldToFilter('status', $status);
        if($imported)
            $orders->addFieldToFilter('imported', $imported);
        if($type and $increment_id)
            $orders->addFieldToFilter('increment_id', array($type => $increment_id));

//return $orders->getCollection()->getSelect();
        $orders_list = array();
        foreach($orders as $order){
            $orders_list[] =  $order->getData();
        }
       return $orders_list;
    }

    public function orderInfoSmart($params){
        $increment_id = $params['increment_id'];
        //$increment_id =     str_replace ("\"","" , $increment_id);
        $orders = Mage::getModel('sales/order')->getCollection();
        $orders->addFieldToFilter('increment_id', $increment_id);
        $orders->getSelect()->columns("*")->joinLeft(array('trs_sales_flat_order_item' => 'trs_sales_flat_order_item'),
            'entity_id = trs_sales_flat_order_item.order_id', array());
        $i = 0;
        $finalData = array();
        foreach($orders as $order) {
            if(!$i){
                $i++;
                $finalData['order_id'] = $order->getData('entity_id');
                $finalData['increment_id'] = $order->getData('increment_id');
                $finalData['ext_order_id'] = $order->getData('ext_order_id');
                $finalData['state'] = $order->getData('state');
                $finalData['created_at'] = $order->getData('created_at');
                $finalData['shipping_address'] = $order->getShippingAddress()->getData();
                $finalData['billing_address'] = $order->getBillingAddress()->getData();

            }
            $j = 0;
            foreach ($order->getAllItems() as $item) {
                $top_product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                $finalData['items'][$j] = array(
                    'name'          => $item->getName(),
                    'sku'           => $item->getSku(),
                    'price'         => $item->getPrice(),
                    'ordered_qty'   => $item->getQtyOrdered(),
                    'weight'   => $item->getWeight(),
                    'setqty' => $top_product->getData('setqty')
               );
                if($top_product->getData('setqty') > 1){
                    $realSku = $top_product->getData('setproductsku');
                    $product = Mage::getModel('catalog/product');
                    $id = Mage::getModel('catalog/product')->getResource()->getIdBySku($realSku);
                    if ($id) {
                        $product->load($id);
                        $finalData['items'][$j] = array(
                            'name'          => $product->getName(),
                            'sku'           => $product->getSku(),
                            'price'         => $item->getPrice() / $top_product->getData('setqty'),
                            'ordered_qty'   => $top_product->getData('setqty'),
                            'weight'   => $item->getWeight(),
                            'qty' => $top_product->getData('setqty')
                        );
                    }
                }

            $j++;
            }
            return $finalData;
        }
    }

    public function updateImported($params){

        $increment_id = $params['increment_id'];
        $result = 'false';
        $orders = Mage::getModel('sales/order')->getCollection()
        ->addFieldToFilter('increment_id', array('like' => $increment_id));
        foreach($orders as $order){
            $order->setData('imported', '1');
            $order->save();
            $result = 'true';
        }
        return $result;
    }


    public function help() {
        return "<a href='https://www.youtube.com/watch?v=8MUqtvaT5bw'>good sond</a>";
    }

    public function __call($name, $params)
    {
        return 'cant find function ' . $name;
    }
}