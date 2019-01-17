<?php

namespace App\Console\Commands;

use App\Jobs\CreateOrderFulfillment;
use App\Services\TalClient;
use App\shopifyapi;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOrderFulfillments extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-fulfillments
                                {--a|age=12 : Search for orders older than a specific age in hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command checks for orders with missing fulfillments.';

    /**
     * @var array
     */
    private $testOrderIds = [];

    /**
     * CheckOrderFulfillments constructor.
     */
    public function __construct() {
        parent::__construct();

        $ids = env('TAL_CUSTOM_TEST_ORDER_IDS');

        if(!is_null($ids)) {
            $this->testOrderIds = array_map('trim', explode(',', $ids));
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $age = intval($this->option('age'));
        $env = strtolower(app()->environment());

        if (!is_int($age) || $age < 1) {
            $this->error('Age must be an integer greater than 0');

            return false;
        }

        $minAge = Carbon::now()->subhours($age);
        $unfulfilledOrders = shopifyapi::where('processed', 1)
            ->whereNull('tracking_info')
            # Is {created_at} a reliable time stamp for referencing the order creation date?
            #->whereDate('created_at', '>=', $minAge->toDateString())
            ->get();

        # If not on prod then just use test orders
        if(strpos($env, 'prod') === false) {
            $unfulfilledOrders = $unfulfilledOrders->filter(function ($o) {
                $saveText = json_decode($o['savetext'], true);

                return in_array($saveText['id'], $this->testOrderIds);
            });
        }

        $unfulfilledOrders = $unfulfilledOrders->filter(function ($o) use ($minAge) {
                $passes = false;
                $saveText = json_decode($o['savetext'], true);

                # Manually filter out orders older than 12 hours from now
                if (isset($saveText['created_at'])) {
                    # Convert order created_at to UTC time
                    $created = Carbon::createFromFormat(\DateTime::ISO8601, $saveText['created_at'])
                        ->tz('UTC');
                    $passes = $created->lte($minAge);
                }

                # If fails then kick out now
                if (!$passes)
                    return $passes;

                # See if any line items are custom shirts
                if (isset($saveText['line_items'])) {
                    $p = false;

                    foreach ($saveText['line_items'] as $li) {
                        if ($li[env('TAL_PRODCHECK')] == env('TAL_PRODNUM')) {
                            $p = true;
                            break;
                        }
                    }

                    if ($p) {
                        $passes = true;
                    }
                }

                return $passes;
            });

        if ($unfulfilledOrders->count() < 0)
            return true;

        # Fetch a new token each time this command is run.
        # May want to think of a better, persistent mechanism to store tokens as it appears they have 24hr ttl
        $talClient = new TalClient();
        $tokenRes = $talClient->getAccessToken();
        $token = null;

        if ($tokenRes['success'] && isset($tokenRes['data']['access_token'])) {
            $token = $tokenRes['data']['access_token'];
        } else {
            \Log::error(__CLASS__ . ': Could not fetch a token.');
            $this->error('Could not fetch a token.');
            return true;
        }

        if(strpos($env, 'prod') == false) {
            $this->info("Using token: $token");
        }

        # TAL api exposes only a single endpoint for searching customer orders
        # This endpoint only accepts a single query param: {customerOrderNo}
        # For now we can't really do batch pulls/pushes with TAL
        foreach ($unfulfilledOrders as $o) {
            CreateOrderFulfillment::dispatch($o, $token);
        }

        return true;
    }
}
