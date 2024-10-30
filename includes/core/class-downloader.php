<?php
namespace wpo\core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Downloader {

    private $type;

    private $files_for_regular_view = array(
        'bmp', 'css', 'gif', 'html', 'jpg', 'jpeg', 'png', 'txt', 'xml'
    );

    private $files_for_google_doc_view = array(
        "ai"         =>    "application/postscript",
        "doc"        =>    "application/msword",
        "docx"       =>    "application/vnd.openxmlformats-officedocument.wordprocessingml",
        "dxf"        =>    "application/dxf",
        "eps"        =>    "application/postscript",
        "otf"        =>    "font/opentype",
        "pages"      =>    "application/x-iwork-pages-sffpages",
        "pdf"        =>    "application/pdf",
        "pps"        =>    "application/vnd.ms-powerpoint",
        "ppt"        =>    "application/vnd.ms-powerpoint",
        "pptx"       =>    "application/vnd.openxmlformats-officedocument.presentationml",
        "ps"         =>    "application/postscript",
        "psd"        =>    "image/photoshop",
        "rar"        =>    "application/rar",
        "svg"        =>    "image/svg+xml",
        "tif"        =>    "image/tiff",
        "tiff"       =>    "image/tiff",
        "ttf"        =>    "application/x-font-ttf",
        "xls"        =>    "application/vnd.ms-excel",
        "xlsx"       =>    "application/vnd.openxmlformats-officedocument.spreadsheetml",
        "xps"        =>    "application/vnd.ms-xpsdocument",
        "zip"        =>    "application/zip"
    );

    function __construct( $type = 'download' ) {
        $this->type = $type;
    }

    function generate_google_view( $file_id, $ext, $resource = 'core' ) {
        $user_id = get_current_user_id();
        $hash = md5( $file_id . $user_id . date('Y-m-d') . NONCE_SALT );

        if ( is_multisite() ) {
            $home_url = get_home_url( get_current_blog_id() );
        } else {
            $home_url = get_home_url();
        }

        if( get_option( 'permalink_structure' ) ) {
            $url = $home_url . '/wpo-api/google_view/' . $resource . '/' . $file_id . '/' . $hash . '_' . $user_id . '.' . $ext;
        } else {
            $url = add_query_arg( array(
                'wpo_page' => 'api',
                'wpo_action' => 'google_view',
                'wpo_resource' => $resource,
                'wpo_id' => $file_id,
                'wpo_verify' => $hash . '_' . $user_id . '.' . $ext
            ), $home_url );
        }
        ?>
        <html>
        <head>
            <style type="text/css">
                body {
                    margin:   0;
                    padding:   0;
                    overflow: hidden;
                    position: relative;
                }

                #iframe {
                    position: relative;
                    top:      0;
                    left:     0;
                    bottom:   0;
                    right:    0;
                    margin:   0;
                    padding:  0;
                    width:    100%;
                    height:   100%;
                }
            </style>
        </head>

