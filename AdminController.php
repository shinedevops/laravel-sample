<?php

namespace App\Http\Controllers\Common;

use App\Helpers\Util;
use App\Http\Controllers\Controller;
use App\Http\Traits\CometTrait;
use App\Http\Traits\PaymentTrait;
use Illuminate\Http\Request;
use App\Models\{BackupPlanUser, CometDevice, CometServer, CometUser, Invoice, Payment, Payout, ServerBackupPlan, ServerPlanUser, SubscriptionPlan, User};
use Illuminate\Support\Facades\{DB, Redis, Validator};

class AdminController extends Controller
{
    use CometTrait, PaymentTrait;

    public function dashboard(Request $request)
    {
        $userData = [];
        $authUser = auth()->user();
        $authId = $authUser->id;
        $totalActiveOrders = $this->getActiveOrders($request);
        $totalBilling = $this->getTotalBilling($request);
        $totalUsers = $this->getTotalUsers($request);
        $totalDevices = $this->getTotalDevice();
        $commission = $this->getCommission($request);
        $payout = $this->getPayouts($request);
        $activeServerSubscriptionCount = $this->activeServerSubscriptionCount($request);
        $activeBackupPlans = $activeServerSubscriptionCount['active_backup_plans_count'];
        $activeServerPlans = $activeServerSubscriptionCount['active_server_plans_count'];
        $activeSubscriptionPlans = $activeServerSubscriptionCount['active_subscription_plans_count'];
        $userBarGraphData = $this->getUserBarGraph($request);
        $billingLineGraph = $this->getBillingLineGraph($request);

        $data = [
            'active_order' => $totalActiveOrders,
            'billing' => $totalBilling,
            'users' => $totalUsers,
            'devices' => $totalDevices,
            'plans_sold_out' => $totalActiveOrders,
            'active_backup_plans' => $activeBackupPlans,
            'active_server_plans' => $activeServerPlans,
            'active_subscription_plans' => $activeSubscriptionPlans,
            'payout' => $payout,
            'commission' => $commission,
        ];

        return view('common.admin.dashboard', compact('data', 'userBarGraphData', 'billingLineGraph'));
    }

