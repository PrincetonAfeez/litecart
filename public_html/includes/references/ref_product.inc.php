<?php

  class ref_product {

    private $_currency_code;
    private $_language_codes;
    private $_customer_id;
    private $_data = [];

    function __construct($product_id, $language_code=null, $currency_code=null, $customer_id=null) {

      if (empty($language_code)) $language_code = language::$selected['code'];
      if (empty($currency_code)) $currency_code = currency::$selected['code'];
      if (empty($customer_id)) $customer_id = customer::$data['id'];

      $this->_data['id'] = (int)$product_id;
      $this->_language_codes = array_unique([
        $language_code,
        settings::get('default_language_code'),
        settings::get('store_language_code'),
      ]);
      $this->_currency_code = $currency_code;
      $this->_customer_id = $customer_id;
    }

    public function &__get($name) {

      if (array_key_exists($name, $this->_data)) {
        return $this->_data[$name];
      }

      $this->_data[$name] = null;
      $this->_load($name);

      return $this->_data[$name];
    }

    public function &__isset($name) {
      return $this->__get($name);
    }

    public function __set($name, $value) {
      trigger_error('Setting data is prohibited ('.$name.')', E_USER_ERROR);
    }

    private function _load($field) {

      switch($field) {

        case 'also_purchased_products':

          $this->_data['also_purchased_products'] = [];

            $query = database::query(
              "select oi.product_id, sum(oi.quantity) as total_quantity from ". DB_TABLE_PREFIX ."orders_items oi
              left join ". DB_TABLE_PREFIX ."products p on (p.id = oi.product_id)
              where p.status
              and (oi.product_id != 0 and oi.product_id != ". (int)$this->_data['id'] .")
              and order_id in (
                select distinct order_id as id from ". DB_TABLE_PREFIX ."orders_items
                where product_id = ". (int)$this->_data['id'] ."
              )
              group by oi.product_id
              order by total_quantity desc;"
            );

            while ($row = database::fetch($query)) {
              $this->_data['also_purchased_products'][$row['product_id']] = reference::product($row['product_id'], $this->_language_codes[0]);
            }

          break;

        case 'attributes':

          $this->_data['attributes'] = [];

          $product_attributes_query = database::query(
            "select pa.*, ag.code, agi.name as group_name, avi.name as value_name, pa.custom_value from ". DB_TABLE_PREFIX ."products_attributes pa
            left join ". DB_TABLE_PREFIX ."attribute_groups ag on (ag.id = pa.group_id)
            left join ". DB_TABLE_PREFIX ."attribute_groups_info agi on (agi.group_id = pa.group_id and agi.language_code = '". database::input($this->_language_codes[0]) ."')
            left join ". DB_TABLE_PREFIX ."attribute_values_info avi on (avi.value_id = pa.value_id and avi.language_code = '". database::input($this->_language_codes[0]) ."')
            where product_id = ". (int)$this->_data['id'] ."
            order by group_name, value_name, custom_value;"
          );

          while ($attribute = database::fetch($product_attributes_query)) {
            $this->_data['attributes'][$attribute['group_id'].'-'.$attribute['value_id']] = $attribute;
          }

          break;

        case 'categories':

          $this->_data['categories'] = [];

          $products_to_categories_query = database::query(
            "select * from ". DB_TABLE_PREFIX ."products_to_categories
            where product_id = ". (int)$this->_data['id'] .";"
          );

          while ($product_to_category = database::fetch($products_to_categories_query)) {
            $categories_info_query = database::query(
              "select * from ". DB_TABLE_PREFIX ."categories_info
              where category_id = ". (int)$product_to_category['category_id'] ."
              and language_code in ('". implode("', '", database::input($this->_language_codes)) ."')
              order by field(language_code, '". implode("', '", database::input($this->_language_codes)) ."');"
            );

            while ($row = database::fetch($categories_info_query)) {
              foreach ($row as $key => $value) {
                if (in_array($key, ['id', 'category_id', 'language_code'])) continue;
                if (empty($this->_data['categories'][$product_to_category['category_id']])) $this->_data['categories'][$product_to_category['category_id']] = $value;
              }
            }
          }

          break;

        case 'name':
        case 'short_description':
        case 'description':
        case 'technical_data':
        case 'head_title':
        case 'meta_description':

          $query = database::query(
            "select * from ". DB_TABLE_PREFIX ."products_info
            where product_id = ". (int)$this->_data['id'] ."
            and language_code in ('". implode("', '", database::input($this->_language_codes)) ."')
            order by field(language_code, '". implode("', '", database::input($this->_language_codes)) ."');"
          );

          while ($row = database::fetch($query)) {
            foreach ($row as $key => $value) {
              if (in_array($key, ['id', 'product_id', 'language_code'])) continue;
              if (empty($this->_data[$key])) $this->_data[$key] = $value;
            }
          }

          break;

        case 'campaign':

          $this->_data['campaign'] = [];

          $campaigns_query = database::query(
            "select *, min(if(`". database::input(currency::$selected['code']) ."`, `". database::input(currency::$selected['code']) ."` * ". (float)currency::$selected['value'] .", `". database::input(settings::get('store_currency_code')) ."`)) as price
            from ". DB_TABLE_PREFIX ."products_campaigns
            where product_id = ". (int)$this->_data['id'] ."
            and (year(start_date) < '1971' or start_date <= '". date('Y-m-d H:i:s') ."')
            and (year(end_date) < '1971' or end_date >= '". date('Y-m-d H:i:s') ."')
            limit 1;"
          );

          if ($campaign = database::fetch($campaigns_query)) {
            $this->_data['campaign'] = $campaign;
          }

          break;

        case 'default_category':

          $this->_data['default_category'] = false;

          if (empty($this->default_category_id)) return;

          $this->_data['default_category'] = reference::category($this->default_category_id, $this->_language_codes[0]);

          break;

        case 'delivery_status':

          $this->_data['delivery_status'] = [];

          $query = database::query(
            "select * from ". DB_TABLE_PREFIX ."delivery_statuses_info
            where delivery_status_id = ". (int)$this->_data['delivery_status_id'] ."
            and language_code in ('". implode("', '", database::input($this->_language_codes)) ."')
            order by field(language_code, '". implode("', '", database::input($this->_language_codes)) ."');"
          );

          while ($row = database::fetch($query)) {
            foreach ($row as $key => $value) {
              if (in_array($key, ['id', 'delivery_status_id', 'language_code'])) continue;
              if (empty($this->_data['delivery_status'][$key])) $this->_data['delivery_status'][$key] = $value;
            }
          }

          break;

        case 'images':

          $this->_data['images'] = [];

          $query = database::query(
            "select * from ". DB_TABLE_PREFIX ."products_images
            where product_id = ". (int)$this->_data['id'] ."
            order by priority asc, id asc;"
          );
          while ($row = database::fetch($query)) {
            $this->_data['images'][$row['id']] = $row['filename'];
          }

          break;

        case 'brand':

          $this->_data['brand'] = [];

          if (empty($this->_data['brand_id'])) return;

          $this->_data['brand'] = reference::brand($this->brand_id, $this->_language_codes[0]);

          break;

        case 'stock_options':

          $this->_data['stock_options'] = [];

          $query = database::query(
            "select * from ". DB_TABLE_PREFIX ."products_stock_options
            where product_id = ". (int)$this->_data['id'] ."
            ". (!empty($option_id) ? "and id = ". (int)$option_id ."" : '') ."
            order by priority asc;"
          );

          while ($row = database::fetch($query)) {

            if (empty($row['tax_class_id'])) {
              $row['tax_class_id'] = $this->tax_class_id;
            }

            if (empty($row['sku'])) {
              $row['sku'] = $this->sku;
            }

            if (empty($row['weight']) || $row['weight'] == 0) {
              $row['weight'] = $this->weight;
              $row['weight_class'] = $this->weight_class;
            }

            if (empty($row['dim_x'])) {
              $row['dim_x'] = $this->dim_x;
              $row['dim_y'] = $this->dim_y;
              $row['dim_z'] = $this->dim_z;
              $row['dim_class'] = $this->dim_class;
            }

            $row['name'] = [];

            foreach (explode(',', $row['combination']) as $combination) {
              list($group_id, $value_id) = explode('-', $combination);

              $options_values_query = database::query(
                "select * from ". DB_TABLE_PREFIX ."products_options_values pov
                left join ". DB_TABLE_PREFIX ."attribute_values_info avi on (avi.value_id = pov.value_id)
                where pov.value_id = ". (int)$value_id ."
                and avi.language_code in ('". implode("', '", database::input($this->_language_codes)) ."')
                order by field(avi.language_code, '". implode("', '", database::input($this->_language_codes)) ."');"
              );

              while ($option_value_info = database::fetch($options_values_query)) {
                foreach ($option_value_info as $key => $value) {
                  if (in_array($key, ['id', 'value_id', 'language_code'])) continue;
                  if (!is_array(empty($row[$key][$option_value_info['value_id']]))) continue;
                  if (empty($row[$key][$option_value_info['value_id']])) {
                    $row[$key][$option_value_info['value_id']] = $value;
                  }
                }
              }
            }

            $row['name'] = implode(',', $row['name']);

            $this->_data['stock_options'][$row['id']] = $row;
          }

          break;

        case 'parents':

          $this->_data['parents'] = [];

          $query = database::query(
            "select category_id from ". DB_TABLE_PREFIX ."products_to_categories
            where product_id = ". (int)$this->_data['id'] .";"
          );

          while ($row = database::fetch($query)) {
            $this->_data['parents'][$row['category_id']] = reference::category($row['category_id'], $this->_language_codes[0]);
          }

          break;

        case 'price':

          $this->_data['price'] = 0;

          $products_prices_query = database::query(
            "select * from ". DB_TABLE_PREFIX ."products_prices
            where product_id = ". (int)$this->_data['id'] ."
            limit 1;"
          );
          $product_price = database::fetch($products_prices_query);

          if ($product_price[$this->_currency_code] != 0) {
            $this->_data['price'] = currency::convert($product_price[$this->_currency_code], $this->_currency_code, settings::get('store_currency_code'));
          } else {
            $this->_data['price'] = $product_price[settings::get('store_currency_code')];
          }

          break;

        case 'quantity_unit':

          $this->_data['quantity_unit'] = [
            'id' => null,
            'decimals' => 0,
            'separate' => false,
            'name' => '',
          ];

          $quantity_unit_query = database::query(
            "select id, decimals, separate from ". DB_TABLE_PREFIX ."quantity_units
            where id = ". (int)$this->quantity_unit_id ."
            limit 1;"
          );

          if (!$this->_data['quantity_unit'] = database::fetch($quantity_unit_query)) return;

          $query = database::query(
            "select * from ". DB_TABLE_PREFIX ."quantity_units_info
            where quantity_unit_id = ". (int)$this->quantity_unit_id ."
            and language_code in ('". implode("', '", database::input($this->_language_codes)) ."')
            order by field(language_code, '". implode("', '", database::input($this->_language_codes)) ."');"
          );
          while ($row = database::fetch($query)) {
            foreach ($row as $key => $value) {
              if (in_array($key, ['id', 'quantity_unit_id', 'language_code'])) continue;
              if (empty($this->_data['quantity_unit'][$key])) $this->_data['quantity_unit'][$key] = $value;
            }
          }

          break;

        case 'supplier':

          $this->_data['supplier'] = null;

          if (!empty($this->supplier_id)) {
            $this->_data['supplier'] = reference::supplier($this->supplier_id);
          }

          break;

        case 'sold_out_status':

          $this->_data['sold_out_status'] = [];

          $query = database::query(
            "select id, orderable from ". DB_TABLE_PREFIX ."sold_out_statuses
            where id = ". (int)$this->sold_out_status_id ."
            limit 1;"
          );

          if (!$this->_data['sold_out_status'] = database::fetch($query)) return;

          $query = database::query(
            "select * from ". DB_TABLE_PREFIX ."sold_out_statuses_info
            where sold_out_status_id = ". (int)$this->_data['sold_out_status_id'] ."
            and language_code in ('". implode("', '", database::input($this->_language_codes)) ."')
            order by field(language_code, '". implode("', '", database::input($this->_language_codes)) ."');"
          );

          while ($row = database::fetch($query)) {
            foreach ($row as $key => $value) {
              if (in_array($key, ['id', 'sold_out_status_id', 'language_code'])) continue;
              if (empty($this->_data['sold_out_status'][$key])) $this->_data['sold_out_status'][$key] = $value;
            }
          }

          break;

        default:

          $query = database::query(
            "select * from ". DB_TABLE_PREFIX ."products
            where id = ". (int)$this->_data['id'] ."
            limit 1;"
          );

          if (!$row = database::fetch($query)) return;

          foreach ($row as $key => $value) {
            switch($key) {
              case 'keywords':
                $this->_data[$key] = !empty($row[$key]) ? explode(',', $row[$key]) : [];
                break;

              default:
                $this->_data[$key] = $value;
                break;
            }
          }

          break;
      }
    }

    public function adjust_stock($stock_item_id, $quantity) {
      trigger_error('catalog_stock_adjust() is deprecated. Use $ent_product->adjust_quantity()', E_USER_DEPRECATED);
      $product = new ent_product($this->_data['id']);
      return $product->adjust_quantity($quantity, $combination);
    }
  }
