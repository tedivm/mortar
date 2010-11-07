<?php

$config['domain'] = '{# domain #}';
$config['ssl'] = '{# ssl_domain #}'; // if you don't have one, put one anyways

$config['default_main_action'] = 'homepage';
$root = '{# path_root #}';

$config['url']['theme'] = $config['domain'] . 'themes/default/';
$config['url']['packages'] = $config['domain'] . 'packages/';
$config['path_theme'] = $root . 'themes/default/';

$config['path']['config'] = $root . 'config/';
$config['path']['language'] = $root . 'config/languages/';
$config['path']['mainclasses'] = $root . 'main_classes/';
$config['path']['packages'] = $root . 'packages/';
$config['path']['widgets'] = $root . 'widgets/';

$config['url']['admin'] = $config['ssl'] . 'admin.php';
$config['path']['cache'] = $root . 'temp/cache/';
$config['path']['library'] = $root . 'shared_library/';
$config['cache']['enabled'] = {# cache #};

/*

Form formatting stuff

*/

$config['form']['default_max_length'] = '75';
$config['form']['default_text_size'] = '25';
$config['form']['default_textfield_cols'] = '25';
$config['form']['default_textfield_rows'] = '5';
$config['form']['mult_rows'] = '5';



?>