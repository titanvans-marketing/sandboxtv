<?php

declare(strict_types=1);

require_once __DIR__ . '/../private/airtable-config.php';

/*
|--------------------------------------------------------------------------
| Core Airtable request helper
|--------------------------------------------------------------------------
| Supports both styles:
|
| 1) Existing client logic:
|    airtable_request('GET', AIRTABLE_CLIENTS_TABLE, [], ['filterByFormula' => '...']);
|    airtable_request('POST', AIRTABLE_CLIENTS_TABLE, $payload);
|    airtable_request('PATCH', AIRTABLE_CLIENTS_TABLE, $payload);
|
| 2) Direct record-id style:
|    airtable_request('DELETE', AIRTABLE_VEHICLES_TABLE, 'recXXXXXXXXXXXXXX');
|    airtable_request('PATCH', AIRTABLE_VEHICLES_TABLE, 'recXXXXXXXXXXXXXX', ['fields' => [...]]);
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Core Airtable API helpers
|--------------------------------------------------------------------------
*/
/**
 * Sends a request to the Airtable API and returns the decoded response array.
 */
function airtable_request(
    string $method,
    string $tableId,
    string|array|null $recordIdOrPayload = null,
    ?array $payloadOrQuery = null,
    array $query = []
): array {
    $method = strtoupper(trim($method));

    $recordId = null;
    $payload = [];
    $finalQuery = [];

    if (is_array($recordIdOrPayload)) {
        /*
        |--------------------------------------------------------------------------
        | Existing usage
        |--------------------------------------------------------------------------
        */
        $payload = $recordIdOrPayload;
        $finalQuery = is_array($payloadOrQuery) ? $payloadOrQuery : [];
    } else {
        /*
        |--------------------------------------------------------------------------
        | Record-id usage
        |--------------------------------------------------------------------------
        */
        $recordId = is_string($recordIdOrPayload) ? trim($recordIdOrPayload) : null;

        if (in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            $payload = is_array($payloadOrQuery) ? $payloadOrQuery : [];
            $finalQuery = $query;
        } else {
            $payload = [];
            $finalQuery = is_array($payloadOrQuery) ? $payloadOrQuery : $query;
        }
    }

    $url = 'https://api.airtable.com/v0/' . rawurlencode(AIRTABLE_BASE_ID) . '/' . rawurlencode($tableId);

    if ($recordId !== null && $recordId !== '') {
        $url .= '/' . rawurlencode($recordId);
    }

    if (!empty($finalQuery)) {
        $url .= '?' . http_build_query($finalQuery);
    }

    $ch = curl_init($url);

    if ($ch === false) {
        throw new RuntimeException('Unable to initialize Airtable cURL request.');
    }

    $headers = [
        'Authorization: Bearer ' . AIRTABLE_TOKEN,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
    ];

    if (in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode Airtable payload.');
        }

        $options[CURLOPT_POSTFIELDS] = $json;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Curl error: ' . $error);
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);

    if ($status < 200 || $status >= 300) {
        $message = $decoded['error']['message']
            ?? $decoded['message']
            ?? ('Airtable request failed with status ' . $status);

        throw new RuntimeException('Airtable API ' . $status . ': ' . $message);
    }

    return is_array($decoded) ? $decoded : [];
}

/**
 * Checks whether an Airtable exception represents a 404 not found response.
 */
function airtable_is_not_found_error(Throwable $e): bool
{
    return str_contains($e->getMessage(), 'Airtable API 404');
}

/**
 * Escapes a string for safe use inside an Airtable formula.
 */
function airtable_escape_formula(string $value): string
{
    return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
}


/*
|--------------------------------------------------------------------------
| Shared normalization helpers
|--------------------------------------------------------------------------
*/
/**
 * Normalizes an email address for consistent Airtable matching.
 */
function airtable_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

/**
 * Normalizes a phone number for consistent Airtable matching.
 */
function airtable_normalize_phone(string $phone): string
{
    return preg_replace('/\s+/', '', trim($phone));
}

/**
 * Trims a value and returns null when it is empty.
 */
function airtable_trim_or_null(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);

    return $value === '' ? null : $value;
}

/**
 * Removes null and empty-string values from a fields array.
 */
function airtable_filter_null_fields(array $fields): array
{
    return array_filter(
        $fields,
        static fn($value) => $value !== null && $value !== ''
    );
}

/**
 * Normalizes mixed truthy and falsy input into a nullable boolean.
 */
function airtable_normalize_bool(mixed $value): ?bool
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value === 1;
    }

    $normalized = strtolower(trim((string) $value));

    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Airtable field name helpers
|--------------------------------------------------------------------------
*/
/**
 * Returns the configured owner field name for the Vehicles table.
 */
function airtable_vehicle_owner_field_name(): string
{
    return defined('AIRTABLE_FIELD_VEHICLE_OWNER')
        ? AIRTABLE_FIELD_VEHICLE_OWNER
        : 'Owner';
}

/**
 * Returns the configured color field name for the Vehicles table.
 */
function airtable_vehicle_color_field_name(): string
{
    return defined('AIRTABLE_FIELD_VEHICLE_COLOR')
        ? AIRTABLE_FIELD_VEHICLE_COLOR
        : 'Vehicle Color';
}


/*
|--------------------------------------------------------------------------
| Client lookup helpers
|--------------------------------------------------------------------------
*/
/**
 * Finds an Airtable client record by email address.
 */
function airtable_find_client_by_email(string $email): ?array
{
    $email = airtable_normalize_email($email);

    if ($email === '') {
        return null;
    }

    $formula = 'LOWER({' . AIRTABLE_FIELD_EMAIL . '}) = "' . airtable_escape_formula($email) . '"';

    $result = airtable_request(
        'GET',
        AIRTABLE_CLIENTS_TABLE,
        [],
        [
            'filterByFormula' => $formula,
            'maxRecords' => 1,
        ]
    );

    return $result['records'][0] ?? null;
}

/**
 * Finds an Airtable client record by phone number.
 */
function airtable_find_client_by_phone(string $phone): ?array
{
    $phone = airtable_normalize_phone($phone);

    if ($phone === '') {
        return null;
    }

    $formula = '{' . AIRTABLE_FIELD_PHONE . '} = "' . airtable_escape_formula($phone) . '"';

    $result = airtable_request(
        'GET',
        AIRTABLE_CLIENTS_TABLE,
        [],
        [
            'filterByFormula' => $formula,
            'maxRecords' => 1,
        ]
    );

    return $result['records'][0] ?? null;
}

/**
 * Finds an Airtable client record by Airtable record id.
 */
function airtable_find_client_by_record_id(string $recordId): ?array
{
    $recordId = trim($recordId);

    if ($recordId === '') {
        return null;
    }

    $formula = 'RECORD_ID() = "' . airtable_escape_formula($recordId) . '"';

    $result = airtable_request(
        'GET',
        AIRTABLE_CLIENTS_TABLE,
        [],
        [
            'filterByFormula' => $formula,
            'maxRecords' => 1,
        ]
    );

    return $result['records'][0] ?? null;
}

/**
 * Finds the best matching existing client using record id, email, or phone.
 */
