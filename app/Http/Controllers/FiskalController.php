<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\TempData;
use App\Models\VehicleType;
use App\Models\SystemConfig;

class FiskalController extends Controller
{
    // Parametri za fiskal API, povlače se iz konfiguracije
    private $apiUrl;
    private $apiToken;
    private $enuIdentifier;
    private $userCode;
    private $userName;
    private $sellerName;
    private $sellerIdType;
    private $sellerIdValue;
    private $sellerAddress;

    // Mapa kodova grešaka i njihovih značenja
    private $fiskalErrorCodes = [
        '11'  => 'Pogrešni podaci (npr. nedostaje adresa kupca, pogrešan znak količine/cene, nema stavki na računu).',
        '44'  => 'Pogrešan PDV za firmu (kontaktirati podršku).',
        '58'  => 'Depozit nije unet (za gotovinske račune).',
        '78'  => 'Račun sa ovim brojem već postoji (nije kritična greška, možeš koristiti podatke iz odgovora).',
        '500' => 'Nema interneta ili servis ne radi.',
        '900' => 'Greška na poreskom serveru.',
        '901' => 'Greška na poreskom serveru.',
        '902' => 'Greška na poreskom serveru.',
        '903' => 'Greška na poreskom serveru.',
        '904' => 'Greška na poreskom serveru.',
        '905' => 'Greška na poreskom serveru.',
        '906' => 'Greška na poreskom serveru.',
        '907' => 'Greška na poreskom serveru.',
        '908' => 'Greška na poreskom serveru.',
        '909' => 'Greška na poreskom serveru.',
        '910' => 'Greška na poreskom serveru.',
        '911' => 'Greška na poreskom serveru.',
        '912' => 'Greška na poreskom serveru.',
        '913' => 'Greška na poreskom serveru.',
        '914' => 'Greška na poreskom serveru.',
        '915' => 'Greška na poreskom serveru.',
        '916' => 'Greška na poreskom serveru.',
        '917' => 'Greška na poreskom serveru.',
        '918' => 'Greška na poreskom serveru.',
        '919' => 'Greška na poreskom serveru.',
        '920' => 'Greška na poreskom serveru.',
        '999' => 'Servis je privremeno nedostupan.',
    ];

    // Konstruktor - učitava parametre iz config/services.php
    public function __construct()
    {
        $this->apiUrl = config('services.fiscal.api_url');
        $this->apiToken = config('services.fiscal.api_token');
        $this->enuIdentifier = config('services.fiscal.enu_identifier');
        $this->userCode = config('services.fiscal.user_code');
        $this->userName = config('services.fiscal.user_name');
        $this->sellerName = config('services.fiscal.seller_name');
        $this->sellerIdType = config('services.fiscal.seller_id_type');
        $this->sellerIdValue = config('services.fiscal.seller_id_value');
        $this->sellerAddress = config('services.fiscal.seller_address');
    }

