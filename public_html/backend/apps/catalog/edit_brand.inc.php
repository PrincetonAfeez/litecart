<?php

  if (!empty($_GET['brand_id'])) {
    $brand = new ent_brand($_GET['brand_id']);
  } else {
    $brand = new ent_brand();
  }

  if (empty($_POST)) {
    foreach ($brand->data as $key => $value) {
      $_POST[$key] = $value;
    }
  }

  document::$snippets['title'][] = !empty($brand->data['id']) ? language::translate('title_edit_brand', 'Edit Brand') :  language::translate('title_add_new_brand', 'Add New Brand');

  breadcrumbs::add(language::translate('title_catalog', 'Catalog'));
  breadcrumbs::add(language::translate('title_brands', 'Brands'), document::link(WS_DIR_ADMIN, ['doc' => 'brands'], ['app']));
  breadcrumbs::add(!empty($brand->data['id']) ? language::translate('title_edit_brand', 'Edit Brand') :  language::translate('title_add_new_brand', 'Add New Brand'));

  if (isset($_POST['save'])) {

    try {
      if (empty($_POST['name'])) throw new Exception(language::translate('error_name_missing', 'You must enter a name.'));

      if (!empty($_POST['code']) && database::num_rows(database::query("select id from ". DB_TABLE_PREFIX ."brands where id != '". (isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0) ."' and code = '". database::input($_POST['code']) ."' limit 1;"))) throw new Exception(language::translate('error_code_database_conflict', 'Another entry with the given code already exists in the database'));

      $fields = [
        'status',
        'featured',
        'code',
        'name',
        'short_description',
        'description',
        'keywords',
        'head_title',
        'h1_title',
        'meta_description',
        'link',
      ];

      foreach ($fields as $field) {
        if (isset($_POST[$field])) $brand->data[$field] = $_POST[$field];
      }

      $brand->save();

      if (!empty($_POST['delete_image'])) $brand->delete_image();

      if (is_uploaded_file($_FILES['image']['tmp_name'])) {
        $brand->save_image($_FILES['image']['tmp_name']);
      }

      notices::add('success', language::translate('success_changes_saved', 'Changes saved'));
      header('Location: '. document::link(WS_DIR_ADMIN, ['doc' => 'brands'], ['app']));
      exit;

    } catch (Exception $e) {
      notices::add('errors', $e->getMessage());
    }
  }

  if (isset($_POST['delete'])) {

    try {
      if (empty($brand->data['id'])) throw new Exception(language::translate('error_must_provide_brand', 'You must provide a brand'));

      $brand->delete();

      notices::add('success', language::translate('success_changes_saved', 'Changes saved'));
      header('Location: '. document::link(WS_DIR_ADMIN, ['doc' => 'brands'], ['app']));
      exit;

    } catch (Exception $e) {
      notices::add('errors', $e->getMessage());
    }
  }
?>

