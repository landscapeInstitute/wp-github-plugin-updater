<?php

/*
Name: WP GitHub Updater Class
Version: 2.3
Author: Louis Varley
Author URI: http://www.landscapeinstitute.org
*/

if ( !defined('ABSPATH') )
	die( 'Wordpress is required for WP GitHub Updater Class' );


if(!class_exists('WP_GitHub_Updater')){
	
	class WP_GitHub_Updater{
		
		private $file;
		
		function __construct($file){

			if ( is_admin() ||  wp_doing_ajax() ) {
			
				$this->file = $file;
				
				$this->master_file = basename($file);
				
				$this->folder = dirname($this->file);
				
				$this->local_meta = \get_plugin_data($this->file);
				
				$this->name = $this->local_meta['Name'];
				
				$this->github_repo = str_replace('https://github.com/','',$this->local_meta['PluginURI']);
				
				$this->current_version = $this->local_meta['Version'];

				$this->remote_master_file = 'https://raw.githubusercontent.com/' . $this->github_repo . '/master/' . $this->master_file;
				
				if(!$this->url_exists($this->remote_master_file)) return false;
				
				$this->remote_meta = \get_plugin_data($this->remote_master_file);
			
				$this->remote_version = $this->remote_meta['Version'];

				$this->branch = (isset($this->local_meta['Branch']) ? $this->local_meta['Branch'] : "master");		
				
				add_action( 'admin_notices', array($this,'show_messages') );
				add_filter( 'plugin_row_meta', array($this,'updater_links'), 10, 2 );
				
				if ( wp_doing_ajax() ){
					add_action( 'wp_ajax_plugin_updater',array($this, 'ajax_plugin_updater') );	
				}
				
			}
			
		}
				


		function url_exists($URL)
		{
			
			$exists = true;
			$file_headers = @get_headers($URL);
			$InvalidHeaders = array('404', '403', '500');
			foreach($InvalidHeaders as $HeaderVal)
			{
					if(strstr($file_headers[0], $HeaderVal))
					{
							$exists = false;
							break;
					}
			}
			return $exists;
		}


		
		function updater_links( $links_array, $plugin_file_name ){
		 		
			if( dirname($plugin_file_name) ==  basename($this->folder) ) {
				
				$query = http_build_query(array(
					'action'=>'plugin_updater',
					'repo'=>urlencode($this->github_repo),
				));

				if(version_compare($this->remote_version,$this->current_version) > 0){
					$links_array[] = '<a class="button button-small button-primary" href="' . admin_url('admin-ajax.php?') . $query . '">Update Available v' . $this->remote_version  . '</a>';
				}else{
					$links_array[] = '<a class="button button-small button-primary" href="' . admin_url('admin-ajax.php?') . $query . '">Reinstall v' . $this->remote_version  . '</a>';
				}
			}
		 
			return $links_array;
		}
		
		function show_messages(){
			
			if(isset($_GET['updater']) && isset($_GET['repo'])){
				
				if(urldecode($_GET['repo']) != $this->github_repo) return false;
				
				if($_GET['updater'] == 'fail'){
					$class = 'notice notice-error';
					$message = __( 'There was a problem updating plugin ' . $this->name, 'updater' );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
				}
				
				
				if($_GET['updater'] == 'success'){
					$class = 'notice notice-success';
					$message = __( 'Plugin ' . $this->name . ', Successfully updated to version ' . $this->current_version, 'updater' );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 				
				}			
			}
			
		}
		
		function remove_local_plugin(){
			
			
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($this->folder, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($files as $fileinfo) {
				$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
				$todo($fileinfo->getRealPath());
			}

			rmdir($this->folder);
			
		}
		
		function ajax_plugin_updater(){
			
			if($this->github_repo == urldecode($_GET['repo'])){

				try{

					$ch = curl_init();
					$source = 'https://github.com/' . $this->github_repo . '/archive/' . $this->branch . '.zip'; 
					curl_setopt($ch, CURLOPT_URL, $source);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					$data = curl_exec ($ch);
					curl_close ($ch);

					$destination = wp_upload_dir()['basedir'] . '/' . $this->branch . '.zip'; 
					
					$file = fopen($destination, "w+");
					fputs($file, $data);
					fclose($file);

					$zip = new ZipArchive;
					$res = $zip->open($destination); 
					
					if ($res === TRUE) {
						$zip->extractTo(WP_PLUGIN_DIR); 
						$zip->close();

						$this->remove_local_plugin();
						rename($this->folder . '-' . $this->branch, $this->folder );

					} else {
						'unzip failed;';
					}
					
					unlink($destination);
					echo 'success! redirecting...';
					wp_redirect( admin_url( 'plugins.php?updater=success&repo=' . urlencode($this->github_repo) ));
					exit;
				
				}
				
				catch (Exception $e){
					echo 'fail! redirecting...';
					wp_redirect( admin_url( 'plugins.php?updater=fail&repo=' . urlencode($this->github_repo) ));
					exit;
					
				}
				

			}

		}
		
	}

}

?>