function airtable_find_existing_client(array $user, ?string $preferredRecordId = null): ?array
{
    $preferredRecordId = trim((string) $preferredRecordId);

    if ($preferredRecordId !== '') {
        $record = airtable_find_client_by_record_id($preferredRecordId);

        if ($record !== null) {
            return $record;
        }
    }

    if (!empty($user['email'])) {
        $record = airtable_find_client_by_email((string) $user['email']);
        if ($record !== null) {
            return $record;
        }
    }

    if (!empty($user['phone'])) {
        $record = airtable_find_client_by_phone((string) $user['phone']);
        if ($record !== null) {
            return $record;
        }
    }

    return null;
}

/**
 * Extracts the display name from an Airtable select field value.
 */
function airtable_get_select_name(mixed $value): ?string
{
    if (is_array($value) && isset($value['name'])) {
        return (string) $value['name'];
    }

    if (is_string($value)) {
        return $value;
    }

    return null;
}

/**
 * Extracts a collaborator id from an Airtable collaborator field value.
 */
function airtable_get_collaborator_id(mixed $value): ?string
{
    if (is_array($value) && isset($value['id'])) {
        return (string) $value['id'];
    }

    if (is_array($value) && isset($value[0]) && is_array($value[0]) && isset($value[0]['id'])) {
        return (string) $value[0]['id'];
    }

    return null;
}

/**
 * Maps a raw Airtable client record into the local normalized client shape.
 */
function airtable_client_fields_from_record(?array $record): array
{
    $fields = is_array($record['fields'] ?? null) ? $record['fields'] : [];

    return [
        'record_id' => (string) ($record['id'] ?? ''),
        'first_name' => trim((string) ($fields[AIRTABLE_FIELD_FIRST_NAME] ?? '')),
        'last_name' => trim((string) ($fields[AIRTABLE_FIELD_LAST_NAME] ?? '')),
        'email' => airtable_normalize_email((string) ($fields[AIRTABLE_FIELD_EMAIL] ?? '')),
        'phone' => trim((string) ($fields[AIRTABLE_FIELD_PHONE] ?? '')),
        'address' => trim((string) ($fields[AIRTABLE_FIELD_ADDRESS] ?? '')),
        'client_id' => trim((string) ($fields[AIRTABLE_FIELD_CLIENT_NUMBER] ?? '')),
        'date_of_inquiry' => trim((string) ($fields[AIRTABLE_FIELD_DATE_OF_INQUIRY] ?? '')),
        'sales_status' => airtable_get_select_name($fields[AIRTABLE_FIELD_SALE_STATUS] ?? null),
        'sales_department' => airtable_get_select_name($fields[AIRTABLE_FIELD_SALE_DEPARTMENT] ?? null),
        'responsible_sales_person_id' => airtable_get_collaborator_id($fields[AIRTABLE_FIELD_RESPONSIBLE_SALES_PERSON] ?? null),
        'sales_notes' => trim((string) ($fields[AIRTABLE_FIELD_SALES_NOTES] ?? '')),
    ];
}

/*
|--------------------------------------------------------------------------
| Registration sync
|--------------------------------------------------------------------------
| If a client already exists in Airtable, Airtable stays authoritative for
| core profile fields. If Airtable is missing a value, local registration
| input fills the blank.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Client payload builders and sync actions
|--------------------------------------------------------------------------
*/
/**
 * Builds the Airtable client payload used during registration sync.
 */
function airtable_build_registration_fields(array $user, array $existing = [], bool $isNewClient = false): array
{
    $resolvedFirstName = trim((string) ($existing['first_name'] ?? '')) !== ''
        ? trim((string) $existing['first_name'])
        : trim((string) ($user['first_name'] ?? ''));

    $resolvedLastName = trim((string) ($existing['last_name'] ?? '')) !== ''
        ? trim((string) $existing['last_name'])
        : trim((string) ($user['last_name'] ?? ''));

    $resolvedEmail = trim((string) ($existing['email'] ?? '')) !== ''
        ? airtable_normalize_email((string) $existing['email'])
        : airtable_normalize_email((string) ($user['email'] ?? ''));

    $resolvedPhone = trim((string) ($existing['phone'] ?? '')) !== ''
        ? trim((string) $existing['phone'])
        : trim((string) ($user['phone'] ?? ''));

    $resolvedAddress = trim((string) ($existing['address'] ?? '')) !== ''
        ? trim((string) ($existing['address'] ?? ''))
        : trim((string) ($user['address'] ?? ''));

    $fields = [
        AIRTABLE_FIELD_FIRST_NAME => $resolvedFirstName,
        AIRTABLE_FIELD_LAST_NAME => $resolvedLastName,
        AIRTABLE_FIELD_EMAIL => $resolvedEmail,
    ];

    if ($resolvedPhone !== '') {
        $fields[AIRTABLE_FIELD_PHONE] = $resolvedPhone;
    }

    if ($resolvedAddress !== '') {
        $fields[AIRTABLE_FIELD_ADDRESS] = $resolvedAddress;
    }

    if ($isNewClient) {
        $fields[AIRTABLE_FIELD_DATE_OF_INQUIRY] = date('Y-m-d');
        $fields[AIRTABLE_FIELD_INTAKE_FORM_TIME] = date('c');
        $fields[AIRTABLE_FIELD_SALE_STATUS] = (string) ($user['sale_status'] ?? 'Open');
        $fields[AIRTABLE_FIELD_SALE_DEPARTMENT] = (string) ($user['sale_department'] ?? 'Service');
        $fields[AIRTABLE_FIELD_CONTACT_PREFERENCE] = (string) ($user['contact_preference'] ?? 'Yes');
        $fields[AIRTABLE_FIELD_CONTACT_METHOD] = (string) ($user['contact_method'] ?? 'Form Submission');
        $fields[AIRTABLE_FIELD_FORM_FILLED_WEBSITE] = (string) ($user['form_filled'] ?? 'Service');

        $salesNotes = trim((string) ($user['sales_notes'] ?? 'Portal account registration created from website.'));
        if ($salesNotes !== '') {
            $fields[AIRTABLE_FIELD_SALES_NOTES] = $salesNotes;
        }
    }

    return $fields;
}

/*
|--------------------------------------------------------------------------
| Explicit profile update
|--------------------------------------------------------------------------
*/
/**
 * Builds the Airtable client payload used during profile updates.
 */
function airtable_build_profile_update_fields(array $user, array $existing = []): array
{
    $firstName = trim((string) ($user['first_name'] ?? ''));
    $lastName = trim((string) ($user['last_name'] ?? ''));
    $email = airtable_normalize_email((string) ($user['email'] ?? ''));
    $phone = array_key_exists('phone', $user)
        ? trim((string) ($user['phone'] ?? ''))
        : trim((string) ($existing['phone'] ?? ''));
    $address = array_key_exists('address', $user)
        ? trim((string) ($user['address'] ?? ''))
        : trim((string) ($existing['address'] ?? ''));

    $fields = [
        AIRTABLE_FIELD_FIRST_NAME => $firstName,
        AIRTABLE_FIELD_LAST_NAME => $lastName,
        AIRTABLE_FIELD_EMAIL => $email,
    ];

    if ($phone !== '') {
        $fields[AIRTABLE_FIELD_PHONE] = $phone;
    }

    if ($address !== '') {
        $fields[AIRTABLE_FIELD_ADDRESS] = $address;
    }

    return $fields;
}

/**
 * Creates a new client record in Airtable.
 */
function airtable_create_client_record(array $fields): array
{
    $payload = [
        'records' => [
            [
                'fields' => $fields,
            ],
        ],
        'typecast' => true,
    ];

    $result = airtable_request('POST', AIRTABLE_CLIENTS_TABLE, $payload);

    return $result['records'][0] ?? [];
}

