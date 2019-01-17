<?php

namespace App\Jobs;

use App\Services\TalClient;
use App\shopifyapi;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use RocketCode\Shopify\Exceptions\ShopifyException;
use RocketCode\Shopify\Objects\Fulfillment;

class CreateOrderFulfillment implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    protected $order;

    /**
     * @var
     */
    protected $accessToken;

    /**
     * Create a new job instance.
     *
     * @param shopifyapi $order
     * @param null $accessToken
     */
    public function __construct(shopifyapi $order, $accessToken = null) {
        $this->order = $order;
        $this->accessToken = $accessToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $saveText = json_decode($this->order->savetext, true);

        # Make sure savetext has an order ID
        if (!isset($saveText['id'])) {
            \Log::error("Could not process the order({$this->order->id}) because the savetext was missing the {id} field.");
            print("Could not process the order({$this->order->id}) because the savetext was missing the {id} field.\n");
            $this->fail();

            return;
        }

        $talClient = new TalClient();
        $orderId = $saveText['id'];
        $res = $talClient->getOrder($orderId, $this->accessToken);

        # Dump out if no success
        if (!$res['success']) {
            \Log::error("Order($orderId): could not fetch any data from TAL.");
            print("Order($orderId): could not fetch any data from TAL.\n");
            $this->fail();

            return;
        }

        # Dump out if no data is present
        if (!$res['data']) {
            \Log::info("Order($orderId): could not find any order data.");
            print("Order($orderId): could not find any order data.\n");
            return;
        }

        $customerOrders = $res['data'];

        if (!isset($customerOrders[env('TAL_CUSTOMER_ORDER')])) {
            $field = env('TAL_CUSTOMER_ORDER');
            \Log::error("Unexpected JSON format for order: {$orderId}. Expected $field.");
            $this->fail();

            return;
        }

        $customerOrderObject = $customerOrders[env('TAL_CUSTOMER_ORDER')];

        # Check if any data was returned in the customer orders
        if (is_null($customerOrderObject) || empty($customerOrderObject)) {
            print("Order($orderId) successfully received a response but no order data was found\n");
            return;
        }

        # Grab the first item in the orders since we are only querying for one order
        $customerOrder = $customerOrderObject[0];

        if (!isset($customerOrder[env('TAL_CUSTOMER_ORDER_LINES')])) {
            $field = env('TAL_CUSTOMER_ORDER');
            \Log::error("Unexpected JSON format for order: {$orderId}. Expected $field");
            $this->fail();

            return;
        }

        # TAL order line items
        $orderLineItems = $customerOrder[env('TAL_CUSTOMER_ORDER_LINES')];
        $trackingIds = [];
        # Custom shirt line items from DB for this order
        $customShirts = [];

        if (isset($saveText['line_items'])) {
            $customShirts = collect($saveText['line_items'])->filter(function ($i) {
                # Has field and it's set to our env value
                return isset($i[env('TAL_PRODCHECK')]) && $i[env('TAL_PRODCHECK')] == env('TAL_PRODNUM');
            });
        }

        # DB order did not have any custom shirts
        # Caller should only pass in orders with custom shirts, but just in case we check here too
        if (sizeof($customShirts) < 1) {
            \Log::info("Order({$orderId}) did not have any custom shirts");
            print("Order({$saveText}) did not have any custom shirts\n");
            return;
        }

        # Iterate through the line items for the TAL order
        foreach ($orderLineItems as $li) {
            if (!isset($li[env('TAL_CUSTOMER_ORDER_INTERNALS')])) {
                continue;
            }

            if (empty($li[env('TAL_CUSTOMER_ORDER_INTERNALS')])) {
                continue;
            }

            # Filter internal orders having a tracking number
            $haveTracking = collect($li[env('TAL_CUSTOMER_ORDER_INTERNALS')])->filter(function ($iO) {
                return isset($iO[env('TAL_CUSTOMER_ORDER_LINE_TRACKING')]) && !empty($iO[env('TAL_CUSTOMER_ORDER_LINE_TRACKING')]);
            })->first();

            # Has at least one tracking so we grab the first
            if (!is_null($haveTracking)) {
                $trackingIds[] = $haveTracking[env('TAL_CUSTOMER_ORDER_LINE_TRACKING')];
            }
        }

        if (sizeof($trackingIds) < 1) {
            print("Order({$orderId}) did not have any tracking IDs\n");
            return;
        } else {
            $trackingIds = array_unique($trackingIds);
        }

        $fulfillmentApi = new Fulfillment();
        $shopifySuccess = false;
        $shopifyRes = null;
        $lineItemIds = $customShirts->pluck('id')->transform(function ($id) {
            return ['id' => $id];
        })->all();

        try {
            $data = [
                'parent_id'        => $orderId,
                'tracking_numbers' => $trackingIds,
                'line_items'       => $lineItemIds,
                "status"           => "success",
                'notify_customer'  => true,
            ];
            $res = $fulfillmentApi->create(json_encode($data));
            $res = json_decode($res, true);

            if (isset($res['errors'])) {
                \Log::error($res['errors']);
                print("Order($orderId): an error was returned by the TAL API. See error logs.\n");
            } else {
                $shopifySuccess = true;
                $shopifyRes = $res;
                print("Order($orderId) successfully pushed tracking info\n");
            }
        } catch (ShopifyException $e) {
            print("Order({$orderId}) shopify error occurred: " . $e->getMessage() . "\n");
            \Log::error("Order({$orderId}) shopify error occurred: " . $e->getMessage());
        } catch (\Exception $e) {
            print("Order({$orderId}) unknown error occurred: " . $e->getMessage() . "\n");
            \Log::error("Order({$orderId}) unknown error occurred: " . $e->getMessage());
        }

        if (!$shopifySuccess)
            return;

        # Update tracking info with fulfillment data received back from shopify
        # The fulfillment data will contain tracking info in it
        $this->order->update([
            'tracking_info' => $shopifyRes,
        ]);
    }
}
