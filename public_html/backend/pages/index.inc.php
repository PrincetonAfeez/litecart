<?php

  route::load(FS_DIR_APP . 'includes/routes/url_*.inc.php'); // Needed for url rewriting

  user::require_login();

  breadcrumbs::reset();
  breadcrumbs::add(language::translate('title_dashboard', 'Dashboard'), WS_DIR_ADMIN);

// Build apps list menu
  $box_apps_menu = new ent_view();
  $box_apps_menu->snippets['apps'] = [];

  foreach (functions::admin_get_apps() as $app) {

    if (!empty(user::$data['permissions']) && empty(user::$data['permissions'][$app['code']]['status'])) continue;

    $box_apps_menu->snippets['apps'][$app['code']] = [
      'code' => $app['code'],
      'name' => $app['name'],
      'link' => document::link(WS_DIR_ADMIN, ['app' => $app['code'], 'doc' => $app['default']]),
      'theme' => [
        'icon' => !(empty($app['theme']['icon'])) ? $app['theme']['icon'] : 'fa-plus',
        'color' => !(empty($app['theme']['color'])) ? $app['theme']['color'] : '#97a3b5',
      ],
      'active' => (isset($_GET['app']) && $_GET['app'] == $app['code']) ? true : false,
      'menu' => [],
    ];

    if (!empty($app['menu'])) {
      foreach ($app['menu'] as $item) {

        if (!empty(user::$data['permissions']) && (empty(user::$data['permissions'][$app['code']]['status']) || !in_array($item['doc'], user::$data['permissions'][$app['code']]['docs']))) continue;

        $params = !empty($item['params']) ? array_merge(['app' => $app['code'], 'doc' => $item['doc']], $item['params']) : ['app' => $app['code'], 'doc' => $item['doc']];

        if (isset($_GET['doc']) && $_GET['doc'] == $item['doc']) {
          $selected = true;
          if (!empty($item['params'])) {
            foreach ($item['params'] as $param => $value) {
              if (!isset($_GET[$param]) || $_GET[$param] != $value) {
                $selected = false;
                break;
              }
            }
          }
        } else {
          $selected = false;
        }

        $box_apps_menu->snippets['apps'][$app['code']]['menu'][] = [
          'title' => $item['title'],
          'doc' => $item['doc'],
          'link' => document::link(WS_DIR_ADMIN, ['app' => $app['code'], 'doc' => $item['doc']] + (!empty($item['params']) ? $item['params'] : [])),
          'active' => $selected ? true : false,
        ];
      }
    }
  }

  document::$snippets['box_apps_menu'] = $box_apps_menu->stitch('views/box_apps_menu.inc.php');

// Start page
  if (empty($_GET['app'])) {

  document::$snippets['title'][] = language::translate('title_dashboard', 'Dashboard');

    if (file_exists(FS_DIR_APP . 'install/')) {
      notices::add('warnings', language::translate('warning_install_folder_exists', 'Warning: The installation directory is still available and should be deleted.'), 'install_folder');
    }

    if (settings::get('maintenance_mode')) {
      notices::add('notices', language::translate('reminder_store_in_maintenance_mode', 'The store is in maintenance mode.'));
    }

  // Widgets
    $box_widgets = new ent_view();
    $box_widgets->snippets['widgets'] = [];

    foreach (functions::admin_get_widgets() as $widget) {
      ob_start();
      include vmod::check($widget['directory'] . $widget['file']);

      $box_widgets->snippets['widgets'][] = [
        'code' => $widget['code'],
        'content' => ob_get_clean(),
      ];
    }

    echo $box_widgets->stitch('views/box_widgets.inc.php');

// App content
  } else {

    if (empty(user::$data['permissions']) || (!empty(user::$data['permissions'][$_GET['app']]['status']) && in_array($_GET['doc'], user::$data['permissions'][$_GET['app']]['docs']))) {

      if (empty($_GET['doc'])) $_GET['doc'] = $app_config['default'];

      require vmod::check(FS_DIR_ADMIN .'apps/'. $_GET['app'] .'/config.inc.php');

      if (empty($app_config['docs'][$_GET['doc']])) {
        http_response_code(404);
        die('Doc not found');
      }

      if (empty($app_config['theme']['icon']) && !empty($app_config['icon'])) $app_config['theme']['icon'] = $app_config['icon']; // Backwards compatibility

      breadcrumbs::add($app_config['name'], document::link(WS_DIR_ADMIN, ['app' => $_GET['app'], 'doc' => $app_config['default']]));

      $_page = new ent_view();
      $_page->snippets = [
        'app' => $_GET['app'],
        'doc' => $_GET['doc'],
        'theme' => [
          'icon' => !empty($app_config['theme']['icon']) ? $app_config['theme']['icon'] : 'fa-plus',
          'color' => !empty($app_config['theme']['color']) ? $app_config['theme']['color'] : '#97a3b5',
        ],
      ];

      document::$snippets['help_link'] = document::link('https://wiki.litecart.net/');

      $app_icon = '<span class="fa-stack icon-wrapper">' . PHP_EOL
                . '  ' . functions::draw_fonticon('fa-circle fa-stack-2x icon-background', 'style="color: '. $_page->snippets['theme']['color'] .';"') . PHP_EOL
                . '  ' . functions::draw_fonticon($_page->snippets['theme']['icon'] .' fa-stack-1x icon', 'style="color: #fff;"') . PHP_EOL
                . '</span>';

      ob_start();
      if (!empty($_GET['doc'])) {
      if (empty($app_config['docs'][$_GET['doc']]) || !file_exists(FS_DIR_ADMIN .'apps/'. $_GET['app'] . '/' . $app_config['docs'][$_GET['doc']])) trigger_error($_GET['app'] .'/'. htmlspecialchars($_GET['doc']) . ' is not a valid admin document', E_USER_ERROR);
      include vmod::check(FS_DIR_ADMIN .'apps/'. $_GET['app'] .'/'. $app_config['docs'][$_GET['doc']]);
      } else {
      include vmod::check(FS_DIR_ADMIN .'apps/'. $_GET['app'] .'/'. $app_config['docs'][$app_config['default']]);
      }
      $_page->snippets['doc'] = ob_get_clean();


      if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        echo $_page->stitch('pages/doc.inc.php');
      } else {
        echo $_page->snippets['doc'];
      }

    } else {
      echo '<p>'. language::translate('title_access_denied', 'Access Denied') .'</p>';
    }
  }