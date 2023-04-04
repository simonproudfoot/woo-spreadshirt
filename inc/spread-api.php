<?php
function get_spreadshirt_data($endPoint, $postData)
{
    $curl = curl_init();
    $url = 'https://api.spreadshirt.net/api/v1/shops/101082106/' . $endPoint . '?mediaType=json&fullData=true';
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Authorization: SprdAuth apiKey="dd30b4db-8cd6-4fb8-86b3-e680984b9e18"',
            'User-Agent: greenwich/1.0',
            'Access-Control-Allow-Origin: *',
        ),
    ));
    if (($postData)) {

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    }
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Error: ' . curl_error($curl);
    } else {
        return json_decode($response);
    }
    curl_close($curl);
}

function get_spreadshirt_basket($postData)
{
    // Set the cURL options
    $curl_options = array(
        CURLOPT_URL => 'https://api.spreadshirt.net/api/v1/baskets?mediaType=json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => array(
            'Authorization: SprdAuth apiKey="dd30b4db-8cd6-4fb8-86b3-e680984b9e18"',
            'User-Agent: greenwich/1.0',
            'Access-Control-Allow-Origin: *',
        ),
    );
    // Initialize cURL
    $curl = curl_init();

    // Set the cURL options
    curl_setopt_array($curl, $curl_options);

    // Execute the cURL request
    $response = json_decode(curl_exec($curl));
  
    return $response;

    // Check for errors
    if (curl_errno($curl)) {
        // Log the error or display an error message to the user
        error_log('Failed to convert cart to Spreadshirt basket: ' . curl_error($curl));
        // Add your own error handling code here
    } else {

    }
    // Close the cURL session
    curl_close($curl);
}
