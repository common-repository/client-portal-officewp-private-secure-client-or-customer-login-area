<?php
/**
 * Template Name: Search
 * Template Description: Displays Search Line
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/search.php.
 *
 * HOWEVER, on occasion WP-Office will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 	WP-Office
 * @version     1.0.0
 */

//needs for translation
__( 'Search', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Search Line', WP_OFFICE_TEXT_DOMAIN );
?>
<div class="wpo_search_wrapper">
    <input type="search" name="search" class="wpo_search_line" value="<?php echo $search_value; ?>">
    <div class="wpo_button wpo_text_button wpo_search_submit" title="Search">
        <div class="dashicons dashicons-search"></div>
    </div>
</div>