/**
 * Updates an existing Airtable client record.
 */
function airtable_update_client_record(string $recordId, array $fields): array
{
    $payload = [
        'records' => [
            [
                'id' => $recordId,
                'fields' => $fields,
            ],
        ],
        'typecast' => true,
    ];

    $result = airtable_request('PATCH', AIRTABLE_CLIENTS_TABLE, $payload);

    return $result['records'][0] ?? [];
}

/**
 * Syncs a registration user to Airtable and returns the resulting record.
 */
function airtable_sync_registration_user(array $user, ?array $existingRecord = null, ?string $preferredRecordId = null): array
{
    $existingRecord = $existingRecord ?? airtable_find_existing_client($user, $preferredRecordId);
    $existing = airtable_client_fields_from_record($existingRecord);
    $isNewClient = empty($existing['record_id']);

    $fields = airtable_build_registration_fields($user, $existing, $isNewClient);

    if (!$isNewClient) {
        return airtable_update_client_record($existing['record_id'], $fields);
    }

    return airtable_create_client_record($fields);
}

/**
 * Pushes an explicit local profile update to Airtable.
 */
function airtable_push_profile_update(array $user, ?array $existingRecord = null, ?string $preferredRecordId = null): array
{
    $existingRecord = $existingRecord ?? airtable_find_existing_client($user, $preferredRecordId);
    $existing = airtable_client_fields_from_record($existingRecord);

    $fields = airtable_build_profile_update_fields($user, $existing);

    if (!empty($existing['record_id'])) {
        return airtable_update_client_record($existing['record_id'], $fields);
    }

    return airtable_create_client_record($fields);
}

/*
|--------------------------------------------------------------------------
| Local user sync metadata helpers
|--------------------------------------------------------------------------
*/
/**
 * Marks a local user Airtable sync as successful.
 */
function airtable_mark_sync_success(PDO $pdo, int $userId, ?string $recordId = null): void
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET
            airtable_client_record_id = COALESCE(?, airtable_client_record_id),
            airtable_sync_status = 'synced',
            airtable_sync_error = NULL,
            airtable_last_synced_at = NOW(),
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([
        $recordId !== '' ? $recordId : null,
        $userId,
    ]);
}

/**
 * Marks a local user Airtable sync as failed and stores the error message.
 */
function airtable_mark_sync_error(PDO $pdo, int $userId, string $message): void
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET
            airtable_sync_status = 'error',
            airtable_sync_error = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([
        mb_substr($message, 0, 65535),
        $userId,
    ]);
}


/*
|--------------------------------------------------------------------------
| Local database schema helpers
|--------------------------------------------------------------------------
*/
/**
 * Checks whether a column exists on a local database table.
 */
function table_has_column(PDO $pdo, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        throw new InvalidArgumentException('Invalid table name supplied.');
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);

    return (bool) $stmt->fetch();
}

/*
|--------------------------------------------------------------------------
| Linked Airtable client record lookup from local users table
|--------------------------------------------------------------------------
*/
/**
 * Returns the Airtable client record id linked to a local user.
 */
function get_user_airtable_client_record_id(PDO $pdo, int $userId): ?string
{
    if (!table_has_column($pdo, 'users', 'airtable_client_record_id')) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT airtable_client_record_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);

    $recordId = $stmt->fetchColumn();

    return $recordId ? (string) $recordId : null;
}

/*
|--------------------------------------------------------------------------
| Build Airtable vehicle fields from local vehicle data
|--------------------------------------------------------------------------
| Current local vehicle screens use:
| make, model, year, vin, build_type, color, notes
|
| This mapper safely supports those plus optional future fields if you later
| add them locally:
| chassis, fuel_type, license_plate_number, license_plate_state, is_primary
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Vehicle field mapping helpers
|--------------------------------------------------------------------------
*/
/**
 * Builds the Airtable Vehicles payload from a local vehicle row.
 */
function build_airtable_vehicle_fields(PDO $pdo, int $userId, array $vehicle): array
{
    $make = airtable_trim_or_null($vehicle['make'] ?? null);
    $model = airtable_trim_or_null($vehicle['model'] ?? null);
    $year = airtable_trim_or_null($vehicle['year'] ?? null);
    $vin = airtable_trim_or_null($vehicle['vin'] ?? null);
    $color = airtable_trim_or_null($vehicle['color'] ?? null);

    $chassis = airtable_trim_or_null($vehicle['chassis'] ?? null);
    $fuelType = airtable_trim_or_null($vehicle['fuel_type'] ?? null);

    $licensePlateNumber = airtable_trim_or_null($vehicle['license_plate_number'] ?? null);
    $licensePlateState = airtable_trim_or_null($vehicle['license_plate_state'] ?? null);

    $fields = [
        AIRTABLE_FIELD_VEHICLE_YEAR => $year,
        AIRTABLE_FIELD_VEHICLE_MODEL => $model,
        AIRTABLE_FIELD_VEHICLE_MAKE => $make,
        AIRTABLE_FIELD_VEHICLE_VIN => $vin,
    ];

    /*
    |--------------------------------------------------------------------------
    | Owner linked-record field in Airtable Vehicles table
    |--------------------------------------------------------------------------
    */
    $clientRecordId = get_user_airtable_client_record_id($pdo, $userId);
    if ($clientRecordId) {
        $fields[airtable_vehicle_owner_field_name()] = [$clientRecordId];
    }

    /*
    |--------------------------------------------------------------------------
    | Optional supported fields
    |--------------------------------------------------------------------------
    */
    if ($chassis !== null && defined('AIRTABLE_FIELD_VEHICLE_CHASSIS')) {
        $fields[AIRTABLE_FIELD_VEHICLE_CHASSIS] = $chassis;
    }

    if ($fuelType !== null && defined('AIRTABLE_FIELD_VEHICLE_FUEL_TYPE')) {
        $fields[AIRTABLE_FIELD_VEHICLE_FUEL_TYPE] = $fuelType;
    }

    if ($licensePlateNumber !== null && defined('AIRTABLE_FIELD_VEHICLE_LICENSE_PLATE_NUMBER')) {
        $fields[AIRTABLE_FIELD_VEHICLE_LICENSE_PLATE_NUMBER] = $licensePlateNumber;
    }

    if ($licensePlateState !== null && defined('AIRTABLE_FIELD_VEHICLE_LICENSE_PLATE_STATE')) {
        $fields[AIRTABLE_FIELD_VEHICLE_LICENSE_PLATE_STATE] = $licensePlateState;
    }

    /*
    |--------------------------------------------------------------------------
    | Vehicle Color
    |--------------------------------------------------------------------------
    */
    if ($color !== null) {
        $fields[airtable_vehicle_color_field_name()] = $color;
    }

    /*
    |--------------------------------------------------------------------------
    | Optional primary vehicle checkbox
    |--------------------------------------------------------------------------
    */
    $primaryValue = null;

    if (array_key_exists('is_primary', $vehicle)) {
        $primaryValue = airtable_normalize_bool($vehicle['is_primary']);
    } elseif (array_key_exists('primary', $vehicle)) {
        $primaryValue = airtable_normalize_bool($vehicle['primary']);
    }

    if ($primaryValue !== null && defined('AIRTABLE_FIELD_VEHICLE_PRIMARY')) {
        $fields[AIRTABLE_FIELD_VEHICLE_PRIMARY] = $primaryValue;
    }

    return airtable_filter_null_fields($fields);
}

