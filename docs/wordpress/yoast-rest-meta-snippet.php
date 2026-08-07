<?php

/**
 * Yoast SEO — Buka Akses Meta lewat REST API
 * ===========================================
 *
 * WordPress REST API MENOLAK menyimpan custom meta field yang tidak diregistrasi
 * dengan `show_in_rest => true`. Yoast SEO tidak meregistrasi kunci meta-nya
 * secara default, sehingga permintaan dari portal artikel Laravel tampak sukses
 * (HTTP 200) tetapi data Yoast (SEO title, meta description, focus keyword)
 * TIDAK pernah tersimpan.
 *
 * Pasang snippet ini SEKALI di situs WordPress target:
 *   - via plugin "Code Snippets", ATAU
 *   - tempel ke functions.php tema aktif, ATAU
 *   - simpan sebagai file di wp-content/mu-plugins/yoast-rest-meta.php
 *
 * Setelah aktif, /wp-json/wp/v2/posts/{id} menerima request terpisah berisi:
 *   { "meta": { "_yoast_wpseo_title": "...", "_yoast_wpseo_metadesc": "...", "_yoast_wpseo_focuskw": "..." } }
 *
 * @see docs/ARCHITECTURE.md  (alur publikasi Laravel → WordPress)
 */
add_action('rest_api_init', function () {
    $post_types = ['post', 'page'];

    $meta_keys = [
        '_yoast_wpseo_title',
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_focuskw',
        // Skor & metrik tambahan yang dikirim portal Laravel (opsional):
        '_yoast_wpseo_linkdex',
        '_yoast_wpseo_content_score',
        '_yoast_wpseo_estimated_reading_time_minutes',
    ];

    foreach ($post_types as $post_type) {
        foreach ($meta_keys as $key) {
            register_post_meta($post_type, $key, [
                'show_in_rest' => true,
                'single' => true,
                'type' => 'string',
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
                'sanitize_callback' => 'sanitize_text_field',
            ]);
        }
    }
});
