<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles, Sortable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
		'first_name', 
		'last_name',
		'email',
		'password',
		'social_type',
		'social_id',
		'created_at',
		'updated_at',
		'status',
		'show_in_front',
		'verified',
	];
	
	public $sortable = [ 
		'id',
		'first_name', 
		'last_name',
		'email',
		'social_type',
		'created_at',
		'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
	];
	
	public $timestamps = true;
	
	//One to one mapping with user_details table
	public function user_detail()
	{
		return $this->hasOne(User_detail::class, 'user_id', 'id');
	}
	
	//One to one mapping with company_details table
	public function company_detail()
	{
		return $this->hasOne(Company_detail::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_locations table
	public function company_location()
	{
		return $this->hasMany(Company_location::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_photos table
	public function company_photo()
	{
		return $this->hasMany(Company_photo::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_services table
	public function company_service()
	{
		return $this->hasMany(Company_service::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_views table
	public function company_view()
	{
		return $this->hasMany(CompanyView::class, 'company_id', 'id');
	}
	
	//One to many mapping with company_tags table
	public function company_tag()
	{
		return $this->hasMany(Company_tag::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_coupons table
	public function company_coupon()
	{
		return $this->hasMany(Company_coupon::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_categories table
	public function company_category()
	{
		return $this->hasMany(CompanyCategory::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_reviews table
	public function company_review()
	{
		return $this->hasMany(Company_review::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_rating table
	public function company_rating()
	{
		return $this->hasMany(Company_review::class, 'company_id', 'id');
	}
	
	//One to many mapping with company_payment_methods table
	public function company_payment_method()
	{
		return $this->hasMany(Company_payment_method::class, 'user_id', 'id');
	}
	
	//One to many mapping with company_active_plans table
	public function company_active_plan()
	{
		return $this->hasOne(Company_active_plan::class, 'user_id', 'id');
	}
}
