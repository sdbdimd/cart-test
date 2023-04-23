<?php

use Tygh\Registry;
use Tygh\Languages\Languages;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

function fn_update_tz_document($document_data, $description, $doc_id, $main_lang_code)
{
    $document_data['company_id'] = fn_get_company_id('tz_categories_docs', 'doc_category_id', $document_data['doc_category_id']);

    if (!empty($doc_id)) {
        db_query("UPDATE ?:tz_documents SET ?u WHERE doc_id = ?i", $document_data, $doc_id);

        $result = db_query("UPDATE ?:tz_documents_description SET ?u WHERE doc_id = ?i AND lang_code = ?s", $description, $doc_id, $main_lang_code);

        if (empty($result)) {
            $description['lang_code'] = $main_lang_code;
            $description['doc_id'] = $doc_id;
            db_query("REPLACE INTO ?:tz_documents_description ?e", $description);
        }
    } else {
        $document_data['create_date'] = time();

        $description['doc_id'] = $doc_id = db_query("INSERT INTO ?:tz_documents ?e", $document_data);

        $lang_codes = Languages::getAll();

        foreach ($lang_codes as $lang_code => $lang_data) {
            $description['lang_code'] = $lang_code;
            db_query("INSERT INTO ?:tz_documents_description ?e", $description);
        }
    }

    return $doc_id;
}

function fn_update_tz_documents_category($category_data, $description, $doc_category_id, $main_lang_code)
{
    if (empty($category_data['company_id'])) {
        $category_data['company_id'] = Registry::get('runtime.simple_ultimate') ? Registry::get('runtime.forced_company_id') : Registry::get('runtime.company_id');
    }

    if (!empty($doc_category_id)) {
        db_query("UPDATE ?:tz_categories_docs SET ?u WHERE doc_category_id = ?i", $category_data, $doc_category_id);

        db_query("UPDATE ?:tz_documents SET company_id = ?i WHERE doc_category_id = ?i", $category_data['company_id'], $doc_category_id);

        $result = db_query("UPDATE ?:tz_categories_docs_description SET ?u WHERE doc_category_id = ?i AND lang_code = ?s", $description, $doc_category_id, $main_lang_code);

        if (empty($result)) {
            $description['lang_code'] = $main_lang_code;
            $description['doc_category_id'] = $doc_category_id;
            db_query("REPLACE INTO ?:tz_categories_docs_description ?e", $description);
        }
    } else {
        $description['doc_category_id'] = $doc_category_id = db_query("INSERT INTO ?:tz_categories_docs ?e", $category_data);

        $lang_codes = Languages::getAll();

        foreach ($lang_codes as $lang_code => $lang_data) {
            $description['lang_code'] = $lang_code;
            db_query("INSERT INTO ?:tz_categories_docs_description ?e", $description);
        }
    }

    return $doc_category_id;
}

function fn_get_tz_categories_docs($lang_code, $doc_category_id = '', $for_docs = '', $company_id = '')
{
    if (empty($company_id)) {
        $company_id = Registry::get('runtime.simple_ultimate') ? Registry::get('runtime.forced_company_id') : Registry::get('runtime.company_id');
    }

    $condition = '';

    if (!empty($company_id)) {
        if (!empty($for_docs)) {
            $condition .= db_quote(" AND ?:tz_categories_docs.company_id = ?i", $company_id);
        } else {
            $condition .= db_quote(" AND (?:tz_categories_docs.company_id = ?i OR ?:tz_categories_docs.company_id = 0)", $company_id);
        }
    }

    if (!empty($doc_category_id)) {
        $condition .= db_quote(" AND ?:tz_categories_docs.doc_category_id = ?i", $doc_category_id);
    }

    $categories = db_get_array("SELECT ?:tz_categories_docs.doc_category_id, ?:tz_categories_docs.company_id, ?:tz_categories_docs.status, ?:tz_categories_docs_description.category_name FROM ?:tz_categories_docs LEFT JOIN ?:tz_categories_docs_description ON ?:tz_categories_docs.doc_category_id = ?:tz_categories_docs_description.doc_category_id AND ?:tz_categories_docs_description.lang_code = ?s WHERE 1 $condition", $lang_code);

    return $categories;
}

