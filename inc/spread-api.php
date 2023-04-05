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


function get_spreadshirt_basket($postData, $basketId)
{
    $params = $basketId !== null ? '/' . $basketId : '';
    $url = 'https://api.spreadshirt.net/api/v1/baskets' . $params . '?mediaType=json';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Authorization: SprdAuth apiKey="dd30b4db-8cd6-4fb8-86b3-e680984b9e18"',
            'User-Agent: greenwich/1.0',
            'Access-Control-Allow-Origin: *',
        ),
    ));
    if (($postData && $postData !== null)) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    } else {
        curl_setopt($curl, CURLOPT_HTTPGET, true);
    }

    $response = json_decode(curl_exec($curl));
    return $response;


    if (curl_errno($curl)) {

        error_log('Failed to convert cart to Spreadshirt basket: ' . curl_error($curl));
    } else {
    }
    curl_close($curl);
}
