<?php

function apiCallFunction($formData) {
    // Initialize cURL
    $ch = curl_init();

    // Set the API endpoint URL
    $apiUrl = "https://hooks.airtable.com/workflows/v1/genericWebhook/appP5XZVpgyoV0QqI/wflDqIKI7uMnhAGTY/wtrzHPyk0feAEY2MV";

    // Prepare the data to be sent as JSON
    $jsonData = json_encode($formData);

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        // Add additional headers here if required by your API
    ));

    // Execute the cURL session
    $response = curl_exec($ch);

    // Close the cURL session
    curl_close($ch);

    // Return the API response for further processing
    return $response;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // access
    $secretKey = '6LcXvwkcAAAAAJsTd3jAUNxbqDRxa9mjhtbhgG8o';
    $captcha = $_POST['g-recaptcha-response'];

    if(!$captcha){
        echo '<p class="alert alert-warning">Please check the the captcha form.</p>';
        exit;
      }

    // Create an associative array to hold the form data
    $formData = array(
        'vehicleMake' => 'empty',
        'vehicleModel' => 'empty',
        'chassis' => 'empty',
        'vehicleYear' => 'empty',
        'fuelType' => 'empty',
        'vin' => 'empty',
        'buildType' => 'empty',
        'workNeeded' => 'empty',
        'firstName' => $_POST['firstName'],
    'lastName' => $_POST['lastName'],
    'address' => $_POST['address'],
    'address2' => $_POST['address2'],
    'city' => $_POST['city'],
    'state' => $_POST['state'],
    'zip' => $_POST['zip'],
    'phone' => $_POST['phone'],
    'email' => $_POST['email'],
    'vanForSale' => $_POST['vanForSale'],
    'anythingElse' => $_POST['anythingElse'],
    'source' => $_POST['source'],
        'formName' => 'Buy a Van Form'
        // Additional fields can be added here
    );

    // Perform the API call using the formatted array
    apiCallFunction($formData);
}

$target_page = 'van-for-sale/contact-buy-van-ty.html';
header("Location: $target_page");
exit;

// Your form.php should include this function