function fn_get_tz_documents($params, $items_per_page = 0, $lang_code)
{
    if (empty($params['company_id'])) {
        $company_id = Registry::get('runtime.simple_ultimate') ? Registry::get('runtime.forced_company_id') : Registry::get('runtime.company_id');
    } else {
        $company_id = $params['company_id'];
    }

    $default_params = array(
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $fields = array(
        '?:tz_documents.doc_id',
        '?:tz_documents.doc_category_id',
        '?:tz_documents.type',
        '?:tz_documents.create_date',
        '?:tz_documents.status',
        '?:tz_documents.company_id',
        '?:tz_documents_description.name',
        '?:tz_documents_description.text',
        '?:tz_categories_docs_description.category_name'
    );

    $sortings = array(
        'name' => '?:tz_documents_description.name',
        'category' => '?:tz_categories_docs_description.category_name',
        'type' => '?:tz_documents.type',
        'create_date' => '?:tz_documents.create_date',
        'status' => '?:tz_documents.status'
    );

    $sorting = db_sort($params, $sortings, 'create_date', 'desc');

    $join = db_quote(" LEFT JOIN ?:tz_categories_docs_description ON ?:tz_documents.doc_category_id = ?:tz_categories_docs_description.doc_category_id AND ?:tz_categories_docs_description.lang_code = ?s", $lang_code);
    $join .= db_quote(" LEFT JOIN ?:tz_documents_description ON ?:tz_documents_description.doc_id = ?:tz_documents.doc_id AND ?:tz_documents_description.lang_code = ?s", $lang_code);
    $join .= " LEFT JOIN ?:tz_categories_docs ON ?:tz_categories_docs.doc_category_id = ?:tz_documents.doc_category_id";
    
    if (AREA == 'C') {
        $condition = " AND ?:tz_categories_docs.status = 'A'";
    } else {
        $condition = '';
    }

    if (!empty($company_id)) {
        if (fn_allowed_for('MULTIVENDOR') && AREA == 'C') {
            $condition .= db_quote(" AND ?:tz_documents.company_id = ?i", $company_id);
        } else {
            $condition .= db_quote(" AND (?:tz_documents.company_id = ?i OR (?:tz_documents.company_id = 0 AND ?:tz_documents.status != 'D'))", $company_id);
        }
    } elseif (fn_allowed_for('MULTIVENDOR') && AREA == 'C' && empty($params['doc_id'])) {
        $condition .= " AND ?:tz_documents.company_id = 0";
    }

    if (!empty($params['doc_id'])) {
        $condition .= db_quote(" AND ?:tz_documents.doc_id = ?i", $params['doc_id']);
    }

    if (AREA == 'C') {
        if (
            (fn_allowed_for('MULTIVENDOR') 
                && Registry::get('user_info.user_type') == 'V')
            || (fn_allowed_for('ULTIMATE')
                && Registry::get('user_info.user_type') == 'A'
                && Registry::get('user_info.company_id'))
        ) {
            $condition .= db_quote(" AND (?:tz_documents.type = 'G' OR (?:tz_documents.company_id = ?i OR ?:tz_documents.company_id = 0))", Registry::get('user_info.company_id'));
        } elseif (Registry::get('user_info.user_type') != 'A') {
            $condition .= " AND ?:tz_documents.type = 'G'";
        }
    }

    if (!empty($params['text'])) {
        $condition .= db_quote(" AND (?:tz_documents_description.name LIKE ?s OR ?:tz_documents_description.text LIKE ?s)", '%'.$params['text'].'%', '%'.$params['text'].'%');
    }

    if (!empty($params['file_name'])) {
        $join .= db_quote(" LEFT JOIN ?:tz_files_docs ON ?:tz_documents.doc_id = ?:tz_files_docs.doc_id AND ?:tz_files_docs.lang_code = ?s", $lang_code);
        $condition .= db_quote(" AND ?:tz_files_docs.file_name LIKE ?s", '%'.$params['file_name'].'%');
    }

    if (!empty($params['categories'])) {
        $condition .= db_quote(" AND ?:tz_documents.doc_category_id IN (?a)", $params['categories']);
    }

    if (!empty($params['min_create_date'])) {
        $min_create_date = fn_parse_date($params['min_create_date']);
        $condition .= db_quote(" AND ?:tz_documents.create_date >= ?s", $min_create_date);
    }

    if (!empty($params['max_create_date'])) {
        $max_create_date = fn_parse_date($params['max_create_date']);
        $condition .= db_quote(" AND ?:tz_documents.create_date <= ?s", $max_create_date);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND ?:tz_documents.status IN (?a)", $params['status']);
    }

    $limit = '';
    
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(DISTINCT (?:tz_documents.doc_id)) FROM ?:tz_documents $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $group = ' GROUP BY ?:tz_documents.doc_id';

    $documents_data = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:tz_documents $join WHERE 1 $condition $group $sorting $limit");

    return array($documents_data, $params);
}

function fn_get_tz_documents_files_info($doc_id, $lang_code)
{
    $files = db_get_array("SELECT file_id, file_name, path_file FROM ?:tz_files_docs WHERE doc_id = ?i AND lang_code = ?s", $doc_id, $lang_code);

    foreach ($files as $k => $file) {
        $stat = stat($file['path_file']);
        $files[$k]['ctime'] = $stat['ctime'];
        $files[$k]['size'] = $stat['size'];
    }

    return $files;
}

function fn_delete_tz_documents_files($params)
{
    $base_name = fn_basename($params['file']);

    $path = fn_get_files_dir_path('tz_documents');

    $path_file = $path . $params['doc_id'] . '/' . DESCR_SL . '/' . $base_name;

    db_query("DELETE FROM ?:tz_files_docs WHERE file_id = ?i", $params['file_id']);

    return fn_rm($path_file);
}

function fn_delete_tz_document($doc_id)
{
    $path = fn_get_files_dir_path('tz_documents');

    $path .= $doc_id . '/';

    db_query("DELETE FROM ?:tz_documents WHERE doc_id = ?i", $doc_id);
    db_query("DELETE FROM ?:tz_documents_description WHERE doc_id = ?i", $doc_id);

    return fn_rm($path);
}

function fn_delete_tz_category($doc_category_id)
{
    $doc_ids = db_get_fields("SELECT doc_id FROM ?:tz_documents WHERE doc_category_id = ?i", $doc_category_id);

    foreach ($doc_ids as $doc_id) {
        fn_delete_tz_document($doc_id);
    }

    db_query("DELETE FROM ?:tz_categories_docs WHERE doc_category_id = ?i", $doc_category_id);
    db_query("DELETE FROM ?:tz_categories_docs_description WHERE doc_category_id = ?i", $doc_category_id);
}

function fn_get_tz_categories_docs_for_search($lang_code, $company_id = '')
{
    if (empty($company_id)) {
        $company_id = Registry::get('runtime.simple_ultimate') ? Registry::get('runtime.forced_company_id') : Registry::get('runtime.company_id');
    }

    if (AREA == 'C') {
        $condition = " AND ?:tz_documents.status = 'A'";
    } else {
        $condition = '';
    }

    if (!empty($company_id)) {
        if (fn_allowed_for('MULTIVENDOR') && AREA == 'C') {
            $condition .= db_quote(" AND ?:tz_documents.company_id = ?i", $company_id);
        } else {
            $condition .= db_quote(" AND (?:tz_documents.company_id = ?i OR (?:tz_documents.company_id = 0 AND ?:tz_documents.status = 'A'))", $company_id);
        }
    } elseif (fn_allowed_for('MULTIVENDOR') && AREA == 'C') {
        $condition .= " AND ?:tz_documents.company_id = 0";
    }

    $categories = db_get_array(
        "SELECT ?:tz_documents.doc_category_id, ?:tz_categories_docs_description.category_name FROM ?:tz_documents 
    		LEFT JOIN ?:tz_categories_docs_description ON ?:tz_documents.doc_category_id = ?:tz_categories_docs_description.doc_category_id AND ?:tz_categories_docs_description.lang_code = ?s
    		LEFT JOIN ?:tz_categories_docs ON ?:tz_documents.doc_category_id = ?:tz_categories_docs.doc_category_id 
    		WHERE 1 $condition
    		GROUP BY ?:tz_documents.doc_category_id", $lang_code, $company_id);

    return $categories;
}

function fn_uninstall_tz_documents()
{
    fn_rm(fn_get_files_dir_path('tz_documents'));
}