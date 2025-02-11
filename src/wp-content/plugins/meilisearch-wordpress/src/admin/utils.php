<?php

use MeiliSearch\Client;
use MeiliSearch\Exceptions\ApiException;


function get_meilisearch_index($index_name = 'post' ){
    $meilisearch_options = get_option( 'meilisearch_option_name' );
    $client = new Client($meilisearch_options['meilisearch_url_0'], $meilisearch_options['meilisearch_private_key_1']);

    try {
        $index = $client->getIndex($index_name);
    } catch (ApiException $e) {
        $client->createIndex($index_name);
        $index = $client->getIndex($index_name);
    }

    return $index;

}

function index_post_after_update($post_ID, $post, $update){
    if ($post->post_status == 'publish') {
        index_post($post);
    }
}

function index_post_after_meta_update($post, $request){
    if ($post->post_status == 'publish') {
        index_post($post);
    }
}

function index_post($post){
    $index = get_meilisearch_index($post->post_type);
    $categories = [];
    foreach ($post->post_category as $category){
        array_push($categories, get_cat_name($category));
    }
    $document = [
        [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => strip_tags($post->post_content),
            'img' => get_the_post_thumbnail_url($post, array(100,100)),
            'url' => get_the_permalink($post),
            'tags' => $post->tags_input,
            'categories' => $categories,
        ],
    ];
    $index->addDocuments($document);
}

function delete_post_from_index($post_id){
    $post = get_post($post_id);
    $index = get_meilisearch_index($post->post_type);
    $index->deleteDocument($post_id);
}


function meilisearch_wordpress_activate(){

}

function index_all_posts($post_type = 'post' ){
    $index = get_meilisearch_index($post_type);
    $documents = [];
    $posts = get_posts(array('numberposts' => -1, 'post_type' => $post_type));
    foreach ($posts as $post){
        $categories = [];
        foreach ($post->post_category as $category){
            array_push($categories, get_cat_name($category));
        }
        $document = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => strip_tags($post->post_content),
            'img' => get_the_post_thumbnail_url($post, array(100,100)),
            'url' => get_the_permalink($post),
            'tags' => $post->tags_input,
            'categories' => $categories,
        ];
        array_push($documents, $document);
    }

    $index->addDocuments($documents);
}

function delete_index($post_type = 'post'){
    $index = get_meilisearch_index($post_type);
    $index->delete();
}

function count_indexed($post_type = 'post'){
    $index = get_meilisearch_index($post_type);
    $count = $index->stats();
    return $count['numberOfDocuments'];
}

?>
