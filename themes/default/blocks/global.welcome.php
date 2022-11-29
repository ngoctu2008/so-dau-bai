<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_MAINFILE')) {
    exit('Stop!!!');
}
function nv_welcome_config($module, $data_block, $lang_block)
{
    global $lang_global, $selectthemes;

    // Find language file
    if (file_exists(NV_ROOTDIR . '/themes/' . $selectthemes . '/language/' . NV_LANG_INTERFACE . '.php')) {
        include NV_ROOTDIR . '/themes/' . $selectthemes . '/language/' . NV_LANG_INTERFACE . '.php';
    }

    $html = '<div class="form-group">';
    $html .= '<label class="control-label col-sm-6">' . $lang_global['company_name'] . ':</label>';
    $html .= '<div class="col-sm-18"><input type="text" class="form-control" name="welcome_name" value="' . $data_block['welcome_name'] . '"></div>';

    $html .= '</div>';

    return $html;
}
function nv_company_info_submit()
{
    global $nv_Request;

    $return = [];
    $return['error'] = [];
    $return['config']['welcome_name'] = $nv_Request->get_title('welcome_name', 'post');

    return $return;
}

function nv_block_language($block_config)
{
    global $global_config, $lang_global, $language_array;

    if (file_exists(NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/blocks/global.block_language.tpl')) {
        $block_theme = $global_config['module_theme'];
    } elseif (file_exists(NV_ROOTDIR . '/themes/' . $global_config['site_theme'] . '/blocks/global.block_language.tpl')) {
        $block_theme = $global_config['site_theme'];
    } else {
        $block_theme = 'default';
    }

    $xtpl = new XTemplate('global.block_language.tpl', NV_ROOTDIR . '/themes/' . $block_theme . '/blocks');
    $xtpl->assign('NV_BASE_SITEURL', NV_BASE_SITEURL);
    $xtpl->assign('BLOCK_THEME', $block_theme);
    $xtpl->assign('SELECT_LANGUAGE', $lang_global['langsite']);


    $xtpl->parse('main');

    return $xtpl->text('main');
}

if (defined('NV_SYSTEM')) {
    $content = nv_block_language($block_config);
}