        <body>
        <iframe id="iframe" allowfullscreen="allowfullscreen" src="//docs.google.com/viewer?url=<?php echo urlencode( $url ); ?>&hl=<?php echo get_locale(); ?>&embedded=true" frameborder="0"></iframe>
        </body>
        </html>
        <?php
        exit;
    }


    function download_link( $resource, $id ) {
        $type = $this->type;
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        $user_id = get_current_user_id();
        $nonce = wp_create_nonce( $ip . $user_id . $type . $resource . $id );
        if( is_admin() ) {
            $url = add_query_arg( array(
                'action' => 'wpo_api',
                'wpo_ajax_side' => 'common',
                'wpo_action' => $type,
                'wpo_resource' => $resource,
                'wpo_id' => $id,
                'wpo_verify' => $nonce
            ), get_admin_url( null, 'admin-ajax.php') );
        } else if( get_option( 'permalink_structure' ) ) {
            $url = get_home_url( null, 'wpo-api/' . $type . '/' . $resource . '/' . $id . '/' . $nonce );
        } else {
            $url = add_query_arg( array(
                'wpo_page' => 'api',
                'wpo_action' => $type,
                'wpo_resource' => $resource,
                'wpo_id' => $id,
                'wpo_verify' => $nonce
            ), get_home_url() );
        }

        return $url;
    }


    function ajax_get_download_url() {
        if( isset( $_POST['type'] ) ) {
            $type = $_POST['type'];
        } else {
            exit( json_encode( array(
                'status' => false,
                'message' => __( 'Invalid type.', WP_OFFICE_TEXT_DOMAIN )
            ) ) );
        }

        if( isset( $_POST['id'] ) ) {
            $id = '';
            if( is_numeric( $_POST['id'] ) ) {
                $id = $_POST['id'];
            } else if( is_array( $_POST['id'] ) ) {
                $id = implode('_', $_POST['id']);
            }
        } else {
            exit( json_encode( array(
                'status' => false,
                'message' => __( 'Invalid ID.', WP_OFFICE_TEXT_DOMAIN )
            ) ) );
        }

        $resource = isset( $_POST['resource'] ) ? $_POST['resource'] : 'core';

        exit( json_encode( array(
            'status' => true,
            'message' => $this->set_type( $type )->download_link( $resource, $id )
        ) ) );
    }


    function set_type( $type ) {
        $this->type = $type;
        return $this;
    }

    /*
    * Download file by parts
    */
    function readfile_chunked( $filename, $retbytes = true ) {
        $chunksize = 1 *( 1024 * 1024 ); // how many bytes per chunk
        $cnt = 0;
        // $handle = fopen($filename, 'rb');
        $handle = fopen( $filename, 'rb' );
        if ( $handle === false ) {
            return false;
        }

        while ( !feof( $handle ) ) {
            $buffer = fread( $handle, $chunksize );
            echo $buffer;
            if ( $retbytes ) {
                $cnt += strlen( $buffer );
            }
        }
        $status = fclose( $handle );
        if ( $retbytes && $status ) {
            return $cnt; // return num. bytes delivered like readfile() does.
        }
        return $status;

    }

    function available_view( $file_id ) {
        $file = WO()->files()->get_file( $file_id );
        if( empty( $file['file_path'] ) ) return false;

        $path_parts = pathinfo( $file['file_path'] );
        $ext = isset( $path_parts['extension'] ) ? strtolower( $path_parts['extension'] ) : '';
        if( in_array( $ext, array_keys( $this->files_for_google_doc_view ) ) || in_array( $ext, $this->files_for_regular_view ) )
            return true;
        return false;
    }

    function process_stream( $params ) {
        @ignore_user_abort(true);
        if ( !ini_get( 'safe_mode' ) ) {
            @set_time_limit( 0 );
        }

        $levels = ob_get_level();
        for( $i=0; $i<$levels; $i++ )
            @ob_end_clean();

        if( is_numeric( $params['ids'] ) ) {
            if( (int)$params['ids'] <= 0 ) {
                exit(__('Invalid ID.', WP_OFFICE_TEXT_DOMAIN));
            }
            $ids = $params['ids'];
        } else {
            if( $params['ids'] == '' ) {
                exit(__('Invalid IDs.', WP_OFFICE_TEXT_DOMAIN));
            }
            $ids = explode( '_', $params['ids'] );
            foreach( $ids as $id ) {
                if( (int)$id <= 0 ) {
                    exit(__('Invalid IDs.', WP_OFFICE_TEXT_DOMAIN));
                }
            }
        }
        do_action( 'wpoffice_stream_' . $params['resource'], $ids );
        exit;
    }

    function process( $params ) {
        @ignore_user_abort(true);
        if ( !ini_get( 'safe_mode' ) ) {
            @set_time_limit( 0 );
        }

        if ( (int)$params['id'] <= 0 ) {
            exit( __( 'Invalid file. Please try downloading again!', WP_OFFICE_TEXT_DOMAIN ) );
        }
        $download_params = apply_filters( 'wpoffice_download_' . $params['resource'] . '_file_params', array(
            'headers' => array(
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Robots' => 'none',
                'Content-Description' => 'File Transfer',
                'Content-Transfer-Encoding' => 'none'
            ),
            'file_path' => '',
            'file_name' => ''
        ), $params );

        if( is_wp_error( $download_params['file_path'] ) ) {
            exit( $download_params['file_path']->get_error_messages() );
        }

        if( !file_exists( $download_params['file_path'] ) || empty( $download_params['file_path'] ) ) {
            exit( __( 'File does not exists.', WP_OFFICE_TEXT_DOMAIN ) );
        }

        if( $params['action'] == 'download' ) {

            $filedata = WO()->get_object( $params['id'] );
            WO()->send_notification(
                'file_downloaded',
                array(
                    'doer' => get_current_user_id(),
                    'object_author' => !empty( $filedata['author'] ) ? $filedata['author'] : ''
                ),
                array(
                    'member_id' => get_current_user_id(),
                    'object_id' => $params['id'],
                    'object_type' => 'member',
                )
            );
        }

        $path_parts = pathinfo( $download_params['file_path'] );
        $ext = isset( $path_parts['extension'] ) ? strtolower( $path_parts['extension'] ) : '';

        if( $params['action'] == 'view' && in_array( $ext, array_keys( $this->files_for_google_doc_view ) ) ) {
            $this->generate_google_view( $params['id'], $ext, $params['resource'] );
        }

        if( isset( $download_params['headers'] ) && is_array( $download_params['headers'] ) ) {
            foreach( $download_params['headers'] as $key=>$val ) {
                header( $key . ': ' . $val );
            }
        }

        $mime_types = array_merge( wp_get_mime_types(), $this->files_for_google_doc_view );
        $extensions = array_keys( $mime_types );

        $content_type = '';
        foreach( $extensions as $_extension ) {
            if ( preg_match( "/{$ext}/i", $_extension ) ) {
                $content_type = $mime_types[ $_extension ];
                break;
            }
        }

        if( empty( $content_type ) ) $content_type = 'application/octet-stream';
        header("Content-type: $content_type");

        $fsize = filesize( $download_params['file_path'] );

        $file_name = !empty( $download_params['file_name'] ) ? $download_params['file_name'] : $path_parts['basename'];

        if( $params['action'] == 'download' ) {
            header("Content-Disposition: attachment; filename=\"" . $file_name . "\"");
            header("Content-length: $fsize");
        } else if( $params['action'] == 'view' || $params['action'] == 'google_view' ) {
            header( "Content-Disposition: inline; filename=\"" . $file_name . "\"" );
        } else {
            exit( __( 'Wrong action.', WP_OFFICE_TEXT_DOMAIN ) );
        }

        $levels = ob_get_level();
        for ($i=0; $i<$levels; $i++)
            @ob_end_clean();

        $this->readfile_chunked( $download_params['file_path'] );

        exit;
    }
}