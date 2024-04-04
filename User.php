<?php

namespace App\Models;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Request;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Concerns\Prorates;

use function Illuminate\Events\queueable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Billable, Prorates;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'email_verified_at',
        'role_id',
        'parent_id',
        'comet_user_id',
        'subdomain',
        'status',
        'stripe_id',
        'remember_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The relationship that should be retrieved.
     *
     * @var array<string, string>
     */
    protected $with = ['role', 'detail'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(queueable(function ($customer) {
            if ($customer->hasStripeId()) {
                $customer->syncStripeCustomerDetails();
            }
        }));
    }

    /**
     * Interact with the user's first name.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trim(ucfirst($value)),
            set: fn ($value) => trim(explode(" ", $value)[0]),
        );
    }

    /**
     * Interact with the user's first name.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trim(ucfirst($value)),
            set: function ($value) {
                $fullName = explode(" ", $value);
                array_shift($fullName);

                return count($fullName) ? trim(implode(" ", $fullName)) : null;
            }
        );
    }

    /**
     * Interact with the user's first name and last name.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function getFullNameAttribute()
    {
        $firstName = ucfirst($this->first_name);
        $lastName = ucfirst($this->last_name);

        return trim("{$firstName} {$lastName}");
    }

    /**
     * Send email for account verification
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Retrieve the parent user on the behalf of the domain name
     */
    public function scopeParentUser($query, $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * Retrieve the parent user on the behalf of the domain name
     */
    public function parent()
    {
        return self::where('id', $this->parent_id)->first();
    }

    /**
     * Retrieve the tenant
     */
    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * The users that belong to the user.
     *
     * @return  \Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Retrieve users cards
     *
     * @return  \Illuminate\Database\Eloquent\Collection
     */
    public function cards()
    {
        return $this->hasMany(UserCard::class);
    }

    /**
     * Reteieve the user role
     *
     * @return  \Illuminate\Database\Eloquent\Collection
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the subscriptions
     *
     * @return  \Illuminate\Database\Eloquent\Collection
     */
    public function subscriptions()
    {
        return $this->hasOne(Subscription::class, 'user_id');
    }


    /**
     * Reteieve the user detail
     *
     * @return  \Illuminate\Database\Eloquent\Collection
     */
    public function detail()
    {
        return $this->hasOne(UserDetail::class);
    }

    /**
     * Reteieve the user payment detai
     */
    public function paymentDetail()
    {
        return $this->hasOne(UserPaymentSetting::class, 'user_id', 'id');
    }

    /**
     * Reteieve the user plan buy
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the customer name that should be synced to Stripe.
     *
     * @return string|null
     */
    public function stripeName()
    {
        $firstName = ucfirst($this->first_name);
        $lastName = ucfirst($this->last_name);

        return "{$firstName} {$lastName}";
    }

    /**
     * Get the customer address that should be synced to Stripe.
     *
     * @return string|null
     */
    public function stripeAddress()
    {
        if (!is_null($this->detail)) {
            return [
                'city' => $this->detail->city ?? '',
                'country' => $this->detail->country->name ?? '',
                'line1' => $this->detail->address,
                'line2' => '',
                'postal_code' => $this->detail->postal_code ?? '',
                'state' => $this->detail->state->name ?? '',
            ];
        }
    }

    public function isSubscribed()
    {
        return $this->subscriptions()->active()->count() ? true : false;
    }

    public function subscriptionPlan()
    {
        if ($this->isSubscribed()) {
            $item = $this->subscriptions->items->first();

            return SubscriptionPlan::where('stripe_id', $item->stripe_product)->first();
        }

        return null;
    }

    public function userSubscriptionPlan()
    {
        $subscriptionPlans = SubscriptionPlan::get();
        $subscriptionPrices = $subscriptionPlans->pluck('stripe_price')->toArray();

        $items = $this->subscriptions()->active()->get();
        foreach ($items as $code) {
            if (in_array($code->stripe_price, $subscriptionPrices)) {
                $subscriptionPlan = $subscriptionPlans->where('stripe_price', $code->stripe_price)->first();
                $expiryDate = Order::where('subscription_id', $code->id)->orderByDesc('id')->pluck('expiry_date')->first();
                // $subscriptionPlan->append();
                return (object)(array_merge($subscriptionPlan->toArray(), ['expiry_date' => $expiryDate]));
            }
        }

        return Collection::make();
    }

    public function getSubscription($name, $status = 'active')
    {
        return Subscription::where('name', $name)->where('user_id', $this->id)->where('stripe_status', $status)->first();
    }

    public function stripeSubscription($stripeId)
    {
        return $this->stripe()->subscriptions->retrieve($stripeId, []);
    }

    /**
     * Check is backup customer exist
     *
     * @return boolean
     */
    public function hasBackupPlanCustomer()
    {
        $userIds = $this->children()->pluck('id')->toArray();

        return BackupPlanUser::whereIn('user_id', $userIds)->count() ? true : false;
    }

    /**
     * Check is backup plan purchased
     *
     * @return boolean
     */
    public function hasBackupPlan()
    {
        return BackupPlanUser::where('user_id', $this->id)->count() ? true : false;
    }

    /**
     * Get the customer address that should be synced to Stripe.
     *
     * @return string|null
     */
    public function getCashier()
    {
        if (config('constants.reseller') == $this->role->slug && in_array(Request::route()->getName(), ['admin.server.plans.buy', 'admin.server.plans.success'])) {
            return Cashier::stripe();
        } elseif (config('constants.reseller') == $this->role->slug && !is_null($this->paymentDetail) && !is_null($this->paymentDetail->transfer_to) && 'STRIPE' == $this->paymentDetail->transfer_to) {
            config(['cashier.key' => $this->paymentDetail->public_key['original']]);
            config(['cashier.secret' => $this->paymentDetail->secret_key['original']]);
        } elseif (config('constants.user') == $this->role->slug) {
            $userId = $this->parent_id;
            $parentUser = self::find($userId);
            if (!is_null($parentUser->paymentDetail) && !is_null($parentUser->paymentDetail->transfer_to) && 'STRIPE' == $parentUser->paymentDetail->transfer_to) {
                config(['cashier.key' => $parentUser->paymentDetail->public_key['original']]);
                config(['cashier.secret' => $parentUser->paymentDetail->secret_key['original']]);
            }
        }

        return Cashier::stripe();
    }

    public function getInvoice($invoiceId)
    {
        return $this->stripe()->invoices->retrieve($invoiceId, []);
    }

    /**
     * Get the admin companies/resellers
     *
     * @return \App\Models\Company
     */
    public function company()
    {
        return $this->hasMany(Company::class);
    }
}