/*
|--------------------------------------------------------------------------
| Local vehicle sync metadata helpers
|--------------------------------------------------------------------------
*/
/**
 * Marks a local vehicle as pending Airtable sync.
 */
function set_vehicle_sync_pending(PDO $pdo, int $vehicleId, ?string $existingRecordId = null): void
{
    $stmt = $pdo->prepare("
        UPDATE vehicles
        SET
            airtable_vehicle_record_id = ?,
            airtable_sync_status = 'pending',
            airtable_sync_error = NULL
        WHERE id = ?
    ");
    $stmt->execute([$existingRecordId, $vehicleId]);
}

/**
 * Marks a local vehicle Airtable sync as successful.
 */
function set_vehicle_sync_success(PDO $pdo, int $vehicleId, string $recordId): void
{
    $stmt = $pdo->prepare("
        UPDATE vehicles
        SET
            airtable_vehicle_record_id = ?,
            airtable_sync_status = 'synced',
            airtable_sync_error = NULL,
            airtable_last_synced_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$recordId, $vehicleId]);
}

/**
 * Marks a local vehicle Airtable sync as failed and stores the error.
 */
function set_vehicle_sync_failed(PDO $pdo, int $vehicleId, ?string $existingRecordId, string $errorMessage): void
{
    $stmt = $pdo->prepare("
        UPDATE vehicles
        SET
            airtable_vehicle_record_id = ?,
            airtable_sync_status = 'failed',
            airtable_sync_error = ?,
            airtable_last_synced_at = NULL
        WHERE id = ?
    ");
    $stmt->execute([
        $existingRecordId,
        mb_substr($errorMessage, 0, 65000),
        $vehicleId,
    ]);
}

/*
|--------------------------------------------------------------------------
| Vehicle create/update/delete wrappers
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Vehicle Airtable mutations
|--------------------------------------------------------------------------
*/
/**
 * Creates a new vehicle record in Airtable.
 */
function create_airtable_vehicle(PDO $pdo, int $userId, array $vehicle): array
{
    $fields = build_airtable_vehicle_fields($pdo, $userId, $vehicle);

    if (empty($fields)) {
        throw new RuntimeException('No mapped vehicle fields were available to send to Airtable.');
    }

    $payload = [
        'records' => [
            [
                'fields' => $fields,
            ],
        ],
        'typecast' => true,
    ];

    $response = airtable_request('POST', AIRTABLE_VEHICLES_TABLE, $payload);
    $record = $response['records'][0] ?? null;

    if (!is_array($record) || empty($record['id'])) {
        throw new RuntimeException('Airtable did not return a vehicle record id.');
    }

    return $record;
}

/**
 * Updates an existing vehicle record in Airtable.
 */
function update_airtable_vehicle(PDO $pdo, int $userId, string $recordId, array $vehicle): array
{
    $fields = build_airtable_vehicle_fields($pdo, $userId, $vehicle);

    if (empty($fields)) {
        throw new RuntimeException('No mapped vehicle fields were available to send to Airtable.');
    }

    $payload = [
        'records' => [
            [
                'id' => $recordId,
                'fields' => $fields,
            ],
        ],
        'typecast' => true,
    ];

    $response = airtable_request('PATCH', AIRTABLE_VEHICLES_TABLE, $payload);

    return $response['records'][0] ?? [];
}

/**
 * Deletes a vehicle record from Airtable by record id.
 */
function delete_airtable_vehicle(string $recordId): array
{
    $recordId = trim($recordId);

    if ($recordId === '') {
        throw new InvalidArgumentException('Airtable vehicle record id is required for deletion.');
    }

    return airtable_request('DELETE', AIRTABLE_VEHICLES_TABLE, $recordId);
}

/*
|--------------------------------------------------------------------------
| Main vehicle sync entry point
|--------------------------------------------------------------------------
| If local row already has an Airtable record id, try update first.
| If that Airtable record no longer exists, create a new one.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Vehicle sync entry point
|--------------------------------------------------------------------------
*/
/**
 * Creates or updates a vehicle in Airtable and updates local sync metadata.
 */
function sync_vehicle_to_airtable(PDO $pdo, int $userId, array $vehicle): string
{
    $vehicleId = (int) ($vehicle['id'] ?? 0);
    $existingRecordId = airtable_trim_or_null($vehicle['airtable_vehicle_record_id'] ?? null);

    if ($vehicleId <= 0) {
        throw new InvalidArgumentException('Vehicle id is required for Airtable sync.');
    }

    set_vehicle_sync_pending($pdo, $vehicleId, $existingRecordId);

    try {
        if ($existingRecordId) {
            try {
                update_airtable_vehicle($pdo, $userId, $existingRecordId, $vehicle);
                set_vehicle_sync_success($pdo, $vehicleId, $existingRecordId);

                return $existingRecordId;
            } catch (Throwable $e) {
                if (!airtable_is_not_found_error($e)) {
                    throw $e;
                }
            }
        }

        $created = create_airtable_vehicle($pdo, $userId, $vehicle);
        $newRecordId = (string) $created['id'];

        set_vehicle_sync_success($pdo, $vehicleId, $newRecordId);

        return $newRecordId;
    } catch (Throwable $e) {
        set_vehicle_sync_failed($pdo, $vehicleId, $existingRecordId, $e->getMessage());
        throw $e;
    }
}
/*
|--------------------------------------------------------------------------
| Cached table column lookup
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Cached schema helpers
|--------------------------------------------------------------------------
*/
/**
 * Caches column existence checks to avoid repeated schema lookups.
 */
function cached_table_has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];

    $cacheKey = $table . '.' . $column;

    if (!array_key_exists($cacheKey, $cache)) {
        $cache[$cacheKey] = table_has_column($pdo, $table, $column);
    }

    return $cache[$cacheKey];
}

/*
|--------------------------------------------------------------------------
| Sales Orders sync helpers
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Sales Orders shared helpers
|--------------------------------------------------------------------------
*/
/**
 * Returns a configured Airtable constant value or a fallback default.
 */
function airtable_config_name(string $constantName, string $default): string
{
    if (defined($constantName)) {
        $value = trim((string) constant($constantName));

        if ($value !== '') {
            return $value;
        }
    }

    return $default;
}

/**
 * Builds a unique ordered list of preferred and fallback Airtable field names.
 */
function airtable_candidate_field_keys(array $preferred, array $fallbacks = []): array
{
    $keys = array_merge($preferred, $fallbacks);
    $keys = array_map(
        static fn($value): string => trim((string) $value),
        $keys
    );

    return array_values(array_unique(array_filter(
        $keys,
        static fn($value): bool => $value !== ''
    )));
}

/**
 * Returns the first existing field value from a list of possible Airtable field names.
 */
function airtable_first_existing_field_value(array $fields, array $possibleKeys, mixed $default = null): mixed
{
    foreach ($possibleKeys as $key) {
        if (array_key_exists($key, $fields)) {
            return $fields[$key];
        }
    }

    return $default;
}


/*
|--------------------------------------------------------------------------
| Sales Orders field resolution helpers
|--------------------------------------------------------------------------
*/
/**
 * Returns the possible linked-client field names used by Sales Orders.
 */
function airtable_sales_order_customer_field_keys(): array
{
    return airtable_candidate_field_keys(
        [
            airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_CUSTOMER', 'Client'),
        ],
        [
            'Client',
            'Customer',
            'Clients',
            'Owner',
        ]
    );
}


