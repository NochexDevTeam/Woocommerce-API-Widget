<?php
use Nochexapi\WC_Nochexapi_Constants AS Nochexapi_CONSTANTS; 

trait NochexapiHelperTrait{
    
    public function checkValidJsonDecode( $json ){
        $return       = false;
        $obj          = json_decode( $json );
        switch ( json_last_error() ) {
            case JSON_ERROR_NONE:
                $return = $obj;
                break;
            case JSON_ERROR_DEPTH:
                $return = false;
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $return = false;
                break;
            case JSON_ERROR_CTRL_CHAR:
                $return = false;
                break;
            case JSON_ERROR_SYNTAX:
                $return = false;
                break;
            case JSON_ERROR_UTF8:
                $return = false;
                break;
            default:
                $return = false;
                break;
        }
        return $return;
    }
    
    protected function writeLog( $obj, $level = false ){
        $debug     = $this->get_option( 'serversidedebug' );
        if( $debug != 'yes' ){
            return;
        }
        
        $logger     = wc_get_logger();
        
        $context    = array( 'source' => Nochexapi_CONSTANTS::GATEWAY_ID );
        switch ( $level ) {
            case 'critical':
                $logger->critical( wc_print_r( $obj , true ) , $context );
                break;
            case 'debug':
                $logger->debug( wc_print_r( $obj , true ) , $context );
                break;
            case 'emergency':
                $logger->emergency( wc_print_r( $obj , true ) , $context );
                break;
            case 'error':
                $logger->error( wc_print_r( $obj , true ) , $context );
                break;
            case 'warning':
                $logger->warning( wc_print_r( $obj , true ) , $context );
                break;
            default:
               $logger->info( wc_print_r( $obj , true ) , $context );
        }
        
        return true;
    }

    public function get_wc_logfiles(){
        $log_files  = WC_Log_Handler_File::get_log_files();
        krsort( $log_files );
        $arr        = [];
        foreach( $log_files AS $key => $file ){
            if( strpos( $key, Nochexapi_CONSTANTS::GATEWAY_ID ) !== false ){
                $arr[$key] = $file;
            }
        }
        return $arr;
    }

    public function get_wc_logfiles_path( $handler ){
        $log_files_path  = WC_Log_Handler_File::get_log_file_path( $handler );
        return $log_files_path;
    }

    public function get_wc_logfiles_content( $handler ){
        return $log_files_path  = WC_Log_Handler_File::get_log_file_path( $handler );
        $content         = esc_html( file_get_contents( $log_files_path ) );
        return $content;
    }
}
