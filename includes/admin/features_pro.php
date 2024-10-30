<?php

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_enqueue_style( 'wpo-features-style', WO()->plugin_url . 'assets/css/admin-features.css', array(), WP_OFFICE_VER );

?>

<style type="text/css" >

    .wpo_pro_features .wpo_features_table {
        width: calc(100% - 20px);
    }

    .wpo_pro_features .wpo_features_table .wpo_main_title {
        font-size: 18px;
    }

    .wpo_pro_features .wpo_features_table img {
        width: calc(40% - 40px);
        padding: 15px;
    }

    .wpo_pro_features .wpo_center_block {
        float: right;
        text-align: justify;
        width: 60% !important;
    }

    .wpo_pro_features .wpo_td {
        padding: 15px 20px 15px 10px;
    }

    .wpo_pro_features .wpo_center_block ul {
        list-style: inherit;
    }


</style>

<?php

$all_features = array(

    array(
        'bg_color' => '#87D0ED',
        'title' => __('File Sharing', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-file-sharing.png',
        'texts' => array(
            'Upload files from directly inside your WordPress dashboard, and assign them to any combination of clients, managers, and custom user groups.',
            'File Categories can be created and files can be placed inside them, allowing you to easily "bulk assign" many files to a user in one step, by simply assigning them directly to the corresponding File Category.',
            'Clients can be allowed to upload files themselves as well, which the site admins (and any assigned managers) will be able to access from the OfficeWP backend dashboard.',
            'Nearly all file types are supported, and file size limitations are based only on your hosting, giving you and your clients the freedom to share files of nearly any type or size.',
        ),
    ),
    array(
        'bg_color' => '#87D0ED',
        'title' => __('Private Messaging', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-private-messaging.png',
        'texts' => array(
            'Easily communicate securely with any users in your installation, including clients, managers, and even other admins.',
            'Messages can be sent to one user to create a private conversation, or sent to multiple people for group messages, allowing you to tailor the conversation to fit your needs.',
            'Messages can be searched and sorted, allowing you to easily find the exact message you are looking for in your inbox.',
            'Additionally, bulk actions allow you to easily manage multiple messages at once.',
            'Who a user can send a message to is based on assignment and permissions set by you, so you can be certain everyone has only the access you grant them.',
        ),
    ),
    array(
        'bg_color' => '#87D0ED',
        'title' => __('Circles', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-circles.png',
        'texts' => array(
            'Easily group users for organization, and quickly bulk assign resources (files, pages, etc) to many users at once.',
            'For example, you can assign 10 clients to a Circle, then assign that Circle to a file or Office Page, and the 10 clients inside the Circle will automatically gain access to the file or page, without needing to assign each client individually.',
            'Bulk assigning files, pages, and other resources to many users becomes incredibly easy with Circles.',
        ),
    ),
    array(
        'bg_color' => '#87D0ED',
        'title' => __('Role Capabilities Editor', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-capabilities.png',
        'texts' => array(
            'Users can access content based on what is assigned to them by default, but you can take it to another level and control exactly what different users have access to based on their role, on a granular level.',
            'Easily turn different sections of the plugin on and off for each user role, allowing you to fully customize the end-user experience for each role.',
            'Optionally create your own custom user roles with the available Role Creator add-on, with their own set of unique capabilities and permissions, giving you full control over how your users interact with your installation.',
            'The capabilities of your custom roles, along with the default user roles, are completely customizable, giving you nearly endless possibilities for providing customized access to your users.',
        ),
    ),
    array(
        'bg_color' => '#87D0ED',
        'title' => __('Custom Redirects', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-redirects.png',
        'texts' => array(
            'Easily define where users are sent after login, logout, after registration, and other scenarios.',
            'Redirects can be for all users, or be for a specific user or group of users, allowing you to fully customize where your users are sent after logging in or out, or after self-registration.',
            'Additionally, redirects can be setup for when non-logged-in users attempt to view protected pages, so you can choose to send them to somewhere besides the default error page (such as to the login page, or to a customized "you don\'t have access" page).',
        ),
    ),
    array(
        'bg_color' => '#87D0ED',
        'title' => __('Approve Member', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-registration.png',
        'texts' => array(
            'Allow your users to self-register, but still maintain control over their access with the user approval workflow.',
            'All users who self-register using the frontend registration forms will be put in “pending” status, until their account is approved by an admin.',
            'Once a pending user is approved by the admin, that user will be able to login to the site with their unique credentials, and be able to access their assigned/permissioned resources.',
        ),
    ),
    array(
        'bg_color' => '#87D0ED',
        'title' => __('Custom Email Notifications', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-email-notifications.png',
        'texts' => array(
            'Take the default Email Notifications functionality a step further, and create your own custom notifications, based on your own desired triggers, involved users, actions, and so on.',
            'Custom tailor each notification to fit your specific needs.',
            'A host of provided placeholders allow you to dynamically insert pertinent data into email notifications, such as usernames, login links, filenames, and much more, eliminating the need to manually insert client data.',
            'Email notifications can be turned on and off, edited, deleted, and created as needed, giving you the control to ever-evolve the functionality as your business and client needs grow and change.',
        ),
    ),
    array(
        'bg_color' => '#87D0ED',
        'title' => __('Frontend Templates', WP_OFFICE_TEXT_DOMAIN),
        'image_url' => WO()->plugin_url . 'assets/images/pro_features/wpo-frontend-templates.png',
        'texts' => array(
            'Customize the content, layout, and functionality of the various OfficeWP shortcodes by directly editing their templates.',
            'With the editable Frontend Templates, you will be able to modify a shortcode’s output, from what content is shown, to how it looks, even down to the text strings that appear.',
            'Frontend Templates are included for the Login Form, Registration Form, Files List, and more, so you can modify the shortcode output to fit your particular needs.',
        ),
    ),

);

echo '<div class="wpo_pro_features">';

echo '<h4 style="margin: 20px 20px 20px 50px; float: left;">' . __( 'Like OfficeWP Lite? Purchasing a Pro license gets you all of the features below, plus many others as we continue to update and build onto the plugin', WP_OFFICE_TEXT_DOMAIN ) . '</h4>';

foreach ( $all_features as $block) {
    echo '<div class="wpo_table wpo_features_table wpo_pro_features">';
    echo '<div class="wpo_thead"' . ( isset( $block['bg_color'] ) ? ' style="background-color: ' . $block['bg_color'] . '"' : '' ) .'>';
    echo '<div class="wpo_tr">';
    echo '<div class="wpo_th wpo_main_title"'

        . '>' . $block['title'] . '</div>';
    echo '</div>';
    echo '<div class="wpo_tr">';
    echo '<div class="wpo_th wpo_title_hr">' . WO()->hr( '0 0 0 0' ) . '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="wpo_tbody">';

    echo '<div class="wpo_tr">';
    echo '<img src="' . $block['image_url'] . '"  />';
    echo '<div class="wpo_td wpo_center_block"><ul>';
    foreach ( $block['texts'] as $text ) {
        echo '<li>' . $text . '</li>';
    }
    echo '</ul></div>';
    echo '</div>';

    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '<h1 style="margin: 20px 20px 20px 50px; float: left; width: 100%; text-align: center;">' . __( 'and much more...', WP_OFFICE_TEXT_DOMAIN ) . '</h1>';

echo '<h1 style="margin: 20px 20px 20px 50px; float: left; width: 100%; text-align: center;">' . __( 'Do you want to improve your business?', WP_OFFICE_TEXT_DOMAIN ) . '</h1>';

echo '<div style="margin: 40px 20px 40px 50px; float: left; width: 100%; text-align: center;"><a href="https://officewp.com/pricing/" target="_blank" class="button-primary" style="padding: 20px; font-size: 25px; height: auto;">Get PRO Version Right NOW</a></div>';
