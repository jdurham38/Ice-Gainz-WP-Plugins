<?php
/**
 * Plugin Name: Shipping Calculator
 * Description: Determines if a customer is within a 25 mile radius and calculates shipping cost accordingly.
 * Version: 1.0
 * Author: Joshua Durham
 */

add_action( 'woocommerce_after_checkout_validation', 'shipping_calculator' );

function shipping_calculator( $posted ) {
    // Get customer's shipping address
    $customer_address = WC()->customer->get_shipping_address();
    $customer_city = WC()->customer->get_shipping_city();
    $customer_state = WC()->customer->get_shipping_state();
    $customer_postcode = WC()->customer->get_shipping_postcode();

    // Get the coordinates of the customer's address
    $customer_coordinates = get_coordinates( $customer_address, $customer_city, $customer_state, $customer_postcode );
    
    // Get the coordinates of the store's address
    $store_address = '123 Main St';
    $store_city = 'Anytown';
    $store_state = 'CA';
    $store_postcode = '90210';
    $store_coordinates = get_coordinates( $store_address, $store_city, $store_state, $store_postcode );

    // Calculate the distance between the customer and the store
    $distance = distance( $customer_coordinates['lat'], $customer_coordinates['lng'], $store_coordinates['lat'], $store_coordinates['lng'], 'M' );

    // If the distance is greater than 25 miles, show an error message and prevent the order from being processed
    if ( $distance > 25 ) {
        wc_add_notice( 'We cannot ship to your location as it is outside our delivery range.', 'error' );
        return;
    }

    // Calculate the shipping cost based on the distance
    $shipping_cost = 5 + ( ceil( $distance / 5 ) - 1 ) * 0.5;

    // Set the shipping cost for the order
    WC()->session->set( 'shipping_cost', $shipping_cost );
}

// Helper function to get the coordinates of an address
function get_coordinates( $address, $city, $state, $postcode ) {
    $address = urlencode( $address );
    $city = urlencode( $city );
    $state = urlencode( $state );
    $postcode = urlencode( $postcode );
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address},{$city},{$state},{$postcode}&key=YOUR_API_KEY";

    $response = wp_remote_get( $url );
    $response_body = wp_remote_retrieve_body( $response );
    $response_data = json_decode( $response_body, true );

    return $response_data['results'][0]['geometry']['location'];
}

// Helper function to calculate the distance between two points
function distance( $lat1, $lon1, $lat2, $lon2, $unit ) {
    $theta = $lon1 - $lon2;
    $dist = sin( deg2rad( $lat1 ) ) * sin( deg2rad( $lat2 ) ) +  cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * cos( deg2rad( $theta ) );
    $dist = acos( $dist );
    $dist = rad2deg( $dist );
    $miles = $dist * 60 * 1.1515;
    switch ( $unit ) {
        case 'M':
            return ( $miles * 1.609344 );
        case 'K':
            return ( $miles * 609.344 );
case 'N':
return ( $miles * 0.8684 );
default:
return $miles;
}
}
