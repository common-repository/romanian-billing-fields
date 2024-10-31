<?php
	/*
		* Plugin Name: Romanian billing fields
		* Description: Add Romanian billing fields to WooCommerce checkout
		* Version: 1.9.6
		* Author: Gheorghiu Robert
		* Author URI: https://www.linkedin.com/in/cezar-robert-gheorghiu/
		* Text Domain: romanian-billing-fields
		* Domain Path: /languages
		* License: GPL v3 or later
		* License URI: http://www.gnu.org/licenses/gpl-3.0.html
		* Requires at least: 5.6
		* Tested up to: 6.5.5
		* Requires PHP: 7.2
		* WC requires at least: 4.0
		* WC tested up to: 9.0.2
		* WC HPOS compatible: yes
	*/
	
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	
	
	add_action( 'before_woocommerce_init', 'before_woocommerce_hpos' );
	function before_woocommerce_hpos() { 
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) { 
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true ); 
		} 
	}
	// Check if WooCommerce is active
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && ! function_exists( 'initialize_romanian_billing_fields' ) ) {
		
		// Global array to reposition the elements to display as you want (e.g. kept 'cif' after 'company')
		$grc_address_fields = array(
        'first_name',
        'last_name',
        'company',
        'b_nrregcom',
        'b_cif',
        'b_cont',
        'b_banca',
        'b_cnp',
        'address_1',
        'city',
        'state',
        'postcode',
        'country'
		);
		
		// Global array only for extra fields
		$grc_ext_fields = array('b_cif', 'b_nrregcom', 'b_cont', 'b_banca', 'b_cnp');
		
		// Override default fields
		add_filter( 'woocommerce_default_address_fields', 'grc_override_default_address_fields' );
		function grc_override_default_address_fields( $address_fields ) {
			
			$temp_fields = array();
			
			$address_fields['b_cif'] = array(
            'label'       => __( 'CIF:', 'woocommerce' ),
            'required'    => true,
            'placeholder' => 'CIF',
            'class'       => array( 'form-row-last' ),
            'type'        => 'text'
			);
			$address_fields['b_nrregcom'] = array(
            'label'       => __( 'Nr.Reg.Com.:', 'woocommerce' ),
            'required'    => true,
            'placeholder' => 'Nr.Reg.Com.',
            'class'       => array( 'form-row-first' ),
            'type'        => 'text'
			);
			$address_fields['b_cont'] = array(
			'label'       => __( 'Cont:', 'woocommerce' ),
			'required'    => false,
			'placeholder' => 'Cont',
			'class'       => array( 'form-row-first' ),
			'type'        => 'text'
			);
			$address_fields['b_banca'] = array(
			'label'       => __( 'Banca:', 'woocommerce' ),
			'required'    => false,
			'placeholder' => 'Banca',
			'class'       => array( 'form-row-last' ),
			'type'        => 'text'
			);
			$address_fields['b_cnp'] = array(
			'label'       => __( 'CNP:', 'woocommerce' ),
			'required'    => false,
			'placeholder' => 'CNP (optional)',
			'class'       => array( 'form-row-wide' ),
			'type'        => 'text'
			);
			
			$address_fields['company']['required'] = true;
			
			global $grc_address_fields;
			
			foreach ( $grc_address_fields as $fky ) {
				$temp_fields[$fky] = $address_fields[$fky];
			}
			
			$address_fields = $temp_fields;
			
			// If pf remove required from pj
			if ( isset( $_POST['persoana'] ) && $_POST['persoana'] == 'pf' ) {
				$address_fields['company']['required'] = false;
				$address_fields['b_cif']['required'] = false;
				$address_fields['b_nrregcom']['required'] = false;
				$address_fields['b_cont']['required'] = false;
				$address_fields['b_banca']['required'] = false;
			}
			
			return $address_fields;
		}
		
		// Concatenate the order custom fields with company and add custom fields to the formatted address
		add_filter( "woocommerce_formatted_address_replacements", "custom_formatted_address_replacements", 99, 2 );
		function custom_formatted_address_replacements( $address, $args ) {
			// Define the custom fields and their corresponding keys in the $args array
			$custom_field_map = array(
			"company"     => "company",
			"b_cif"       => "b_cif",
			"b_nrregcom"  => "b_nrregcom",
			"b_cnp"       => "b_cnp",
			);
			
			// Initialize an empty string to hold the custom fields
			$custom_fields_string = "";
			
			// Loop through the custom fields and add them to the string
			foreach ( $custom_field_map as $field_key => $arg_key ) {
				if ( isset( $args[$arg_key] ) ) {
					$custom_fields_string .= $args[$arg_key] . "\n";
				}
			}
			
			// Add the custom fields to the address replacement
			$address["{company}"] = $custom_fields_string;
			
			return $address;
		}
		
		add_filter( 'woocommerce_order_formatted_billing_address', 'grc_update_formatted_billing_address', 99, 2 );
		function grc_update_formatted_billing_address( $address, $obj ) {
			global $grc_address_fields;
			if ( is_array( $grc_address_fields ) ) {
				foreach ( $grc_address_fields as $waf ) {
					$address[$waf] = $obj->get_meta( '_billing_' . $waf );
				}
			}
			return $address;
		}
		
		add_filter( 'woocommerce_my_account_my_address_formatted_address', 'grc_my_account_address_formatted_address', 99, 3 );
		function grc_my_account_address_formatted_address( $address, $customer_id, $name ) {
			global $grc_address_fields;
			if ( is_array( $grc_address_fields ) ) {
				foreach ( $grc_address_fields as $waf ) {
					$address[$waf] = get_user_meta( $customer_id, $name . '_' . $waf, true );
				}
			}
			return $address;
		}
		
		add_filter( 'woocommerce_admin_billing_fields', 'grc_add_extra_customer_field' );
		function grc_add_extra_customer_field( $fields ) {
			
			$email = $fields['email'];
			$phone = $fields['phone'];
			$fields = grc_override_default_address_fields( $fields );
			$fields['email'] = $email;
			$fields['phone'] = $phone;
			
			global $grc_ext_fields;
			
			if ( is_array( $grc_ext_fields ) ) {
				foreach ( $grc_ext_fields as $wef ) {
					$fields[$wef]['show'] = false;
				}
			}
			return $fields;
		}
		
		// Remove from shipping fields
		add_filter( 'woocommerce_shipping_fields', 'grc_custom_billing_fields' );
		function grc_custom_billing_fields( $fields = array() ) {
			unset( $fields['shipping_b_cif'] );
			unset( $fields['shipping_b_nrregcom'] );
			unset( $fields['shipping_b_cont'] );
			unset( $fields['shipping_b_banca'] );
			unset( $fields['shipping_b_cnp'] );
			return $fields;
		}
		
		// PF and PJ select
		add_action( 'woocommerce_checkout_before_customer_details', 'grc_add_checkout_content', 12 );
		function grc_add_checkout_content() {
			if ( is_checkout() ) {
				woocommerce_form_field( 'persoana', array(
				'type'     => 'select',
				'class'    => array( 'tip-facturare' ),
				'required' => 'yes',
				'label'    => __( 'Alege tipul de facturare' ),
				'options'  => array(
				'pf' => __( 'Persoana fizica', 'grc' ),
				'pj' => __( 'Persoana juridica', 'grc' )
				)
				));
			}
		}
		
		// Show hide selected fields
		add_action( 'wp_footer', 'grc_conditional_script' );
		function grc_conditional_script() {
			if ( is_checkout() ) {
			?>
			<script>
				jQuery(function ($) {
					toggleFields();
					
					$('#persoana').change(function() {
						var selectedValue = $(this).val();
						if (selectedValue === 'pf') {
							showPFFields();
							} else if (selectedValue === 'pj') {
							showPJFields();
						}
					});
					
					
					function toggleFields() {
						$('#billing_company_field, #billing_b_nrregcom_field, #billing_b_cif_field, #billing_b_cont_field, #billing_b_banca_field, #billing_b_cnp_field').hide();
					}
					
					function showPFFields() {
						toggleFields();
						$('#billing_b_cnp_field').show();
					}
					
					function showPJFields() {
						toggleFields();
						$('#billing_company_field, #billing_b_nrregcom_field, #billing_b_cif_field, #billing_b_cont_field, #billing_b_banca_field').show();
					}
				});
			</script>
			<?php
			}
		}
	}