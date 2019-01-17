<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp;
use Log;
use App\shopifyapi;
//use RocketCode\Shopify\Exceptions\ShopifyException;
use Illuminate\Support\Collection;

class RunCustomPDP extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'untuckit:runcustompdp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'UnTuckit Custom Shirt PDP';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    private $shopify = null;

    public function handle() {
        //$this->shopify = new \RocketCode\Shopify\Client;

        $jsonreturn = shopifyapi::where('processed', '=', '0');
        $jsonreturn = $jsonreturn->get();

        $xmlvar = '';

        //Check for passing empty XML file
        $talchk2 = false;
        $detailcountcheck2 = 0;
        $needle = getenv('TAL_PRODNUM'); // searched word
        foreach ($jsonreturn as $orderchk) {
            $jsonarrychk = json_decode($orderchk->savetext,true);

            foreach ( $jsonarrychk['line_items'] as $skuchk ) {
                if ( $skuchk[getenv('TAL_PRODCHECK')] == $needle ) {
                    $talchk2 = true;
                    $detailcountcheck2++;
                }
            }
        }


        $xmlvar .= '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlvar .= '<Orders xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';

        foreach ($jsonreturn as $order) {

            $jsonarry = json_decode($order->savetext, true);
            $saveprocess = shopifyapi::where('id', $order->id)->get()->first();
            //$collection1 = json_decode($order->savetext);
            //print $collection1->id;

            //$key = null; // will contain the needed 'global array-key' if a search was successful
            $talchk = false;
            $detailcountcheck = 0;
            $needle = getenv('TAL_PRODNUM'); // searched word

            foreach ($jsonarry['line_items'] as $skuchk) {
                if ($skuchk[getenv('TAL_PRODCHECK')] == $needle) {
                    $talchk = true;
                    $detailcountcheck++;
                }
            }

            if ($talchk) {
                if ($jsonarry['billing_address']['phone'] == '') {
                    $vPhone = '+1 (888) 992-3102';
                } else {
                    $vPhone = $jsonarry['billing_address']['phone'];
                }

                $xmlvar .= "<Order>\n";
                $xmlvar .= "<OrderHeader>\n";
                $xmlvar .= "<OrderID>" . $jsonarry['id'] . "</OrderID>\n";
                $xmlvar .= "<OrderDate>" . substr($jsonarry['created_at'], 0, 19) . "</OrderDate>\n";
                $xmlvar .= "<Customer>\n";
                $xmlvar .= "<ShipToName>" . $jsonarry['billing_address']['first_name'] . " " . $jsonarry['billing_address']['last_name'] . "</ShipToName>\n";
                $xmlvar .= "<StreetAddress>" . $jsonarry['billing_address']['address1'] . "</StreetAddress>\n";
                $xmlvar .= "<StreetAddressLine2>" . $jsonarry['billing_address']['address2'] . "</StreetAddressLine2>\n";
                $xmlvar .= "<City>" . $jsonarry['billing_address']['city'] . "</City>\n";
                $xmlvar .= "<State>" . $jsonarry['billing_address']['province_code'] . "</State>\n";
                $xmlvar .= "<ZipCode>" . $jsonarry['billing_address']['zip'] . "</ZipCode>\n";
                $xmlvar .= "<CustomerAddressCountry>" . $jsonarry['billing_address']['country_code'] . "</CustomerAddressCountry>\n";
                $xmlvar .= "<CustomerPhoneNumber>" . $vPhone . "</CustomerPhoneNumber>\n";
                $xmlvar .= "</Customer>\n";
                $xmlvar .= "</OrderHeader>\n";

                $xmlvar .= "<ShirtDetails>\n";
                $xmlvar .= "<ShirtDetailCount>" . $detailcountcheck . "</ShirtDetailCount>\n";
                $detailcount = 1;

                foreach ($jsonarry['line_items'] as $lineitem) {

                    if ($lineitem[getenv('TAL_PRODCHECK')] == $needle) {

                        $xmlvar .= "<ShirtDetail>\n";

                        $quantity = $lineitem['quantity'];
                        $price = $lineitem['price'];

                        $retailtotalprice = $price * $quantity;

                        $fit = getenv('TAL_FIT');
                        $size = getenv('TAL_SIZE');
                        $wash = getenv('TAL_WASH');
                        $mainfabric = getenv('TAL_FABRIC');
                        $sail = getenv('TAL_SAIL');
                        $placket = getenv('TAL_PLACKET');
                        $collar = getenv('TAL_COLLAR');
                        $cuffstyle = getenv('TAL_CUFF_STYLE');
                        $pocket = getenv('TAL_POCKET');
                        $contrastlocation = getenv('TAL_CONTRAST_LOCATION');
                        $contrastfabric = getenv('TAL_CONTRAST_FABRIC');
                        $bodylength = getenv('TAL_BODY_LENGTH');
                        $rightsleevelength = getenv('TAL_RIGHT_SLEEVE_LENGTH');
                        $leftsleevelength = getenv('TAL_LEFT_SLEEVE_LENGTH');
                        $sleevealteration = getenv('TAL_SLEEVE_ALTERATION');

                        $tmparry = array();
                        foreach ($lineitem['properties'] as $prop) {
                            //print $key . " - " . $value . "\n";
                            //array_push(array, var)
                            $tmparry[$prop['name']] = $prop['value'];
                        }

                        if (isset($tmparry[$contrastlocation])) {
                            if (trim($tmparry[$contrastlocation]) == 'Yes') {
                                $vContrastlocation = "Collar and Placket";
                                $vContrastFabric = $tmparry[$contrastfabric];
                            } else {
                                $vContrastlocation = "No contrast";
                                $vContrastFabric = "None";
                            }
                        } else {
                            $vContrastlocation = "No contrast";
                            $vContrastFabric = "None";
                        }

                        if (isset($tmparry[$pocket])) {
                            if (trim($tmparry[$pocket]) == 'Yes') {
                                $vPocket = "1 Pocket";
                            } else {
                                $vPocket = "No Pocket";
                            }
                        } else {
                            $vPocket = "No Pocket";
                        }

                        if (!isset($tmparry[$sleevealteration])) {
                            $tmparry[$sleevealteration] = 0;
                        }

                        if (!isset($tmparry[$bodylength])) {
                            $tmparry[$bodylength] = 0;
                        }

                        $vRightsleevelength = $tmparry[$sleevealteration];
                        $vLeftsleevelength = $tmparry[$sleevealteration];

                        $xmlvar .= "<ShirtDetailNo>" . $detailcount . "</ShirtDetailNo>\n";
                        $xmlvar .= "<Quantity>" . $lineitem['quantity'] . "</Quantity>\n";
                        $xmlvar .= "<Fit>" . trim($tmparry[$fit]) . "</Fit>\n";
                        $xmlvar .= "<RetailPrice>" . sprintf("%01.2f", $retailtotalprice) . "</RetailPrice>\n";
                        $xmlvar .= "<Size>" . trim($tmparry[$size]) . "</Size>\n";
                        $xmlvar .= "<Wash>Warm Wash + Warm Tumble Dry</Wash>\n";
                        $xmlvar .= "<MainFabric>" . trim($tmparry[$mainfabric]) . "</MainFabric>\n";
                        $xmlvar .= "<Sail>" . trim($tmparry[$sail] ?? "None" ) . "</Sail>\n";
                        $xmlvar .= "<Placket>Regular</Placket>\n";
                        $xmlvar .= "<Collar>" . trim($tmparry[$collar]) . "</Collar>\n";
                        $xmlvar .= "<CuffStyle>1 Button Single Barrel</CuffStyle>\n";
                        $xmlvar .= "<Pocket>" . $vPocket . "</Pocket>\n";
                        # $xmlvar .= "<ContrastFabric>FW01035889-0001</ContrastFabric>\n";
                        $xmlvar .= "<ContrastFabric>" . $vContrastFabric . "</ContrastFabric>\n";
                        $xmlvar .= "<ContrastLocation>" . $vContrastlocation . "</ContrastLocation>\n";
                        $xmlvar .= "<ShirtAlteration>\n";
                        $xmlvar .= "<BodyLength>" . $tmparry[$bodylength] . "</BodyLength>\n";
                        $xmlvar .= "<LeftSleeveLength>" . $vLeftsleevelength . "</LeftSleeveLength>\n";
                        $xmlvar .= "<RightSleeveLength>" . $vRightsleevelength . "</RightSleeveLength>\n";
                        $xmlvar .= "</ShirtAlteration>\n";


                        $xmlvar .= "</ShirtDetail>\n";
                        $detailcount++;
                    }
                }

                $xmlvar .= "</ShirtDetails>\n";
                $xmlvar .= "</Order>\n";
                $saveprocess->processed = 1;
                $saveprocess->save();
            } else {
                $saveprocess->processed = 3;
                $saveprocess->save();
            }

        }
        $xmlvar .= '</Orders>';

        if ( $talchk2 ) {
            //dd($xmlvar);

            $token = $this->getAccessToken();
            if (!is_null($token)) {
                $this->sendXML($xmlvar,$token);
            } else {
                //Log error
                Log::info("FAILURE: Error creating Token\n\n" . $xmlvar);
                print "FAILURE: Error creating Token\n\n" . $xmlvar;
            }
        } else {
            //dd('No XML');
        }

    }

    private function sendXML($xml, $token) {
        $client = new GuzzleHttp\Client();
        $res = $client->request('POST', getenv('TAL_CLIENT_URL') . getenv('TAL_CLIENT_URI'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'body'    => $xml,
        ]);

        if ($res->getStatusCode() != 200) {
            //Log error
            Log::info("FAILURE: Error sending API request - " . $res->getStatusCode() . " | " . $res->getBody() . "\n\n" . $token . "\n\n" . $xml);
            print "FAILURE: Error sending API request - " . $res->getStatusCode() . " | " . $res->getBody() . "\n\n" . $token . "\n\n" . $xml;
        } else {
            //echo $res->getStatusCode();
            // "200"
            //echo $res->getHeader('content-type');
            // 'application/json; charset=utf8'
            //echo $res->getBody();
            // {"type":"User"...'
            Log::info("SUCCESS: XML file sent on:" . date('c') . " - " . $res->getStatusCode() . " | " . $res->getBody() . "\n\n" . $token . "\n\n" . $xml);
            //print "SUCCESS: XML file sent on:" . date('c') . " - " . $res->getStatusCode() . " | " . $res->getBody() . "\n\n" . $token . "\n\n" . $xml;
        }
    }

    private function getAccessToken() {
        $client = new GuzzleHttp\Client();

        $res = $client->request('GET', getenv('TAL_CLIENT_URL') . '/oauth2/token?client_id=' . getenv('TAL_CLIENT_ID') . '&client_secret=' . getenv('TAL_CLIENT_SECRET') . '&grant_type=client_credentials&scope=read write');

        if ($res->getStatusCode() == 200) {
            $resarry = json_decode($res->getBody(), true);
            //echo $resarry['access_token'];
            return $resarry['access_token'];
        } else {
            //print 'Error';
            return NULL;
        }
    }
}
