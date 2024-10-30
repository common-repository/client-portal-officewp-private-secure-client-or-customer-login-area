<?php
/**
 * Template Name: Pages List
 * Template Description: - Displays Office Pages in List style
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/pages-list.php
 *
 * HOWEVER, on occasion WP-Office will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @author  WP-Office
 * @version 1.0.0
 */

//needs for translation
__( 'Pages List', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Office Pages in List style', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $query->have_posts() ) {
    echo '<ul class="wpo_pages_list">';
    while ( $query->have_posts() ) {
        $query->the_post();
        echo '<li class="wpo_page_list_item"><a class="wpo_link wpo_page_link" href="' . get_permalink( get_the_ID() ) . '">' . get_the_title() . '</a></li>';
    }
    echo '</ul>';
}
?>