<div class="panel panel-app">
  <div class="panel-heading">
    <?php echo $app_icon; ?> <?php echo !empty($brand->data['id']) ? language::translate('title_edit_brand', 'Edit Brand') :  language::translate('title_add_new_brand', 'Add New Brand'); ?>
  </div>

  <ul class="nav nav-tabs">
    <li role="presentation" class="active"><a data-toggle="tab" href="#tab-general"><?php echo language::translate('title_general', 'General'); ?></a></li>
    <li role="presentation"><a data-toggle="tab" href="#tab-information"><?php echo language::translate('title_information', 'Information'); ?></a></li>
  </ul>

  <div class="panel-body">
    <?php echo functions::form_draw_form_begin('brand_form', 'post', false, true); ?>

      <div class="tab-content">
        <div id="tab-general" class="tab-pane active" style="max-width: 640px;">

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label><?php echo language::translate('title_status', 'Status'); ?></label>
                <?php echo functions::form_draw_toggle('status', (file_get_contents('php://input') != '') ? true : '1', 'e/d'); ?>
              </div>

              <div class="form-group">
                <label><?php echo language::translate('title_featured', 'Featured'); ?></label>
                <?php echo functions::form_draw_toggle('featured', isset($_POST['featured']) ? $_POST['featured'] : '1', 'y/n'); ?>
              </div>

              <div class="form-group">
                <label><?php echo language::translate('title_code', 'Code'); ?></label>
                <?php echo functions::form_draw_text_field('code', true); ?>
              </div>

              <div class="form-group">
                <label><?php echo language::translate('title_name', 'Name'); ?></label>
                <?php echo functions::form_draw_text_field('name', true); ?>
              </div>

              <div class="form-group">
                <label><?php echo language::translate('title_keywords', 'Keywords'); ?></label>
                <?php echo functions::form_draw_text_field('keywords', true); ?>
              </div>
            </div>

            <div class="col-md-6">
              <div id="image">
                <div class="thumbnail" style="margin-bottom: 15px;">
                  <img src="<?php echo document::href_link(WS_DIR_STORAGE . functions::image_thumbnail(FS_DIR_STORAGE . 'images/' . $brand->data['image'], 400, 100)); ?>" alt="" />
                </div>

                <div class="form-group">
                  <label><?php echo ((isset($brand->data['image']) && $brand->data['image'] != '') ? language::translate('title_new_image', 'New Image') : language::translate('title_image', 'Image')); ?></label>
                  <?php echo functions::form_draw_file_field('image', ''); ?>
                  <?php if (!empty($brand->data['image'])) { ?>
                  <div class="checkbox">
                    <label><?php echo functions::form_draw_checkbox('delete_image', 'true', true); ?> <?php echo language::translate('title_delete', 'Delete'); ?></label>
                  </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="tab-information" class="tab-pane" style="max-width: 640px;">

          <ul class="nav nav-tabs">
            <?php foreach (language::$languages as $language) { ?>
              <li<?php echo ($language['code'] == language::$selected['code']) ? ' class="active"' : ''; ?>><a data-toggle="tab" href="#<?php echo $language['code']; ?>"><?php echo $language['name']; ?></a></li>
            <?php } ?>
          </ul>

          <div class="tab-content">

            <?php foreach (array_keys(language::$languages) as $language_code) { ?>
            <div id="<?php echo $language_code; ?>" class="tab-pane fade in<?php echo ($language_code == language::$selected['code']) ? ' active' : ''; ?>">

              <div class="form-group">
                <label><?php echo language::translate('title_h1_title', 'H1 Title'); ?></label>
                <?php echo functions::form_draw_regional_input_field($language_code, 'h1_title['. $language_code .']', true, ''); ?>
              </div>

              <div class="form-group">
                <label><?php echo language::translate('title_short_description', 'Short Description'); ?></label>
                <?php echo functions::form_draw_regional_input_field($language_code, 'short_description['. $language_code .']', true); ?>
              </div>

              <div class="form-group">
                <label><?php echo language::translate('title_description', 'Description'); ?></label>
                <?php echo functions::form_draw_regional_wysiwyg_field($language_code, 'description['. $language_code .']', true, 'style="height: 240px;"'); ?>
              </div>

              <div class="form-group">
                <label><?php echo language::translate('title_link', 'Link'); ?></label>
                <?php echo functions::form_draw_regional_input_field($language_code, 'link['. $language_code .']', true); ?>
              </div>

              <div class="row">
                <div class="form-group col-md-6">
                  <label><?php echo language::translate('title_head_title', 'Head Title'); ?></label>
                  <?php echo functions::form_draw_regional_input_field($language_code, 'head_title['. $language_code .']', true, ''); ?>
                </div>

                <div class="form-group col-md-6">
                  <label><?php echo language::translate('title_meta_description', 'Meta Description'); ?></label>
                  <?php echo functions::form_draw_regional_input_field($language_code, 'meta_description['. $language_code .']', true); ?>
                </div>
              </div>
            </div>
            <?php } ?>

          </div>
        </div>
      </div>

      <div class="panel-action btn-group">
        <?php echo functions::form_draw_button('save', language::translate('title_save', 'Save'), 'submit', '', 'save'); ?>
        <?php echo functions::form_draw_button('cancel', language::translate('title_cancel', 'Cancel'), 'button', 'onclick="history.go(-1);"', 'cancel'); ?>
        <?php echo (!empty($brand->data['id'])) ? functions::form_draw_button('delete', language::translate('title_delete', 'Delete'), 'submit', 'onclick="if (!window.confirm(\''. language::translate('text_are_you_sure', 'Are you sure?') .'\')) return false;"', 'delete') : false; ?>
      </div>

    <?php echo functions::form_draw_form_end(); ?>
  </div>
</div>

<script>
  $('input[name="name"]').bind('input propertyChange', function(e){
    $('input[name^="head_title"]').attr('placeholder', $(this).val());
    $('input[name^="h1_title"]').attr('placeholder', $(this).val());
  }).trigger('input');

  $('input[name="image"]').change(function(e) {
    if ($(this).val() != '') {
      var oFReader = new FileReader();
      oFReader.readAsDataURL(this.files[0]);
      oFReader.onload = function(e){
        $('#image img').attr('src', e.target.result);
      };
    } else {
      $('#image img').attr('src', '<?php echo document::href_link(WS_DIR_STORAGE . functions::image_thumbnail(FS_DIR_STORAGE . 'images/' . $brand->data['image'], 400, 100)); ?>');
    }
  });

  $('input[name^="short_description"]').bind('input propertyChange', function(e){
    var language_code = $(this).attr('name').match(/\[(.*)\]$/)[1];
    $('input[name="meta_description['+language_code+']"]').attr('placeholder', $(this).val());
  }).trigger('input');
</script>