/**
 * Returns the possible VIN field names used by Sales Orders.
 */
function airtable_sales_order_vin_field_keys(): array
{
    return airtable_candidate_field_keys(
        [
            airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_VIN', 'VIN (from Vehicle Link)'),
        ],
        [
            'VIN (from Vehicle Link)',
            'VIN',
            'VIN (from Vehicle)',
        ]
    );
}

/**
 * Returns the primary VIN field name used for VIN formulas.
 */
function airtable_sales_order_primary_vin_field_name(): string
{
    $keys = airtable_sales_order_vin_field_keys();

    return $keys[0] ?? 'VIN (from Vehicle Link)';
}

/**
 * Builds an Airtable filter formula that matches a normalized VIN.
 */
function airtable_build_sales_order_vin_filter_formula(?string $vehicleVin): ?string
{
    $vehicleVin = airtable_normalize_vin($vehicleVin);

    if ($vehicleVin === '') {
        return null;
    }

    $vinField = airtable_sales_order_primary_vin_field_name();

    return 'UPPER(SUBSTITUTE(SUBSTITUTE({' . $vinField . '}&"", "-", ""), " ", "")) = "' .
        airtable_escape_formula($vehicleVin) .
        '"';
}


/*
|--------------------------------------------------------------------------
| Sales Orders normalization helpers
|--------------------------------------------------------------------------
*/
/**
 * Extracts linked record ids from a linked-record field value.
 */
function airtable_linked_record_ids(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }

    $ids = [];

    foreach ($value as $item) {
        if (is_string($item)) {
            $item = trim($item);

            if ($item !== '') {
                $ids[] = $item;
            }
        }
    }

    return array_values(array_unique($ids));
}

/**
 * Extracts linked record ids from the first matching record fields.
 */
function airtable_linked_record_ids_from_fields(array $fields, array $possibleKeys): array
{
    $ids = [];

    foreach ($possibleKeys as $key) {
        if (!array_key_exists($key, $fields)) {
            continue;
        }

        $ids = array_merge($ids, airtable_linked_record_ids($fields[$key]));
    }

    $ids = array_map(
        static fn($value): string => trim((string) $value),
        $ids
    );

    return array_values(array_unique(array_filter(
        $ids,
        static fn($value): bool => $value !== ''
    )));
}

/**
 * Returns the first non-empty string value from the provided field names.
 */
function airtable_record_field_string(array $fields, array $possibleKeys): string
{
    foreach ($possibleKeys as $key) {
        if (!is_string($key) || trim($key) === '' || !array_key_exists($key, $fields)) {
            continue;
        }

        $value = $fields[$key];

        if (is_array($value)) {
            $parts = [];

            foreach ($value as $item) {
                if (is_array($item)) {
                    $candidate = trim((string) (
                        $item['name']
                        ?? $item['url']
                        ?? $item['id']
                        ?? ''
                    ));
                } else {
                    $candidate = trim((string) $item);
                }

                if ($candidate !== '') {
                    $parts[] = $candidate;
                }
            }

            $stringValue = implode(', ', $parts);
        } else {
            $stringValue = trim((string) $value);
        }

        if ($stringValue !== '') {
            return $stringValue;
        }
    }

    return '';
}

/**
 * Converts a numeric-like value into a float when possible.
 */
function airtable_number_or_null(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        return (float) $value;
    }

    $normalized = str_replace(['$', ','], '', trim((string) $value));

    return is_numeric($normalized) ? (float) $normalized : null;
}

/**
 * Normalizes a VIN for reliable comparisons and filtering.
 */
function airtable_normalize_vin(?string $vin): string
{
    $vin = strtoupper(trim((string) $vin));

    if ($vin === '') {
        return '';
    }

    $normalized = preg_replace('/[^A-Z0-9]/', '', $vin);

    return is_string($normalized) ? $normalized : '';
}

/**
 * Converts a date-like value into MySQL Y-m-d format when possible.
 */
function airtable_mysql_date_or_null(mixed $value): ?string
{
    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($value))->format('Y-m-d');
    } catch (Throwable) {
        return null;
    }
}

/**
 * Builds a safe fallback filename from a file URL.
 */
function airtable_guess_filename_from_url(string $url): string
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    $name = basename($path);

    return $name !== '' ? $name : 'attachment';
}

/**
 * Guesses a file type from Airtable attachment metadata.
 */
function airtable_guess_file_type(array $attachment): ?string
{
    $type = trim((string) ($attachment['type'] ?? ''));

    if ($type !== '') {
        return $type;
    }

    $filename = trim((string) ($attachment['filename'] ?? ''));

    if ($filename !== '') {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : null;
    }

    $url = trim((string) ($attachment['url'] ?? ''));

    if ($url !== '') {
        $ext = strtolower((string) pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : null;
    }

    return null;
}

/**
 * Normalizes Airtable attachment rows into a consistent local structure.
 */
function airtable_normalize_attachment_rows(mixed $value, string $sourceField): array
{
    if (!is_array($value)) {
        return [];
    }

    $rows = [];

    foreach ($value as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }

        $url = trim((string) ($attachment['url'] ?? ''));

        if ($url === '') {
            continue;
        }

        $rows[] = [
            'attachment_id' => trim((string) ($attachment['id'] ?? '')),
            'url' => $url,
            'filename' => trim((string) ($attachment['filename'] ?? '')) ?: airtable_guess_filename_from_url($url),
            'type' => airtable_guess_file_type($attachment),
            'source_field' => $sourceField,
        ];
    }

    return $rows;
}


/*
|--------------------------------------------------------------------------
| Sales Orders mapping helpers
|--------------------------------------------------------------------------
*/
/**
 * Returns the best available sort date for a Sales Order record.
 */
function airtable_sales_order_sort_date(array $record): string
{
    $fields = is_array($record['fields'] ?? null) ? $record['fields'] : [];

    $completionDate = airtable_record_field_string($fields, [
        airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_COMPLETION_DATE', 'Job Completion Date'),
        'Job Completion Date',
    ]);

    $startDate = airtable_record_field_string($fields, [
        airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_START_DATE', 'Start Date'),
        'Start Date',
    ]);

    $createdTime = trim((string) ($record['createdTime'] ?? ''));

    if ($completionDate !== '') {
        return $completionDate;
    }

    if ($startDate !== '') {
        return $startDate;
    }

    return $createdTime;
}

/**
 * Maps a raw Airtable Sales Order record into the normalized local structure.
 */
