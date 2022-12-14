<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}

$catid = $nv_Request->get_int('catid', 'post', 0);
$mod = $nv_Request->get_string('mod', 'post', '');
$new_vid = $nv_Request->get_int('new_vid', 'post', 0);
$content = 'NO_' . $catid;

list($catid, $parentid, $numsubcat, $curr_status) = $db->query('SELECT catid, parentid, numsubcat, status FROM ' . NV_PREFIXLANG . '_' . $module_data . '_cat WHERE catid=' . $catid)->fetch(3);
if ($catid > 0) {
    if ($mod == 'weight' and $new_vid > 0 and (defined('NV_IS_ADMIN_MODULE') or ($parentid > 0 and isset($array_cat_admin[$admin_id][$parentid]) and $array_cat_admin[$admin_id][$parentid]['admin'] == 1))) {
        $sql = 'SELECT catid FROM ' . NV_PREFIXLANG . '_' . $module_data . '_cat WHERE catid!=' . $catid . ' AND parentid=' . $parentid . ' ORDER BY weight ASC';
        $result = $db->query($sql);

        $weight = 0;
        while ($row = $result->fetch()) {
            ++$weight;
            if ($weight == $new_vid) {
                ++$weight;
            }
            $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET weight=' . $weight . ' WHERE catid=' . $row['catid'];
            $db->query($sql);
        }

        $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET weight=' . $new_vid . ' WHERE catid=' . $catid;
        $db->query($sql);

        nv_fix_cat_order();
        $content = 'OK_' . $parentid;
    } elseif (defined('NV_IS_ADMIN_MODULE') or (isset($array_cat_admin[$admin_id][$catid]) and $array_cat_admin[$admin_id][$catid]['add_content'] == 1)) {
        if ($mod == 'status' and in_array($new_vid, [0, 1, 2], true) and in_array((int) $curr_status, [0, 1, 2], true) and !(nv_get_mod_countrows() > NV_MIN_MEDIUM_SYSTEM_ROWS and ($new_vid == 0 or $curr_status == 0))) {
            // ?????i v???i c??c chuy??n m???c b??? kh??a b???i chuy??n m???c cha th?? kh??ng thay ?????i g??
            // ?????i v???i h??? th???ng l???n th?? kh??ng th??? ????nh ch???
            if (($new_vid == 0 or $curr_status == 0) and $new_vid != $curr_status) {
                $sudcatids = GetCatidInParent($catid);
                if ($new_vid == 0) {
                    // ????nh ch???
                    $query_update_cat = 'status=status+' . ($global_code_defined['cat_locked_status'] + 1);
                    $query_update_row = 'status=status+' . ($global_code_defined['row_locked_status'] + 1);
                } else {
                    // Cho ho???t ?????ng l???i
                    $query_update_cat = 'status=status-' . ($global_code_defined['cat_locked_status'] + 1);
                    $query_update_row = 'status=status-' . ($global_code_defined['row_locked_status'] + 1);

                    // T??m ra c??c chuy??n m???c v???n c??n b??? kh??a sau khi m??? kh??a chuy??n m???c n??y
                    $array_cat_locked = [];
                    foreach ($global_array_cat as $_catid_i => $_cat_value) {
                        if ($_catid_i != $catid) {
                            if (in_array((int) $_catid_i, array_map('intval', $sudcatids), true)) {
                                // C??c chuy??n m???c con s??? b??? t??c ?????ng th?? tr??? v??? tr???ng th??i status ban ?????u
                                $_cat_value['status'] -= ($global_code_defined['cat_locked_status'] + 1);
                            }
                            if (!in_array((int) $_cat_value['status'], array_map('intval', $global_code_defined['cat_visible_status']), true)) {
                                $array_cat_locked[] = $_catid_i;
                            }
                        }
                    }

                    // Khi m??? kh??a t????ng t??? c??ng kh??ng ghi log thay ?????i status c???a row
                }

                foreach ($sudcatids as $_catid) {
                    // Kh??a c??c chuy??n m???c con
                    if ($_catid != $catid) {
                        try {
                            $db->query('UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET ' . $query_update_cat . ' WHERE catid=' . $_catid);
                        } catch (Exception $e) {
                            trigger_error($e->getMessage());
                        }
                    }

                    /*
                     * Khi kh??a chuy??n m???c th?? ch??? c???n x??c ?????nh c??c b??i vi???t n??y c?? listcatid thu???c v??o $sudcatids th?? s??? l???p t???c b??? kh??a
                     * Kh??ng kh??a c??c b??i vi???t hi???n t???i ??ang b??? kh??a
                     */
                    if ($new_vid == 0) {
                        // Kh??a ??? b???ng rows
                        try {
                            $db->query('UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_rows SET ' . $query_update_row . ' WHERE status<=' . $global_code_defined['row_locked_status'] . ' AND FIND_IN_SET(' . $_catid . ',listcatid)');
                        } catch (Exception $e) {
                            trigger_error($e->getMessage());
                        }
                        // Kh??a ??? c??c b???ng cat
                        foreach ($global_array_cat as $_catid_i => $_cat_value) {
                            try {
                                $db->query('UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_' . $_catid_i . ' SET ' . $query_update_row . ' WHERE status<=' . $global_code_defined['row_locked_status'] . ' AND FIND_IN_SET(' . $_catid . ',listcatid)');
                            } catch (Exception $e) {
                                trigger_error($e->getMessage());
                            }
                        }
                        // Khi kh??a, kh??ng ghi log thay ?????i c???a row
                    } else {
                        // L???y c??c b??i vi???t thu???c chuy??n m???c ho???c chuy??n m???c con c???a chuy??n m???c ??ang b??? kh??a/m??? kh??a
                        $sql = 'SELECT id, catid, listcatid, status FROM ' . NV_PREFIXLANG . '_' . $module_data . '_rows WHERE FIND_IN_SET(' . $_catid . ', listcatid)';
                        $result = $db->query($sql);
                        while ($row = $result->fetch()) {
                            $row['listcatid'] = explode(',', $row['listcatid']);
                            // Xem th??? b??i vi???t n??y c??n thu???c chuy??n m???c n??o b??? kh??a kh??ng
                            if (array_intersect($array_cat_locked, $row['listcatid']) == [] and $row['status'] > $global_code_defined['row_locked_status']) {
                                // M??? kh??a ??? b???ng rows
                                try {
                                    $db->query('UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_rows SET ' . $query_update_row . ' WHERE id=' . $row['id']);
                                } catch (Exception $e) {
                                    trigger_error($e->getMessage());
                                }
                                // M??? kh??a c??c b???ng cat
                                foreach ($row['listcatid'] as $_catid_i) {
                                    try {
                                        $db->query('UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_' . $_catid_i . ' SET ' . $query_update_row . ' WHERE id=' . $row['id']);
                                    } catch (Exception $e) {
                                        trigger_error($e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET status=' . $new_vid . ' WHERE catid=' . $catid;
            $db->query($sql);

            $content = 'OK_' . $parentid;
        } elseif ($mod == 'numlinks' and $new_vid >= 0 and $new_vid <= 20) {
            $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET numlinks=' . $new_vid . ' WHERE catid=' . $catid;
            $db->query($sql);
            $content = 'OK_' . $parentid;
        } elseif ($mod == 'newday' and $new_vid >= 0 and $new_vid <= 10) {
            $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET newday=' . $new_vid . ' WHERE catid=' . $catid;
            $db->query($sql);
            $content = 'OK_' . $parentid;
        } elseif ($mod == 'viewcat' and $nv_Request->isset_request('new_vid', 'post')) {
            $viewcat = $nv_Request->get_title('new_vid', 'post');
            $array_viewcat = ($numsubcat > 0) ? $array_viewcat_full : $array_viewcat_nosub;
            if (!array_key_exists($viewcat, $array_viewcat)) {
                $viewcat = 'viewcat_page_new';
            }
            $stmt = $db->prepare('UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET viewcat= :viewcat WHERE catid=' . $catid);
            $stmt->bindParam(':viewcat', $viewcat, PDO::PARAM_STR);
            $stmt->execute();
            $content = 'OK_' . $parentid;
        }
    }
    $nv_Cache->delMod($module_name);
}

include NV_ROOTDIR . '/includes/header.php';
echo $content;
include NV_ROOTDIR . '/includes/footer.php';
