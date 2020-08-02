<?php

  class url_product {

    function routes() {
      return [
        [
          'pattern' => '#^(?:.*-c-([0-9]+)/)?.*-p-([0-9]+)$#',
          'page' => 'product',
          'params' => 'category_id=$1&product_id=$2',
          'endpoint' => 'frontend',
          'options' => [
            'redirect' => true,
          ],
        ],
      ];
    }

    function rewrite(ent_link $link, $language_code) {

      if (empty($link->query['product_id'])) return;

      $product = reference::product($link->query['product_id'], $language_code);
      if (empty($product->id)) return $link;

      $new_path = '';

      if (!empty($link->query['category_id'])) {

        $category = reference::category($link->query['category_id'], $language_code);

        foreach ($category->path as $category_crumb) {
          $new_path .= functions::general_path_friendly($category_crumb->name, $language_code) .'-c-'. $category_crumb->id .'/';
        }

        $link->path = $new_path;
        $link->unset_query('category_id');

      } else if (!empty($link->query['brand_id'])) {

        $brand = reference::brand($link->query['brand_id'], $language_code);

        if (!empty($brand->id)) {
          $new_path .= functions::general_path_friendly($brand->name, $language_code) .'-m-'. $brand->id .'/';
        }

        $link->path = $new_path;
        $link->unset_query('brand_id');

      } else if (!empty($product->default_category_id)) {

        $category = reference::category($product->default_category_id, $language_code);

        foreach ($category->path as $category_crumb) {
          $new_path .= functions::general_path_friendly($category_crumb->name, $language_code) .'-c-'. $category_crumb->id .'/';
        }

        $new_path .= functions::general_path_friendly($category->name, $language_code) .'-c-'. $product->default_category_id .'/';
      }

      $new_path .= functions::general_path_friendly($product->name, $language_code) .'-p-'. $product->id;

      $link->path = $new_path;
      $link->unset_query('product_id');

      return $link;
    }
  }