    public function testStripeAccount(Request $request)
    {
        $validateUser = Validator::make($request->all(), [
            'public_key' => 'required',
            'secret_key' => 'required',
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'error' => $validateUser->errors()
            ], 401);
        }
        try {
            $this->checkStripeDetails($request);

            return response()->json([
                'status' => 'success',
                'message' => 'Connected successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get users bucket storage info
     *
     * @return float
     */
    public function bucketsStorage()
    {
        try {
            $authId = auth()->user()->id;
            $totalStorage = Redis::get("dashboard_total_storage_{$authId}") ?? $this->getBucketsStorage();

            return response()->json([
                'status' => true,
                'message' => 'Bucket storage info retrieved successfully',
                'data' => [
                    'storage' => $totalStorage
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }

    }

    /**
     * Get all the servers of logged in user
     * Get all the buckets of each server
     * Sum up the storages
     *
     * @return float
     */
    private function getBucketsStorage()
    {
        $authUser = auth()->user();
        $authId = $authUser->id;
        $servers = CometServer::myServers()->get();
        $totalStorage = 0;
        foreach ($servers as $server) {
            $buckets = $this->getAllBuckets($server);
            if (!is_null($buckets)) {
                $totalStorage += array_sum(array_column(array_column($buckets, 'Size'), 'Size'));
            }
        }
        $totalStorage = Util::bytesToGb($totalStorage);
        Redis::set("dashboard_total_storage_{$authId}", $totalStorage, 'EX', 3600);

        return $totalStorage;
    }

    /**
     * Get all the servers of logged in user
     * Get all the buckets of each server
     * Sum up the storages
     *
     * @return float
     */
    private function getTotalDevice()
    {
        $authUser = auth()->user();
        $authId = $authUser->id;
        $serverIds = CometServer::myServers()->pluck('id')->toArray();
        $cometUserIds = CometUser::whereIn('comet_server_id', $serverIds)->pluck('id')->toArray();
        $totalDevices = CometDevice::whereIn('comet_user_id', $cometUserIds)->count();
        Redis::set("dashboard_total_devices_{$authId}", $totalDevices, 'EX', 600);

        return $totalDevices;
    }

    /**
     * Get the user ids
     * Get the subscription payment if logged in user is admin
     * Get the sum of invoices amount
     *
     * @return float
     */
    private function getTotalBilling(Request $request)
    {
        $authUser = auth()->user();
        $subscriptionsAmount = 0;
        $userIds = $authUser->children->where('role_id', config('constants.roles.customer_id'))->pluck('id')->toArray();
        if (config('constants.roles.admin_id') == $authUser->role_id) {
            $subscriptionsAmount = Payment::where('status', 'active')
            ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
                'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
            })
            ->when($request->has('range'), function ($q) use($request) {
                $range = explode(config('constants.date_format.date_separator'), $request->range);
                if (2 == count($range)) {
                    $q->whereDate('created_at', '>=', trim($range[0]));
                    $q->whereDate('created_at', '<=', trim($range[1]));
                }
            })
            ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
                $q->whereYear('created_at', date('Y'));
            })
            ->sum('amount');
            $resellerIds = $authUser->children->where('role_id', config('constants.roles.reseller_id'))->pluck('id')->toArray();
            $userIds = array_merge($userIds, $resellerIds);
        }
        $invoicesAmount = Invoice::whereIn('user_id', $userIds)
        ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
            'month' == $request->filter ? $q->whereMonth('date_paid', date('m')) : $q->whereYear('date_paid', date('Y'));
        })
        ->when($request->has('range'), function ($q) use($request) {
            $range = explode(config('constants.date_format.date_separator'), $request->range);
            if (2 == count($range)) {
                $q->whereDate('date_paid', '>=', trim($range[0]));
                $q->whereDate('date_paid', '<=', trim($range[1]));
            }
        })
        ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
            $q->whereYear('date_paid', date('Y'));
        })
        ->where('status', 'PAID')
        ->sum('amount');
        $total = Util::formatNumber($subscriptionsAmount + $invoicesAmount);

        return $total;
    }

    /**
     * Get the server plan users if logged in user is admin
     * Get the backup plan users
     *
     * @return int
     */
    private function getActiveOrders(Request $request)
    {
        $serverPlanOrders = 0;
        $authUser = auth()->user();
        if (config('constants.roles.admin_id') == $authUser->role_id) {
            $resellerIds = $authUser->children->where('role_id', config('constants.roles.reseller_id'))->pluck('id')->toArray();
            $serverPlanOrders = ServerPlanUser::whereIn('user_id', $resellerIds)
            ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
                'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
            })
            ->when($request->has('range'), function ($q) use($request) {
                $range = explode(config('constants.date_format.date_separator'), $request->range);
                if (2 == count($range)) {
                    $q->whereDate('created_at', '>=', trim($range[0]));
                    $q->whereDate('created_at', '<=', trim($range[1]));
                }
            })
            ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
                $q->whereYear('created_at', date('Y'));
            })
            ->active()
            ->count();
        }

        $userIds = $authUser->children->where('role_id', config('constants.roles.customer_id'))->pluck('id')->toArray();
        $backupPlanOrders = BackupPlanUser::whereIn('user_id', $userIds)
        ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
            'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
        })
        ->when($request->has('range'), function ($q) use($request) {
            $range = explode(config('constants.date_format.date_separator'), $request->range);
            if (2 == count($range)) {
                $q->whereDate('created_at', '>=', trim($range[0]));
                $q->whereDate('created_at', '<=', trim($range[1]));
            }
        })
        ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
            $q->whereYear('created_at', date('Y'));
        })
        ->active()
        ->count();
        $totalActiveOrders = $serverPlanOrders + $backupPlanOrders;

        return $totalActiveOrders;
    }

    /**
     * Get the total users
     */
    private function getTotalUsers($request)
    {
        $authUser = auth()->user();
        $totalUsers = $authUser->children()
        ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
            'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
        })
        ->when($request->has('range'), function ($q) use($request) {
            $range = explode(config('constants.date_format.date_separator'), $request->range);
            if (2 == count($range)) {
                $q->whereDate('created_at', '>=', trim($range[0]));
                $q->whereDate('created_at', '<=', trim($range[1]));
            }
        })
        ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
            $q->whereYear('created_at', date('Y'));
        })
        ->count();

        return $totalUsers;
    }

    /**
     * Get the active server and subscription plans
     *
     * @return array
     */
    private function activeServerSubscriptionCount(Request $request)
    {
        $activeServerPlansCount = $activeSubscriptionPlansCount = 0;
        if (config('constants.roles.admin_id') == auth()->user()->role_id) {
            // active server plans
            $activeServerPlansCount = ServerBackupPlan::myPlans()
            ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
                'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
            })
            ->when($request->has('range'), function ($q) use($request) {
                $range = explode(config('constants.date_format.date_separator'), $request->range);
                if (2 == count($range)) {
                    $q->whereDate('created_at', '>=', trim($range[0]));
                    $q->whereDate('created_at', '<=', trim($range[1]));
                }
            })
            ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
                $q->whereYear('created_at', date('Y'));
            })
            ->servers()
            ->active()
            ->count();

            // active subscription plans
            $activeSubscriptionPlansCount = SubscriptionPlan::active()
            ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
                'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
            })
            ->when($request->has('range'), function ($q) use($request) {
                $range = explode(config('constants.date_format.date_separator'), $request->range);
                if (2 == count($range)) {
                    $q->whereDate('created_at', '>=', trim($range[0]));
                    $q->whereDate('created_at', '<=', trim($range[1]));
                }
            })
            ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
                $q->whereYear('created_at', date('Y'));
            })
            ->count();
        }

        $activeBackupPlansCount = ServerBackupPlan::myPlans()
        ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
            'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
        })
        ->when($request->has('range'), function ($q) use($request) {
            $range = explode(config('constants.date_format.date_separator'), $request->range);
            if (2 == count($range)) {
                $q->whereDate('created_at', '>=', trim($range[0]));
                $q->whereDate('created_at', '<=', trim($range[1]));
            }
        })
        ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
            $q->whereYear('created_at', date('Y'));
        })
        ->backups()
        ->active()
        ->count();

        return [
            'active_backup_plans_count' => $activeBackupPlansCount,
            'active_server_plans_count' => $activeServerPlansCount,
            'active_subscription_plans_count' => $activeSubscriptionPlansCount,
        ];
    }

    /**
     * Get the admin commission
     */
    private function getCommission(Request $request)
    {
        $commission = 0;
        $authUser = auth()->user();
        if (config('constants.roles.admin_id') == $authUser->role_id) {
            $commission = Payout::where('status', 'PAID')
            ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
                'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
            })
            ->when($request->has('range'), function ($q) use($request) {
                $range = explode(config('constants.date_format.date_separator'), $request->range);
                if (2 == count($range)) {
                    $q->whereDate('created_at', '>=', trim($range[0]));
                    $q->whereDate('created_at', '<=', trim($range[1]));
                }
            })
            ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
                $q->whereYear('created_at', date('Y'));
            })
            ->sum('commission_amount');
        }

        return Util::formatNumber($commission);
    }

    /**
     * Get the payouts
     */
    private function getPayouts(Request $request)
    {
        $authUser = auth()->user();
        $authId = $authUser->id;
        $payout = 0;
        if (config('constants.roles.admin_id') <> $authUser->role_id) {
            if (!is_null($authUser->paymentDetail) && 'BANK' == $authUser->paymentDetail->transfer_to) {
                $payout = Payout::where([
                    ['user_id', $authId],
                    ['status', 'PAID'],
                ])
                ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
                    'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
                })
                ->when($request->has('range'), function ($q) use($request) {
                    $range = explode(config('constants.date_format.date_separator'), $request->range);
                    if (2 == count($range)) {
                        $q->whereDate('created_at', '>=', trim($range[0]));
                        $q->whereDate('created_at', '<=', trim($range[1]));
                    }
                })
                ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
                    $q->whereYear('created_at', date('Y'));
                })
                ->sum('paid_amount');

                return Util::formatNumber($payout);
            }
        }

        return $payout;
    }


    /**
     * Get the users bar graph
     *
     * @return array
     */
    private function getUserBarGraph(Request $request)
    {
        $barGraphData = [];
        $userIds = auth()->user()->children->pluck('id')->toArray();
        $users = User::select(DB::raw('MONTH(created_at) as month'), DB::raw('count(id) as total_user'))
        ->whereIn('id', $userIds)
        ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
            'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
        })
        ->when($request->has('range'), function ($q) use($request) {
            $range = explode(config('constants.date_format.date_separator'), $request->range);
            if (2 == count($range)) {
                $q->whereDate('created_at', '>=', trim($range[0]));
                $q->whereDate('created_at', '<=', trim($range[1]));
            }
        })
        ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
            $q->whereYear('created_at', date('Y'));
        })
        ->groupBy('month')->get()
        ->keyBy('month')->toArray();

        for ($i = 1; $i <= 12; $i++) {
            if(in_array($i, array_keys($users))) {
                array_push($barGraphData, floatval($users[$i]['total_user']));
            } else {
                array_push($barGraphData, 0);
            }
        }

        return $barGraphData;
    }

    /**
     * Get the billing line graph
     *
     * @return array
     */
    private function getBillingLineGraph(Request $request)
    {
        $lineGraphData = [];
        $userIds = auth()->user()->children->where('role_id', config('constants.roles.customer_id'))->pluck('id')->toArray();
        $billings = Invoice::select(DB::raw('MONTH(date_paid) as month'), DB::raw('sum(amount) as total_amount'))
        ->whereIn('user_id', $userIds)
        ->when($request->has('filter') && !empty($request->filter), function($q) use($request) {
            'month' == $request->filter ? $q->whereMonth('created_at', date('m')) : $q->whereYear('created_at', date('Y'));
        })
        ->when($request->has('range'), function ($q) use($request) {
            $range = explode(config('constants.date_format.date_separator'), $request->range);
            if (2 == count($range)) {
                $q->whereDate('created_at', '>=', trim($range[0]));
                $q->whereDate('created_at', '<=', trim($range[1]));
            }
        })
        ->when(!$request->has('filter') && !$request->has('range'), function ($q) {
            $q->whereYear('created_at', date('Y'));
        })
        ->where('status', 'PAID')
        ->groupBy('month')->get()
        ->keyBy('month')->toArray();

        for ($i = 1; $i <= 12; $i++) {
            if(in_array($i, array_keys($billings))) {
                array_push($lineGraphData, floatval($billings[$i]['total_amount']));
            } else {
                array_push($lineGraphData, 0);
            }
        }

        return $lineGraphData;
    }
}