function airtable_sales_order_fields_from_record(array $record): array
{
    $fields = is_array($record['fields'] ?? null) ? $record['fields'] : [];

    $jobNumber = airtable_record_field_string($fields, airtable_candidate_field_keys(
        [
            airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_JOB_NUMBER', 'Job #'),
        ],
        [
            'Job #',
        ]
    ));

    $title = airtable_record_field_string($fields, airtable_candidate_field_keys(
        [
            airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_CANNED_JOB_NAME', 'Canned Job Name'),
            airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_JOB_NUMBER', 'Job #'),
        ],
        [
            'Canned Job Name',
            'Job #',
        ]
    ));

    if ($title === '' && $jobNumber !== '') {
        $title = 'Job ' . $jobNumber;
    }

    if ($title === '') {
        $title = 'Sales Order';
    }

    $productionPhotosField = airtable_record_field_string(
        ['Production Photos' => 'Production Photos'],
        ['Production Photos']
    );
    $vehicleImagesField = airtable_record_field_string(
        ['Vehicle Images' => 'Vehicle Images'],
        ['Vehicle Images']
    );

    $productionPhotos = airtable_first_existing_field_value($fields, airtable_candidate_field_keys(
        [
            airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_PRODUCTION_PHOTOS', 'Production Photos'),
        ],
        [
            'Production Photos',
        ]
    ), []);

    $vehicleImages = airtable_first_existing_field_value($fields, airtable_candidate_field_keys(
        [
            airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_VEHICLE_IMAGES', 'Vehicle Images'),
        ],
        [
            'Vehicle Images',
        ]
    ), []);

    $attachments = array_merge(
        airtable_normalize_attachment_rows($productionPhotos, $productionPhotosField),
        airtable_normalize_attachment_rows($vehicleImages, $vehicleImagesField)
    );

    return [
        'record_id' => trim((string) ($record['id'] ?? '')),
        'job_number' => $jobNumber,
        'title' => $title,
        'status' => airtable_record_field_string($fields, airtable_candidate_field_keys(
            [
                airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_STATUS', 'Job Status'),
            ],
            [
                'Job Status',
            ]
        )),
        'start_date' => airtable_record_field_string($fields, airtable_candidate_field_keys(
            [
                airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_START_DATE', 'Start Date'),
            ],
            [
                'Start Date',
            ]
        )),
        'completion_date' => airtable_record_field_string($fields, airtable_candidate_field_keys(
            [
                airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_COMPLETION_DATE', 'Job Completion Date'),
            ],
            [
                'Job Completion Date',
            ]
        )),
        'vehicle' => airtable_record_field_string($fields, airtable_candidate_field_keys(
            [
                airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_VEHICLE', 'Make & Model'),
            ],
            [
                'Make & Model',
            ]
        )),
        'vin' => airtable_record_field_string($fields, airtable_sales_order_vin_field_keys()),
        'invoice_notes' => airtable_record_field_string($fields, airtable_candidate_field_keys(
            [
                airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_INVOICE_NOTES', 'Invoice Notes'),
            ],
            [
                'Invoice Notes',
            ]
        )),
        'sales_notes' => airtable_record_field_string($fields, airtable_candidate_field_keys(
            [
                airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_SALES_NOTES', 'Sales Notes'),
            ],
            [
                'Sales Notes',
            ]
        )),
        'scheduled_bay' => airtable_record_field_string($fields, airtable_candidate_field_keys(
            [
                airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_SCHEDULED_BAY', 'Scheduled Bay'),
            ],
            [
                'Scheduled Bay',
            ]
        )),
        'sales_order_total' => airtable_number_or_null(
            airtable_first_existing_field_value($fields, airtable_candidate_field_keys(
                [
                    airtable_config_name('AIRTABLE_FIELD_SALES_ORDER_TOTAL', 'Sales Order Total'),
                ],
                [
                    'Sales Order Total',
                ]
            ))
        ),
        'customer_record_ids' => airtable_linked_record_ids_from_fields(
            $fields,
            airtable_sales_order_customer_field_keys()
        ),
        'attachments' => $attachments,
        'sort_date' => airtable_sales_order_sort_date($record),
    ];
}


/*
|--------------------------------------------------------------------------
| Sales Orders fetch and filter helpers
|--------------------------------------------------------------------------
*/
/**
 * Fetches Sales Orders linked to a specific Airtable client record id.
 */
function airtable_find_sales_orders_by_client_record_id(string $clientRecordId, ?string $vehicleVin = null): array
{
    $clientRecordId = trim($clientRecordId);

    if ($clientRecordId === '') {
        return [];
    }

    $matchingRecords = [];
    $offset = null;
    $pageCount = 0;
    $maxPages = 10;

    do {
        $query = [
            'pageSize' => 100,
        ];

        $formula = airtable_build_sales_order_vin_filter_formula($vehicleVin);

        if ($formula !== null) {
            $query['filterByFormula'] = $formula;
        }

        if ($offset !== null && $offset !== '') {
            $query['offset'] = $offset;
        }

        $response = airtable_request(
            'GET',
            AIRTABLE_SALES_ORDERS_TABLE,
            [],
            $query
        );

        $records = $response['records'] ?? [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $fields = is_array($record['fields'] ?? null) ? $record['fields'] : [];

            $linkedClientIds = airtable_linked_record_ids_from_fields(
                $fields,
                airtable_sales_order_customer_field_keys()
            );

            if (in_array($clientRecordId, $linkedClientIds, true)) {
                $matchingRecords[] = $record;
            }
        }

        $offset = $response['offset'] ?? null;
        $pageCount++;

        if ($pageCount >= $maxPages) {
            break;
        }
    } while ($offset !== null && $offset !== '');

    usort($matchingRecords, static function (array $a, array $b): int {
        return strcmp(
            airtable_sales_order_sort_date($b),
            airtable_sales_order_sort_date($a)
        );
    });

    return $matchingRecords;
}

/**
 * Filters Sales Orders down to those matching the selected vehicle VIN.
 */
function airtable_filter_sales_orders_for_vehicle(array $salesOrders, ?string $vehicleVin): array
{
    $vehicleVin = airtable_normalize_vin($vehicleVin);

    if ($vehicleVin === '') {
        return [];
    }

    return array_values(array_filter(
        $salesOrders,
        static function (array $salesOrder) use ($vehicleVin): bool {
            $orderVin = airtable_normalize_vin((string) ($salesOrder['vin'] ?? ''));

            return $orderVin !== '' && $orderVin === $vehicleVin;
        }
    ));
}

/**
 * Returns the current user’s Sales Orders for the selected vehicle.
 */
function airtable_get_user_sales_orders_for_vehicle(PDO $pdo, int $userId, ?string $vehicleVin): array
{
    $clientRecordId = get_user_airtable_client_record_id($pdo, $userId);

    if ($clientRecordId === null || trim($clientRecordId) === '') {
        return [];
    }

    $records = airtable_find_sales_orders_by_client_record_id($clientRecordId, $vehicleVin);
    $salesOrders = array_map('airtable_sales_order_fields_from_record', $records);

    return airtable_filter_sales_orders_for_vehicle($salesOrders, $vehicleVin);
}


/*
|--------------------------------------------------------------------------
| Local Sales Orders persistence helpers
|--------------------------------------------------------------------------
*/
/**
 * Maps an Airtable Sales Order status into the local service status.
 */
function airtable_map_sales_order_status_to_local(string $airtableStatus): string
{
    $status = strtoupper(trim($airtableStatus));

    if ($status === '') {
        return 'scheduled';
    }

    if (str_contains($status, 'CANCEL')) {
        return 'cancelled';
    }

    if (
        str_contains($status, 'COMPLETE') ||
        str_contains($status, 'READY FOR PICKUP')
    ) {
        return 'completed';
    }

    if (
        str_contains($status, 'PROGRESS') ||
        str_contains($status, 'WAITING FOR PARTS') ||
        str_contains($status, 'IN SHOP') ||
        str_contains($status, 'WORKING')
    ) {
        return 'in_progress';
    }

    return 'scheduled';
}

/**
 * Finds the matching local service history row for a Sales Order sync.
 */