    /**
     * Inicijalizacija depozita za fiskalizaciju (INITIAL DEPOSIT)
     * Poziva se pre fiskalizacije, šalje osnovne podatke o transakciji.
     */
    public function initDeposit($merchantTransactionId)
    {
        try {
            $payload = [
                'UID' => $merchantTransactionId,
                'ENUIdentifier' => $this->enuIdentifier,
                'Type' => 'json',
                'DepositType' => 'INITIAL',
                'Amount' => 0,
                'DateSend' => now()->format('Y-m-d'),
                'User' => [
                    'UserCode' => $this->userCode,
                    'UserName' => $this->userName
                ]
            ];

            // Poziv fiskal API-ja za inicijalizaciju depozita
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->apiUrl . '/api/efiscal/deposit', $payload);

            Log::info('Fiskal initDeposit response', [
                'merchantTransactionId' => $merchantTransactionId,
                'payload' => $payload,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Fiskal initDeposit error: ' . $e->getMessage(), [
                'merchantTransactionId' => $merchantTransactionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Glavna fiskalizacija (slanje računa)
     * Pronalazi temp podatke i tip vozila, koristi document_number iz system_config,
     * šalje zahtev za fiskalizaciju i po uspehu povećava document_number za 1.
     */
    public function fiscalization($merchantTransactionId)
    {
        try {
            // Pronađi privremene podatke za transakciju
            $temp = TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
            if (!$temp) {
                throw new \Exception('Temp data not found for merchantTransactionId: ' . $merchantTransactionId);
            }

            // Pronađi tip vozila
            $vehicleType = VehicleType::find($temp->vehicle_type_id);
            if (!$vehicleType) {
                throw new \Exception('Vehicle type not found for ID: ' . $temp->vehicle_type_id);
            }

            // Dohvati trenutni document_number iz system_config (kao red sa name = 'document_number')
            $docConfig = SystemConfig::where('name', 'document_number')->first();
            $documentNumber = $docConfig ? $docConfig->value : 1;

            // Pripremi payload za fiskalizaciju
            $payload = [
                'UID' => $merchantTransactionId,
                'ENUIdentifier' => $this->enuIdentifier,
                'DocumentType' => 'INVOICE',
                'DocumentNumber' => $documentNumber,
                'BasePriceIsWithoutTax' => false,
                'IsNoCashReceipt' => false,
                'DateSend' => now()->format('Y-m-d\TH:i:sP'),
                'User' => [
                    'UserCode' => $this->userCode,
                    'UserName' => $this->userName
                ],
                'Seller' => [
                    'Name' => $this->sellerName,
                    'IDType' => $this->sellerIdType,
                    'IDValue' => $this->sellerIdValue,
                    'Address' => $this->sellerAddress
                ],
                'Sales' => [
                    'ItemSaleRow' => [
                        [
                            'ItemCode' => (string)$vehicleType->id,
                            'ItemName' => $vehicleType->description_vehicle,
                            'Price' => $vehicleType->price,
                            'DiscountPercentage' => 0,
                            'DiscountAmount' => 0,
                            'Quantity' => 1,
                            'TaxRate' => 0
                        ]
                    ]
                ],
                'Payments' => [
                    'PaymentRow' => [
                        [
                            'PaymentAmount' => $vehicleType->price,
                            'PaymentType' => 'CARD'
                        ]
                    ]
                ],
                'Type' => 'json'
            ];

            // Poziv fiskal API-ja za fiskalizaciju
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->apiUrl . '/api/efiscal/fiscalReceipt', $payload);

            Log::info('Fiskal fiscalization response', [
                'merchantTransactionId' => $merchantTransactionId,
                'payload' => $payload,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                // Povećaj document_number za 1 i sačuvaj u system_config
                if ($docConfig) {
                    $docConfig->value = $documentNumber + 1;
                    $docConfig->save();
                }

                // Sačuvaj fiskalne podatke u temp_data
                $temp->fiscal_jir = $responseData['ResponseCode'] ?? null; // JIKR
                $temp->fiscal_ikof = $responseData['UIDRequest'] ?? null;  // IKOF
                $temp->fiscal_qr = $responseData['Url']['Value'] ?? null;  // QR (ispravljeno!)
                $temp->fiscal_operator = $this->enuIdentifier;             // ENUIdentifier
                $temp->fiscal_date = $payload['DateSend'] ?? now();        // DateSend
                $temp->save();

                return [
                    'success' => true,
                    'data' => [
                        'qr' => $responseData['Url']['Value'] ?? null,
                        'ikof' => $responseData['UIDRequest'] ?? null,
                        'jikr' => $responseData['ResponseCode'] ?? null,
                        'merchant_transaction_id' => $merchantTransactionId,
                        'operator' => $this->enuIdentifier,
                        'date_send' => $payload['DateSend'] ?? now(),
                    ],
                    'documentNumber' => $documentNumber
                ];
            }

            if (!$response->successful() || !($responseData['IsSucccess'] ?? true)) {
                $errorCode = $responseData['Error']['ErrorCode'] ?? null;
                $errorMessage = $responseData['Error']['ErrorMessage'] ?? ($response->body() ?? 'Nepoznata greška');
                $errorMeaning = $this->fiskalErrorCodes[$errorCode] ?? 'Nepoznat kod greške';

                // Pripremi podatke za upis u failed_fiskal
                \DB::table('failed_fiskal')->insert([
                    'merchant_transaction_id' => $merchantTransactionId,
                    'qr' => $responseData['Value'] ?? null,
                    'ikof' => $responseData['UIDRequest'] ?? null,
                    'jikr' => $responseData['ResponseCode'] ?? null,
                    'operator' => $this->enuIdentifier,
                    'date_send' => $payload['DateSend'] ?? now(),
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'attempts' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Pripremi i pošalji email
                $emailBody = "Neuspješna fiskalizacija\n\n"
                    . "Kod greške: $errorCode\n"
                    . "Značenje: $errorMeaning\n"
                    . "Opis greške: $errorMessage\n\n"
                    . "Podaci o fiskalizaciji:\n"
                    . "QR: " . ($responseData['Value'] ?? '') . "\n"
                    . "IKOF: " . ($responseData['UIDRequest'] ?? '') . "\n"
                    . "JIKR: " . ($responseData['ResponseCode'] ?? '') . "\n"
                    . "Operator: " . $this->enuIdentifier . "\n"
                    . "DateSend: " . ($payload['DateSend'] ?? now()) . "\n"
                    . "merchant_transaction_id: $merchantTransactionId\n\n"
                    . "Podaci o rezervaciji:\n"
                    . print_r($temp->toArray(), true);

                \Mail::raw($emailBody, function ($message) {
                    $message->to('bus@kotor.me')
                        ->subject('Neuspješna fiskalizacija');
                });

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'data' => [
                        'qr' => $responseData['Value'] ?? null,
                        'ikof' => $responseData['UIDRequest'] ?? null,
                        'jikr' => $responseData['ResponseCode'] ?? null,
                        'merchant_transaction_id' => $merchantTransactionId,
                        'operator' => $this->enuIdentifier,
                        'date_send' => $payload['DateSend'] ?? now(),
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Fiskal fiscalization error: ' . $e->getMessage(), [
                'merchantTransactionId' => $merchantTransactionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Storniranje (poništavanje) fiskalnog računa
     * $originalData treba da sadrži: original UID, IssueDate, Type, Total, vehicleTypeId, price, description_vehicle
     */
    public function cancelReceipt($merchantTransactionId, $originalData)
    {
        try {
            // Prvo pozovi initDeposit za storno transakciju
            $initResult = $this->initDeposit($merchantTransactionId);
            if (!$initResult['success']) {
                Log::error('Fiskal initDeposit failed for storno', $initResult);
                return [
                    'success' => false,
                    'error' => 'InitDeposit failed: ' . ($initResult['error'] ?? 'Unknown error')
                ];
            }

            // Dohvati novi document_number
            $docConfig = SystemConfig::where('name', 'document_number')->first();
            $documentNumber = $docConfig ? $docConfig->value : 1;

            // Pronađi tip vozila
            $vehicleType = VehicleType::find($originalData['vehicleTypeId']);
            if (!$vehicleType) {
                throw new \Exception('Vehicle type not found for ID: ' . $originalData['vehicleTypeId']);
            }

            // Pripremi payload za korektivni račun
            $payload = [
                'UID' => $merchantTransactionId,
                'ENUIdentifier' => $this->enuIdentifier,
                'DocumentType' => 'CORRECTIVE_INVOICE',
                'DocumentNumber' => $documentNumber,
                'BasePriceIsWithoutTax' => false,
                'IsNoCashReceipt' => false,
                'DateSend' => now()->format('Y-m-d\TH:i:sP'),
                'ConnectedDocuments' => [
                    'DocumentRow' => [
                        [
                            'UID' => $originalData['originalUID'],
                            'IssueDate' => $originalData['originalIssueDate'],
                            'Type' => 'INVOICE',
                            'Total' => $originalData['originalTotal']
                        ]
                    ]
                ],
                'User' => [
                    'UserCode' => $this->userCode,
                    'UserName' => $this->userName
                ],
                'Seller' => [
                    'Name' => $this->sellerName,
                    'IDType' => $this->sellerIdType,
                    'IDValue' => $this->sellerIdValue,
                    'Address' => $this->sellerAddress
                ],
                'Sales' => [
                    'ItemSaleRow' => [
                        [
                            'ItemCode' => (string)$vehicleType->id,
                            'ItemName' => $vehicleType->description_vehicle,
                            'Price' => $vehicleType->price,
                            'DiscountPercentage' => 0,
                            'DiscountAmount' => 0,
                            'Quantity' => -1,
                            'TaxRate' => 0
                        ]
                    ]
                ],
                'Payments' => [
                    'PaymentRow' => [
                        [
                            'PaymentAmount' => -1 * $vehicleType->price,
                            'PaymentType' => 'CARD'
                        ]
                    ]
                ],
                'Type' => 'json'
            ];

            // Poziv fiskal API-ja za korektivni račun
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->apiUrl . '/api/efiscal/fiscalReceipt', $payload);

            Log::info('Fiskal cancelReceipt (CORRECTIVE_INVOICE) response', [
                'merchantTransactionId' => $merchantTransactionId,
                'payload' => $payload,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Proveri da li je zaista uspešno
                if ($responseData['IsSucccess'] ?? true) {
                    // Povećaj document_number za 1
                    if ($docConfig) {
                        $docConfig->value = $documentNumber + 1;
                        $docConfig->save();
                    }
                    return [
                        'success' => true,
                        'data' => $responseData,
                        'documentNumber' => $documentNumber
                    ];
                } else {
                    // Fiskalizacija nije uspešna
                    $errorCode = $responseData['Error']['ErrorCode'] ?? null;
                    $errorMessage = $responseData['Error']['ErrorMessage'] ?? 'Nepoznata greška';
                    return [
                        'success' => false,
                        'error' => $errorMessage,
                        'errorCode' => $errorCode
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Fiskal cancelReceipt error: ' . $e->getMessage(), [
                'merchantTransactionId' => $merchantTransactionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 