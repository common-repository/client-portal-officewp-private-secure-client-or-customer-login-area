<?php
/**
 * Template Name: Pagination
 * Template Description: Displays Pagination
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/pagination.php.
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
__( 'Pagination', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Pagination', WP_OFFICE_TEXT_DOMAIN );

if ($pagination->getNumPages() > 1) { ?>
    <ul class="wpo_frontend_pagination">
        <?php if ($pagination->getPrevPage()) { ?>
            <li><a href="javascript: void(0);" data-page="<?php echo $pagination->getPrevPage(); ?>">&laquo; <?php _e( 'Previous', WP_OFFICE_TEXT_DOMAIN ) ?></a></li>
        <?php } ?>

        <?php foreach ($pagination->getPages() as $page) { ?>
            <?php if( $page['num'] != 0 ) { ?>
                <li <?php echo $page['isCurrent'] ? 'class="active"' : ''; ?>>
                    <a href="javascript: void(0);" data-page="<?php echo $page['num']; ?>"><?php echo $page['num']; ?></a>
                </li>
            <?php } else { ?>
                <li class="disabled"><span>...</span></li>
            <?php } ?>
        <?php } ?>

        <?php if ($pagination->getNextPage()): ?>
            <li><a href="javascript: void(0);" data-page="<?php echo $pagination->getNextPage(); ?>"><?php _e( 'Next', WP_OFFICE_TEXT_DOMAIN ) ?> &raquo;</a></li>
        <?php endif; ?>
    </ul>
<?php } ?>