function airtable_find_existing_service_history_id(PDO $pdo, int $vehicleId, array $salesOrder): ?int
{
    $recordId = trim((string) ($salesOrder['record_id'] ?? ''));

    if ($recordId !== '' && cached_table_has_column($pdo, 'service_history', 'airtable_sales_order_record_id')) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM service_history
            WHERE airtable_sales_order_record_id = ?
            LIMIT 1
        ");
        $stmt->execute([$recordId]);

        $existingId = $stmt->fetchColumn();

        return $existingId ? (int) $existingId : null;
    }

    $serviceDate = airtable_mysql_date_or_null(
        ($salesOrder['completion_date'] ?? '') !== ''
        ? $salesOrder['completion_date']
        : ($salesOrder['start_date'] ?? '')
    );

    $serviceType = trim((string) ($salesOrder['title'] ?? 'Sales Order'));

    $stmt = $pdo->prepare("
        SELECT id
        FROM service_history
        WHERE vehicle_id = ?
          AND service_type = ?
          AND service_date <=> ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        $vehicleId,
        mb_substr($serviceType !== '' ? $serviceType : 'Sales Order', 0, 150),
        $serviceDate,
    ]);

    $existingId = $stmt->fetchColumn();

    return $existingId ? (int) $existingId : null;
}

/**
 * Creates or updates a local service history row from a Sales Order.
 */
function airtable_upsert_service_history_row(PDO $pdo, int $vehicleId, array $salesOrder): int
{
    $existingId = airtable_find_existing_service_history_id($pdo, $vehicleId, $salesOrder);

    $serviceDate = airtable_mysql_date_or_null(
        ($salesOrder['completion_date'] ?? '') !== ''
        ? $salesOrder['completion_date']
        : ($salesOrder['start_date'] ?? '')
    );

    $serviceType = trim((string) ($salesOrder['title'] ?? ''));
    if ($serviceType === '') {
        $serviceType = 'Sales Order';
    }

    $status = airtable_map_sales_order_status_to_local((string) ($salesOrder['status'] ?? ''));
    $description = airtable_trim_or_null($salesOrder['invoice_notes'] ?? null);
    $technicianNotes = airtable_trim_or_null($salesOrder['sales_notes'] ?? null);
    $totalCost = $salesOrder['sales_order_total'] ?? null;
    $recordId = trim((string) ($salesOrder['record_id'] ?? ''));

    if ($existingId !== null) {
        $set = [
            'vehicle_id = ?',
            'service_date = ?',
            'service_type = ?',
            'status = ?',
            'mileage = ?',
            'description = ?',
            'technician_notes = ?',
            'total_cost = ?',
        ];

        $params = [
            $vehicleId,
            $serviceDate,
            mb_substr($serviceType, 0, 150),
            $status,
            null,
            $description,
            $technicianNotes,
            $totalCost,
        ];

        if (cached_table_has_column($pdo, 'service_history', 'airtable_sales_order_record_id')) {
            $set[] = 'airtable_sales_order_record_id = ?';
            $params[] = $recordId !== '' ? $recordId : null;
        }

        if (cached_table_has_column($pdo, 'service_history', 'source')) {
            $set[] = 'source = ?';
            $params[] = 'airtable';
        }

        if (cached_table_has_column($pdo, 'service_history', 'airtable_last_synced_at')) {
            $set[] = 'airtable_last_synced_at = NOW()';
        }

        if (cached_table_has_column($pdo, 'service_history', 'updated_at')) {
            $set[] = 'updated_at = CURRENT_TIMESTAMP';
        }

        $params[] = $existingId;

        $sql = "
            UPDATE service_history
            SET " . implode(",\n                ", $set) . "
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $existingId;
    }

    $columns = [
        'vehicle_id',
        'service_date',
        'service_type',
        'status',
        'mileage',
        'description',
        'technician_notes',
        'total_cost',
    ];

    $placeholders = array_fill(0, count($columns), '?');

    $params = [
        $vehicleId,
        $serviceDate,
        mb_substr($serviceType, 0, 150),
        $status,
        null,
        $description,
        $technicianNotes,
        $totalCost,
    ];

    if (cached_table_has_column($pdo, 'service_history', 'airtable_sales_order_record_id')) {
        $columns[] = 'airtable_sales_order_record_id';
        $placeholders[] = '?';
        $params[] = $recordId !== '' ? $recordId : null;
    }

    if (cached_table_has_column($pdo, 'service_history', 'source')) {
        $columns[] = 'source';
        $placeholders[] = '?';
        $params[] = 'airtable';
    }

    if (cached_table_has_column($pdo, 'service_history', 'airtable_last_synced_at')) {
        $columns[] = 'airtable_last_synced_at';
        $placeholders[] = 'NOW()';
    }

    $sql = "
        INSERT INTO service_history (
            " . implode(",\n            ", $columns) . "
        ) VALUES (
            " . implode(",\n            ", $placeholders) . "
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $pdo->lastInsertId();
}

/**
 * Finds an existing local service document row for an Airtable attachment.
 */
function airtable_find_existing_service_document_id(PDO $pdo, int $serviceHistoryId, array $attachment): ?int
{
    $attachmentId = trim((string) ($attachment['attachment_id'] ?? ''));

    if ($attachmentId !== '' && cached_table_has_column($pdo, 'service_documents', 'airtable_attachment_id')) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM service_documents
            WHERE airtable_attachment_id = ?
            LIMIT 1
        ");
        $stmt->execute([$attachmentId]);

        $existingId = $stmt->fetchColumn();

        return $existingId ? (int) $existingId : null;
    }

    $url = trim((string) ($attachment['url'] ?? ''));

    if ($url === '') {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM service_documents
        WHERE service_history_id = ?
          AND file_path = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$serviceHistoryId, mb_substr($url, 0, 500)]);

    $existingId = $stmt->fetchColumn();

    return $existingId ? (int) $existingId : null;
}

/**
 * Creates or updates a local service document row from an Airtable attachment.
 */
