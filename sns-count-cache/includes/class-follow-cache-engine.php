<?php
/*
class-follow-cache-engine.php

Description: This class is a data cache engine whitch get and cache data using wp-cron at regular intervals  
Author: Daisuke Maruyama
Author URI: http://marubon.info/
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

/*

Copyright (C) 2014 - 2015 Daisuke Maruyama

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/


abstract class Follow_Cache_Engine extends Cache_Engine {

  	/**
	 * Crawler instance
	 */	            
	protected $crawler = NULL;
  
  	/**
	 * Cache target
	 */	            
  	protected $target_sns = array();  

  	/**
	 * migration between http and https
	 */	     
  	protected $scheme_migration_mode = false;

  	/**
	 * excluded keys in scheme migration
	 */	     
  	protected $scheme_migration_exclude_keys = array();  

  	/**
	 * load ratio for throttle
	 */	   
  	protected $load_ratio = 0.5;  
  
    /**
	 * Get and cache data for a given post
	 *
	 * @since 0.4.0
	 */  	
  	public function cache( $options = array() ) {
	  	Common_Util::log( '[' . __METHOD__ . '] (line='. __LINE__ . ')' );
	  
	  	$cache_key = $options['cache_key'];
		$target_url = $options['target_url'];
		$target_sns = $options['target_sns'];
		$cache_expiration = $options['cache_expiration'];
	  
	  	Common_Util::log( '[' . __METHOD__ . '] feed: ' . $target_url );
	  							
	  	$data = $this->crawler->get_data( $target_sns, $target_url );
			  
		Common_Util::log( $data );

	  	if ( $this->scheme_migration_mode && Common_Util::is_secure_url( $target_url ) ) {
		  	$target_url = Common_Util::get_normal_url( $target_url );

		  	$target_sns_migrated = $target_sns;
		  	
		  	foreach ( $this->scheme_migration_exclude_keys as $sns ) {
			  	unset( $target_sns_migrated[$sns] );
		  	}
		  
	  		Common_Util::log( '[' . __METHOD__ . '] feed: ' . $target_url );
		  
		  	$migrated_data = $this->crawler->get_data( $target_sns_migrated, $target_url );

			Common_Util::log( $migrated_data );

		  	foreach ( $target_sns_migrated as $sns => $active ) {
				if ( $active && isset( $migrated_data[$sns] ) && is_numeric( $migrated_data[$sns] ) && $migrated_data[$sns] > 0 ){
				  	$data[$sns] = $data[$sns] + $migrated_data[$sns];
				}
			}
		  		  
		}	  
	  	  
	  	if ( $data ) {
		  		  
		  	$throttle = new Sleep_Throttle( $this->load_ratio );

			$throttle->reset();
			$throttle->start();
			  
			$result = set_transient( $cache_key, $data, $cache_expiration ); 		  
		  
		  	$throttle->stop();
		  
	  		$retry_count = 0;
			  
		  	while ( true ) {
			  
			  	Common_Util::log( '[' . __METHOD__ . '] set_transient result (' . $cache_key . '): ' . $result );
				 				  			  
			  	if ( $result ) {					
				  	break;
				  
				} else {
				 
					if ( $retry_count < $this->retry_limit ) {
					  
					  	Common_Util::log( '[' . __METHOD__ . '] sleep before set_transient retry (' . $cache_key . '): ' . $throttle->get_sleep_time() . ' sec.' );
					  
					  	$throttle->sleep();
					  
					  	++$retry_count;
					  
					  	Common_Util::log( '[' . __METHOD__ . '] count of set_transient retry (' . $cache_key . '): ' . $retry_count );
					  
						$throttle->reset();
			  			$throttle->start();
			  
			  			$result = set_transient( $cache_key, $data, $cache_expiration ); 
					  
				  		$throttle->stop();
					  	
					} else {
						Common_Util::log( '[' . __METHOD__ . '] set_transient result (' . $cache_key . '): retry failed' );
						break;
					}
				}
			}
			
		}
	  
	  	return $data;
  	}
  
}

?>