function airtable_upsert_service_document(PDO $pdo, int $serviceHistoryId, array $attachment): void
{
    $url = trim((string) ($attachment['url'] ?? ''));

    if ($url === '') {
        return;
    }

    $fileName = trim((string) ($attachment['filename'] ?? ''));
    if ($fileName === '') {
        $fileName = airtable_guess_filename_from_url($url);
    }

    $fileType = airtable_trim_or_null($attachment['type'] ?? null);
    $attachmentId = trim((string) ($attachment['attachment_id'] ?? ''));

    $existingId = airtable_find_existing_service_document_id($pdo, $serviceHistoryId, $attachment);

    if ($existingId !== null) {
        $set = [
            'service_history_id = ?',
            'file_name = ?',
            'file_path = ?',
            'file_type = ?',
        ];

        $params = [
            $serviceHistoryId,
            mb_substr($fileName, 0, 255),
            mb_substr($url, 0, 500),
            $fileType,
        ];

        if (cached_table_has_column($pdo, 'service_documents', 'airtable_attachment_id')) {
            $set[] = 'airtable_attachment_id = ?';
            $params[] = $attachmentId !== '' ? $attachmentId : null;
        }

        if (cached_table_has_column($pdo, 'service_documents', 'source')) {
            $set[] = 'source = ?';
            $params[] = 'airtable';
        }

        if (cached_table_has_column($pdo, 'service_documents', 'external_url')) {
            $set[] = 'external_url = ?';
            $params[] = mb_substr($url, 0, 1000);
        }

        if (cached_table_has_column($pdo, 'service_documents', 'airtable_last_synced_at')) {
            $set[] = 'airtable_last_synced_at = NOW()';
        }

        $params[] = $existingId;

        $sql = "
            UPDATE service_documents
            SET " . implode(",\n                ", $set) . "
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return;
    }

    $columns = [
        'service_history_id',
        'file_name',
        'file_path',
        'file_type',
    ];

    $placeholders = array_fill(0, count($columns), '?');

    $params = [
        $serviceHistoryId,
        mb_substr($fileName, 0, 255),
        mb_substr($url, 0, 500),
        $fileType,
    ];

    if (cached_table_has_column($pdo, 'service_documents', 'airtable_attachment_id')) {
        $columns[] = 'airtable_attachment_id';
        $placeholders[] = '?';
        $params[] = $attachmentId !== '' ? $attachmentId : null;
    }

    if (cached_table_has_column($pdo, 'service_documents', 'source')) {
        $columns[] = 'source';
        $placeholders[] = '?';
        $params[] = 'airtable';
    }

    if (cached_table_has_column($pdo, 'service_documents', 'external_url')) {
        $columns[] = 'external_url';
        $placeholders[] = '?';
        $params[] = mb_substr($url, 0, 1000);
    }

    if (cached_table_has_column($pdo, 'service_documents', 'airtable_last_synced_at')) {
        $columns[] = 'airtable_last_synced_at';
        $placeholders[] = 'NOW()';
    }

    $sql = "
        INSERT INTO service_documents (
            " . implode(",\n            ", $columns) . "
        ) VALUES (
            " . implode(",\n            ", $placeholders) . "
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

/**
 * Synchronizes Airtable attachment rows into local service documents.
 */
function airtable_sync_service_documents(PDO $pdo, int $serviceHistoryId, array $attachments): void
{
    $normalizedAttachments = [];
    $seenKeys = [];

    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }

        $attachmentId = trim((string) ($attachment['attachment_id'] ?? ''));
        $url = trim((string) ($attachment['url'] ?? ''));

        if ($attachmentId === '' && $url === '') {
            continue;
        }

        $dedupeKey = $attachmentId !== '' ? 'id:' . $attachmentId : 'url:' . $url;

        if (isset($seenKeys[$dedupeKey])) {
            continue;
        }

        $seenKeys[$dedupeKey] = true;
        $normalizedAttachments[] = $attachment;
    }

    foreach ($normalizedAttachments as $attachment) {
        airtable_upsert_service_document($pdo, $serviceHistoryId, $attachment);
    }

    if (!cached_table_has_column($pdo, 'service_documents', 'source')) {
        return;
    }

    if (cached_table_has_column($pdo, 'service_documents', 'airtable_attachment_id')) {
        $activeIds = [];

        foreach ($normalizedAttachments as $attachment) {
            $attachmentId = trim((string) ($attachment['attachment_id'] ?? ''));

            if ($attachmentId !== '') {
                $activeIds[] = $attachmentId;
            }
        }

        $activeIds = array_values(array_unique($activeIds));

        if ($activeIds === []) {
            $stmt = $pdo->prepare("
                DELETE FROM service_documents
                WHERE service_history_id = ?
                  AND source = 'airtable'
            ");
            $stmt->execute([$serviceHistoryId]);

            return;
        }

        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $stmt = $pdo->prepare("
            DELETE FROM service_documents
            WHERE service_history_id = ?
              AND source = 'airtable'
              AND airtable_attachment_id NOT IN ($placeholders)
        ");
        $stmt->execute(array_merge([$serviceHistoryId], $activeIds));

        return;
    }

    $activePaths = [];

    foreach ($normalizedAttachments as $attachment) {
        $url = trim((string) ($attachment['url'] ?? ''));

        if ($url !== '') {
            $activePaths[] = mb_substr($url, 0, 500);
        }
    }

    $activePaths = array_values(array_unique($activePaths));

    if ($activePaths === []) {
        $stmt = $pdo->prepare("
            DELETE FROM service_documents
            WHERE service_history_id = ?
              AND source = 'airtable'
        ");
        $stmt->execute([$serviceHistoryId]);

        return;
    }

    $placeholders = implode(',', array_fill(0, count($activePaths), '?'));

    $stmt = $pdo->prepare("
        DELETE FROM service_documents
        WHERE service_history_id = ?
          AND source = 'airtable'
          AND file_path NOT IN ($placeholders)
    ");
    $stmt->execute(array_merge([$serviceHistoryId], $activePaths));
}

/**
 * Deletes stale Airtable-synced service history rows for a vehicle.
 */
function airtable_delete_stale_service_history_rows(PDO $pdo, int $vehicleId, array $activeRecordIds): void
{
    if (
        !cached_table_has_column($pdo, 'service_history', 'source') ||
        !cached_table_has_column($pdo, 'service_history', 'airtable_sales_order_record_id')
    ) {
        return;
    }

    $activeRecordIds = array_values(array_unique(array_filter(array_map(
        static fn($value): string => trim((string) $value),
        $activeRecordIds
    ))));

    if ($activeRecordIds === []) {
        $stmt = $pdo->prepare("
            DELETE FROM service_history
            WHERE vehicle_id = ?
              AND source = 'airtable'
        ");
        $stmt->execute([$vehicleId]);

        return;
    }

    $placeholders = implode(',', array_fill(0, count($activeRecordIds), '?'));

    $stmt = $pdo->prepare("
        DELETE FROM service_history
        WHERE vehicle_id = ?
          AND source = 'airtable'
          AND airtable_sales_order_record_id NOT IN ($placeholders)
    ");
    $stmt->execute(array_merge([$vehicleId], $activeRecordIds));
}


/*
|--------------------------------------------------------------------------
| Sales Orders sync entry point
|--------------------------------------------------------------------------
*/
/**
 * Syncs the selected vehicle’s Airtable Sales Orders into local tables.
 */
function sync_airtable_sales_orders_to_local_for_vehicle(PDO $pdo, int $userId, int $vehicleId, ?string $vehicleVin): array
{
    $clientRecordId = get_user_airtable_client_record_id($pdo, $userId);

    if ($clientRecordId === null || trim($clientRecordId) === '') {
        return [
            'success' => false,
            'count' => 0,
            'message' => 'No Airtable client record id is linked to this user yet.',
        ];
    }

    $vehicleVin = airtable_normalize_vin($vehicleVin);

    if ($vehicleVin === '') {
        return [
            'success' => false,
            'count' => 0,
            'message' => 'This vehicle does not have a VIN yet.',
        ];
    }

    $salesOrders = airtable_get_user_sales_orders_for_vehicle($pdo, $userId, $vehicleVin);

    $syncedCount = 0;
    $activeRecordIds = [];

    $pdo->beginTransaction();

    try {
        foreach ($salesOrders as $salesOrder) {
            $recordId = trim((string) ($salesOrder['record_id'] ?? ''));

            if ($recordId === '') {
                continue;
            }

            $activeRecordIds[] = $recordId;

            $serviceHistoryId = airtable_upsert_service_history_row($pdo, $vehicleId, $salesOrder);

            airtable_sync_service_documents(
                $pdo,
                $serviceHistoryId,
                is_array($salesOrder['attachments'] ?? null) ? $salesOrder['attachments'] : []
            );

            $syncedCount++;
        }

        airtable_delete_stale_service_history_rows($pdo, $vehicleId, $activeRecordIds);

        $pdo->commit();

        return [
            'success' => true,
            'count' => $syncedCount,
            'message' => 'Airtable sales orders synced successfully.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}