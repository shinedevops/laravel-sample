<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\CountryTrait;
use App\Traits\CompanyTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Image;
use App\User;
use Auth;
use Session;
use App\Country;
use App\Company_photo;
use App\Company_detail;
use App\Company_location;
use App\Company_service;
use App\Company_tag;
use App\CompanyCategory;
use App\Membership_plan;
use App\Company_coupon;
use App\Company_payment_method;

class CompanyController extends Controller
{
    use CompanyTrait;
    public function __construct(){
        $this->middleware('auth');
        //Update membership plans details in session for loggeding company
        $this->middleware(function ($request, $next) {            
            $membership = User::with(['company_active_plan.membership_plan'])->where('id', Auth::user()->id)->first();
            $otherData = unserialize($membership->company_active_plan->membership_plan->other_data);
            Session::put('membership_plan_other_data', $otherData);
            return $next($request);
        });
    }
    
    /*
    Method Name:    index
    Purpose:        To display company dashboard with company details and logo
    Params:         {}
    */
    public function index(){
        $companies = User::with(['company_detail', 'company_photo' => function ($query) {
            $query->where('photo_type', 'Logo');
        }])->where('id', Auth::user()->id)->first();
        return view('company.dashboard', compact('companies'));
    }
    /* End Method index */

    /*
    Method Name:    profileUpdate
    Purpose:        To update company profile details by ajax request
    Params:          {first_name, last_name, email}
    */
    public function profileUpdate(Request $request) {
        $validator = Validator::make($request->all(),
        [
            'first_name' => 'required|string',  
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.Auth::user()->id,
        ]);
        if($validator->fails()){
            return response()->json(array(
            'success' => false,
            'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }
        try
        {
            $users = User::findOrFail(Auth::user()->id);
            $postData = $request->all();
            $users->first_name = $postData['first_name'];
            $users->last_name = $postData['last_name'];
            $users->email = $postData['email'];
            $users->push();       
            return response()->json(['success' => true, 'message' => 'Company details '.Config::get('constants.SUCCESS.UPDATE_DONE')]);
        } catch ( \Exception $e ) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    /* End Method profileUpdate */

    /*
    Method Name:    detailUpdate
    Purpose:        To update company details by ajax request
    Params:          {company_name, company_slug, about, website_url}
    */
    public function detailUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'company_name' => 'required|string|unique:company_details,company_name,'.Auth::user()->id.',user_id',  
            'company_slug' => 'required|string|unique:company_details,slug,'.Auth::user()->id.',user_id',
            'about' => 'required|string',
            'website_url' => 'required'
        ]);
        if($validator->fails()){
            return response()->json(array(
            'success' => false,
            'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }
        try
        {
            $slug = Str::slug($request->company_slug);
            if($request->hasFile('company_logo')) {
                $allowedfileExtension=['jpg','png'];
                $file = $request->file('company_logo');
                $extension = $file->getClientOriginalExtension();
                $check=in_array($extension,$allowedfileExtension);
                if($check) {
                    
                    $encrypt_companyId = base64_encode(Auth::user()->id);
                    $companypath = public_path().'/assets/media/' . $encrypt_companyId;
                    $logopath = $companypath.'/logo';
                    $logoImage = time().'.'.$extension;
                    $file->move($logopath, $logoImage); 
                    $company_logo = Company_photo::where('user_id' , Auth::user()->id)->where('photo_type' , 'Logo')->first();
                    if($company_logo == null) {
                        $logodata =[
                            'user_id' => Auth::user()->id,
                            'photo'=>$logoImage,
                            'imagetype' => $extension,
                            'photo_type' => 'Logo',
                            'status' => 1
                        ];
                        Company_photo::create($logodata); 
                    } else {
                        $image_path = $logopath.'/'.$company_logo->photo;  // Value is not URL but directory file path
                        if(File::exists($image_path)) {
                            File::delete($image_path);
                        }
                        $company_logo->update(['photo'=>$logoImage, 'imagetype' => $extension]);
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'image '.Config::get('constants.ERROR.IMAGE_TYPE')]);
                }
                $image = $logoImage;
            }
            else{
                $image = 'false';
            }
            $users = User::findOrFail(Auth::user()->id);
            $postData = $request->all();
            $users->company_detail->company_name = $postData['company_name'];
            $users->company_detail->website_url = $postData['website_url'];
            $users->company_detail->about = $postData['about'];
            $users->company_detail->slug = $slug;
            $users->push();
            $this->updateProfile(Auth::user()->id); 
            return response()->json(['success' => true, 'image' => $image, 'message' => 'Company details '.Config::get('constants.SUCCESS.UPDATE_DONE')]);
        } catch ( \Exception $e ) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    /* End Method detailUpdate */

    /*
    Method Name:    contactUpdate
    Purpose:        To update company contact details by ajax request
    Params:          {contact_person_mobile, contact_person_email, toll_free_number, customer_care_number}
    */
    public function contactUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'contact_person_mobile' => 'required|digits:10|unique:company_details,contact_person_mobile,'.Auth::user()->id.',user_id',
            'contact_person_email' => 'required|email|unique:company_details,contact_person_email,'.Auth::user()->id.',user_id',
        ]);
        if($validator->fails()){
            return response()->json(array(
            'success' => false,
            'errors' => $validator->getMessageBag()->toArray()
            ), 400);
        }
        try
        {
            $users = User::findOrFail(Auth::user()->id);
            $postData = $request->all();
            $users->company_detail->contact_person_mobile = $postData['contact_person_mobile'];
            $users->company_detail->contact_person_email = $postData['contact_person_email'];
            $users->company_detail->toll_free_number = $postData['toll_free_number'];
            $users->company_detail->customer_care_number = $postData['customer_care_number'];
            $users->push();       
            return response()->json(['success' => true, 'message' => 'Company contact details '.Config::get('constants.SUCCESS.UPDATE_DONE')]);
        } catch ( \Exception $e ) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    /* End Method contactUpdate */

    /*
    Method Name:    sociallinkUpdate
    Purpose:        To update company social links by ajax request
    Params:          {google_plus_link, facebook_link, instagram_link, twitter_link}
    */
    public function sociallinkUpdate(Request $request) {
        try{
            $users = User::findOrFail(Auth::user()->id);
            $postData = $request->all();
            $users->company_detail->google_plus_link = $postData['google_plus_link'];
            $users->company_detail->facebook_link = $postData['facebook_link'];
            $users->company_detail->twitter_link = $postData['twitter_link'];
            $users->company_detail->instagram_link = $postData['instagram_link'];
            $users->push();       
            return response()->json(['success' => true, 'message' => 'Company social links '.Config::get('constants.SUCCESS.UPDATE_DONE')]);
        } catch ( \Exception $e ) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    /* End Method sociallinkUpdate */

    /*
    Method Name:    passwordUpdate
    Purpose:        To update company password
    Params:          {old_password, password, instagram_link, password_confirmation}
    */
    public function passwordUpdate(Request $request) {
        if ($request->isMethod('get')) {
            $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                $query->where('photo_type', 'Logo');
            }])
            ->where('id', Auth::user()->id)->first();
            return view('company.password', compact('companies'));
        }
        else{
            $validatedData = $request->validate([
                'old_password' => 'required',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|same:password'
            ]);
            if (!(Hash::check($request->get('old_password'), Auth::user()->password))) {
                return redirect()->back()->with('status', 'error')->with('message', Config::get('constants.ERROR.PASSWORD_MISMATCH'));
            }
            if(strcmp($request->get('old_password'), $request->get('password')) == 0){
                return redirect()->back()->with('status', 'error')->with('message', Config::get('constants.ERROR.PASSWORD_SAME'));
            }
            //Change Password
            $user = Auth::user();
            $user->password = Hash::make($request->get('password'));
            $user->save();
            return redirect()->back()->with('status', 'success')->with('message', ' Password '.Config::get('constants.SUCCESS.UPDATE_DONE'));
        }
    }
    /* End Method passwordUpdate */

    /*
    Method Name:    locationDelete
    Purpose:        To delete company location by id
    Params:          {encrypted locationId}
    */
    public function locationDelete($id){
        $id = decrypt_userdata($id);
        try {
            $coupon = Company_location::where('id',$id)->where('user_id', Auth::user()->id)->delete();
            $this->updateProfile(Auth::user()->id); 
            return redirect()->back()->with(['status' => 'success', 'message' => 'Location '.Config::get('constants.SUCCESS.DELETE_DONE')]); ;
        } catch(Exception $ex){
            return redirect()->back()->with('status', 'error')->with('message', $e->getMessage());
        }
    }
    /* End Method locationDelete */

    /*
    Method Name:    locationUpdate
    Purpose:        To display added location, add new location and update exist one
    Params:          {address, city, zipcode, country, state, encrypted locationId(optional)}
    */
    public function locationUpdate(Request $request, $id = NULL) {
        //If Id exist update location accordingly  otherwise display all locations
        if($id){
            $id = decrypt_userdata($id);
            if ($request->isMethod('get')) {
                $records = Company_location::where('id', $id)->where('user_id', Auth::user()->id)->first();
                if(is_null($records))
                abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));   
                $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                    $query->where('photo_type', 'Logo');
                }, 'company_location' => function ($query) {
                    $query->with('state', 'country');
                }])->where('id', Auth::user()->id)->first();
                return view('company.locations', compact('companies', 'records'));
            }
            else{
                
                $services = Company_location::where('id', '!=', $id)->where('city', $request->city)->where('zipcode', $request->zipcode)->where('address', $request->address)->where('user_id', Auth::user()->id)->first();
                if(!is_null($services))
                return redirect()->back()->with('status', 'error')->with('message', 'Duplicate entry for location not allowed');
                $validatedData = $request->validate([
                    'address' => 'required|string',
                    'city' => 'required|string',
                    'zipcode' => 'required|numeric|min:4|max:5',
                    'country' => 'required|numeric',
                    'state' => 'required|numeric',
                ]);
                $service = Company_location::where('id', $id)->where('user_id', Auth::user()->id)->first();
                if(is_null($service))
                abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));
                $service->address = $request->address;
                $service->city = $request->city;
                $service->zipcode = $request->zipcode;
                $service->state_id = $request->state;
                $service->country_id = $request->country;
                $service->save();        
                $this->updateProfile(Auth::user()->id); 
                return redirect()->route('company.locations')->with('status', 'success')->with('message', ' Company location '.Config::get('constants.SUCCESS.UPDATE_DONE'));
            }
        }
        else{
            if ($request->isMethod('get')) {
                $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                    $query->where('photo_type', 'Logo');
                }, 'company_location' => function ($query) {
                    $query->with('state', 'country');
                }])->where('id', Auth::user()->id)->first();
                return view('company.locations', compact('companies'));
            }
            else{
                $validatedData = $request->validate([
                    'address' => 'required|string',
                    'city' => 'required|string',
                    'zipcode' => 'required|numeric|min:4|max:5',
                    'country' => 'required|numeric',
                    'state' => 'required|numeric',
                ]);
                $location = Company_location::where('city', $request->city)->where('zipcode', $request->zipcode)->where('address', $request->address)->where('user_id', Auth::user()->id)->first();
                if(!is_null($location))
                return redirect()->back()->with('status', 'error')->with('message', 'Duplicate entry for address not allowed');
                Company_location::create([
                    'user_id' => Auth::user()->id,
                    'address'   =>  $request->address,
                    'city'   =>  $request->city,
                    'state_id'   =>  $request->state,
                    'country_id'   =>  $request->country,
                    'zipcode'   =>  $request->zipcode,
                    'status' => 1
                ]);        
                $this->updateProfile(Auth::user()->id); 
                return redirect()->route('company.locations')->with('status', 'success')->with('message', ' Company Location '.Config::get('constants.SUCCESS.CREATE_DONE'));
            }
        }
    }
    /* End Method locationUpdate */

    /*
    Method Name:    awardbannerCreate
    Purpose:        To display added added awards and banner and also add new award banner
    Params:          {photo_type, awardbanner}
    */
    public function awardbannerCreate(Request $request) {
        if ($request->isMethod('get')) {
            $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                $query->where('photo_type', 'Logo');
            }])->where('id', Auth::user()->id)->first();
            $awards = Company_photo::where('photo_type', 'Award')->where('user_id', Auth::user()->id)->get();
            $banners = Company_photo::where('photo_type', 'Banner')->where('user_id', Auth::user()->id)->get();
            return view('company.awards-banners', compact('awards', 'banners', 'companies'));
        }
        else{
            $request->validate([
                'photo_type' => 'required|in:Award,Banner',
            ]);
            try{

                if($request->hasFile('awardbanner')){
                    $file = $request->file('awardbanner');
                    $allowedfileExtension=['jpg', 'jpeg','png'];
                    $extension = $file->getClientOriginalExtension();
                    $check=in_array($extension,$allowedfileExtension);
                    if($check) {
                        $encrypt_companyId = base64_encode(Auth::user()->id);
                        $companypath = public_path().'/assets/media/' . $encrypt_companyId;
                        $logopath = $companypath.'/'.$request->photo_type;
                        $logoImage = time().'.'.$extension;
                        $file->move($logopath, $logoImage); 
                        $logodata =[
                            'user_id' => Auth::user()->id,
                            'photo'=>$logoImage,
                            'imagetype' => $extension,
                            'photo_type' => $request->photo_type,
                            'status' => 1
                        ];
                        $awardCount = Company_photo::where('photo_type', 'Award')->where('user_id', Auth::user()->id)->count();
                        if($request->photo_type == 'Award' && $awardCount >= Session::get('membership_plan_other_data')['Awards'])
                        return redirect()->back()->with('status', 'error')->with('message', 'Maximum '.Session::get('membership_plan_other_data')['Awards'].' award(s) allowed' );
                        Company_photo::create($logodata);
                    } else {
                        return redirect()->back()->with(['status' => 'error', 'message' => $request->photo_type.Config::get('constants.ERROR.IMAGE_TYPE')]);
                    }
                }
                return redirect()->back()->with('status', 'success')->with('message', $request->photo_type.' '.Config::get('constants.SUCCESS.CREATE_DONE'));
            } catch ( \Exception $e ) {
                return redirect()->back()->with('status', 'error')->with('message', $e->getMessage());
            }
        }
    }
    /* End Method awardbannerCreate */

    /*
    Method Name:    awardbannerDelete
    Purpose:        To delete company award or banner by id
    Params:          {encrypted Id}
    */
    public function awardbannerDelete($id){
        $id = decrypt_userdata($id);
        try {
            $image = Company_photo::where('id',$id)->where('user_id', Auth::user()->id)->first();
            $encrypt_companyId = base64_encode(Auth::user()->id);
            $companypath = public_path().'/assets/media/' . $encrypt_companyId;
            $logopath = $companypath.'/'.$image->photo_type;
            $image_path = $logopath.'/'.$image->photo;  // Value is not URL but directory file path
            if(File::exists($image_path)) {
                File::delete($image_path);
                $image->delete();
            }
            return redirect()->back()->with(['status' => 'success', 'message' => $image->photo_type.' '.Config::get('constants.SUCCESS.DELETE_DONE')]); ;
        } catch(Exception $ex){
            return redirect()->back()->with('status', 'error')->with('message', $e->getMessage());
        }
    }
    /* End Method awardbannerCreate */

    /*
    Method Name:    couponCreate
    Purpose:        To add new company coupon
    Params:         {coupon_name, coupon_code, detail, url_to_buy, expiry_date}
    */
    public function couponCreate(Request $request) {
        if((Session::has('membership_plan_other_data')) && (Session::get('membership_plan_other_data')['Coupons'] == 0))
            abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));
        if ($request->isMethod('get')) {
            $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                $query->where('photo_type', 'Logo');
            }])->where('id', Auth::user()->id)->first();
            $coupons = Company_coupon::where('user_id', Auth::user()->id)->get();
            return view('company.coupons', compact('coupons', 'companies'));
        }
        else{
            $request->validate([
                'coupon_name' => 'required|string',
                'coupon_code' => 'required|string',
                'detail' => 'required|string',
                'url_to_buy' => 'required|url',
                'expiry_date' => 'required',
            ]);
            try{
                Company_coupon::create([
                    'user_id' => Auth::user()->id,
                    'name' => $request->coupon_name,
                    'code' => $request->coupon_code,
                    'detail' => $request->detail,
                    'url_to_buy' => $request->url_to_buy,
                    'expiry_date' => $request->expiry_date,
                    'status' => 1
                ]);
                return redirect()->back()->with('status', 'success')->with('message', 'Coupon '.Config::get('constants.SUCCESS.CREATE_DONE'));
            } catch ( \Exception $e ) {
                return redirect()->back()->with('status', 'error')->with('message', $e->getMessage());
            }
        }
    }
    /* End Method couponCreate */

    /*
    Method Name:    couponUpdate
    Purpose:        To update company coupon by id
    Params:          {coupon_name, coupon_code, detail, url_to_buy, expiry_date, encrypted Id}
    */
    public function couponUpdate(Request $request, $id) {
        $id = decrypt_userdata($id);
        if((Session::has('membership_plan_other_data')) && (Session::get('membership_plan_other_data')['Coupons'] == 0))
            abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));
        if ($request->isMethod('get')) {
            $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                $query->where('photo_type', 'Logo');
            }])->where('id', Auth::user()->id)->first();
            $coupons = Company_coupon::where('user_id', Auth::user()->id)->where('id', $id)->first();
            if(!$coupons)
            return redirect()->route('company.coupons')->with('status', 'error')->with('message', Config::get('constants.ERROR.OOPS_ERROR'));
            return view('company.coupon-update', compact('coupons', 'companies'));
        }
        else{
            $request->validate([
                'coupon_name' => 'required|string',
                'coupon_code' => 'required|string',
                'detail' => 'required|string',
                'url_to_buy' => 'required|url',
                'expiry_date' => 'required',
            ]);
            try{
                $coupons = Company_coupon::where('user_id', Auth::user()->id)->where('id', $id)->first();
                if(!$coupons)
                return redirect()->route('company.coupons')->with('status', 'error')->with('message', Config::get('constants.ERROR.OOPS_ERROR'));
                $coupons->name = $request->coupon_name;
                $coupons->code = $request->coupon_code;
                $coupons->detail = $request->detail;
                $coupons->url_to_buy = $request->url_to_buy;
                $coupons->expiry_date = $request->expiry_date;
                $coupons->status = 1;
                $coupons->save();
                return redirect()->route('company.coupons')->with('status', 'success')->with('message', 'Coupon '.Config::get('constants.SUCCESS.UPDATE_DONE'));
            } catch ( \Exception $e ) {
                return redirect()->back()->with('status', 'error')->with('message', $e->getMessage());
            }
        }
    }
    /* End Method couponUpdate */

    /*
    Method Name:    changeCouponStatus
    Purpose:        To change the status of coupon by id
    Params:         {encrypted Id}
    */
    public function changeCouponStatus(Request $request){
        //Check if company have access for coupon module
        if((Session::has('membership_plan_other_data')) && (Session::get('membership_plan_other_data')['Coupons'] == 0))
            abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));
        $getData = $request->all();
        //Check coupon id DB
        $coupons = Company_coupon::where('user_id', Auth::user()->id)->where('id', decrypt_userdata($getData['id']))->first();        
        if(!$coupons) //If coupon not exist redirect back with error message
        return redirect()->route('company.coupons')->with('status', 'error')->with('message', Config::get('constants.ERROR.OOPS_ERROR'));
        //Update status in DB
        $coupons->status = $getData['status'];
        $coupons->save();
        //Redirect back with success message
        return redirect()->back()->with('status', 'success')->with('message', 'Coupon '.Config::get('constants.SUCCESS.STATUS_UPDATE'));
    }
    /* End Method changeCouponStatus */

    /*
    Method Name:    couponDelete
    Purpose:        To delete company coupon by id
    Params:          {encrypted Id}
    */
    public function couponDelete($id){
        $id = decrypt_userdata($id);        
        //Check if company have access for coupon module
        if((Session::has('membership_plan_other_data')) && (Session::get('membership_plan_other_data')['Coupons'] == 0))
            abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));
        try {
            $coupon = Company_coupon::where('id',$id)->where('user_id', Auth::user()->id)->delete();            
            //Redirect back with success message
        	return redirect()->back()->with(['status' => 'success', 'message' => 'Coupon '.Config::get('constants.SUCCESS.DELETE_DONE')]); ;
        } catch(Exception $ex){
            return redirect()->back()->with('status', 'error')->with('message', $e->getMessage());
        }
    }
    /* End Method couponDelete */

    /*
    Method Name:    serviceDelete
    Purpose:        To delete company service by id
    Params:          {encrypted Id}
    */
    public function serviceDelete($id){
        $id = decrypt_userdata($id);
        try {
            $coupon = Company_service::where('id',$id)->where('user_id', Auth::user()->id)->delete();            
            $this->updateProfile(Auth::user()->id); 
            //Redirect back with success message
        	return redirect()->back()->with(['status' => 'success', 'message' => 'Service '.Config::get('constants.SUCCESS.DELETE_DONE')]); ;
        } catch(Exception $ex){
            return redirect()->back()->with('status', 'error')->with('message', $e->getMessage());
        }
    }
    /* End Method serviceDelete */

    /*
    Method Name:    serviceUpdate
    Purpose:        To delete company award or banner by id
    Params:          {encrypted Id}
    */
    public function serviceUpdate(Request $request, $id = NULL) {
        if($id){
            $id = decrypt_userdata($id);
            if ($request->isMethod('get')) {
                $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                    $query->where('photo_type', 'Logo');
                }])->where('id', Auth::user()->id)->first();
                $services = User::with(['company_service' => function ($query) {
                    $query->with('service', 'currency');
                }])->where('id', Auth::user()->id)->first();
                
                $categories = CompanyCategory::where('user_id', Auth::user()->id)->get();
                $records = Company_service::with(['service'])->where('id', $id)->where('user_id', Auth::user()->id)->first();
                if(is_null($records))
                abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));   
                return view('company.services', compact('services', 'companies', 'records', 'categories'));
            }
            else{
                
                $services = Company_service::where('id', '!=', $id)->where('service_id', $request->service)->where('user_id', Auth::user()->id)->first();
                if(!is_null($services))//Redirect back with error message
                    return redirect()->back()->with('status', 'error')->with('message', 'Duplicate entry for service not allowed');
                //Validation checks
                $validatedData = $request->validate([
                    'service' => 'required|numeric',
                    'currency' => 'required|numeric',
                    'price' => 'required|numeric',
                    'platform' => 'required|string',
                    'details' => 'required|string',
                    'url' => 'required|string',
                ]);
                $service = Company_service::where('id', $id)->where('user_id', Auth::user()->id)->first();
                if(is_null($service))
                abort(403, Config::get('constants.ERROR.FORBIDDEN_ERROR'));
                $service->service_id = $request->service;
                $service->currency_id = $request->currency;
                $service->price = $request->price;
                $service->platform = $request->platform;
                $service->details = $request->details;
                $service->url = $request->url;
                $service->save();                
                //Update profile progress by ID
                $this->updateProfile(Auth::user()->id);                 
                //Redirect back with success message
                return redirect()->route('company.services')->with('status', 'success')->with('message', ' Company Service '.Config::get('constants.SUCCESS.UPDATE_DONE'));
            }
        }
        else{
            if ($request->isMethod('get')) {
                $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                    $query->where('photo_type', 'Logo');
                }])->where('id', Auth::user()->id)->first();
                $categories = CompanyCategory::where('user_id', Auth::user()->id)->count();
                $services = User::with(['company_service.service'])->where('id', Auth::user()->id)->first();
                return view('company.services', compact('services', 'companies', 'categories'));
            }
            else{
                $validatedData = $request->validate([
                    'service' => 'required|numeric',
                    'currency' => 'required|numeric',
                    'price' => 'required|numeric',
                    'platform' => 'required|string',
                    'details' => 'required|string',
                    'url' => 'required|string',
                ]);
                //Check for duplicate entry 
                $service = Company_service::where('service_id', $request->service)->where('user_id', Auth::user()->id)->first();
                if(!is_null($service))
                return redirect()->back()->with('status', 'error')->with('message', 'Duplicate entry for service not allowed');
                //Sercice count for company
                $serviceCount = Company_service::where('user_id', Auth::user()->id)->count();
                //Check membership plan how many service can compnay add and take action accordingly
                if($serviceCount >= Session::get('membership_plan_other_data')['Services'])
                return redirect()->back()->with('status', 'error')->with('message', 'Maximum '.Session::get('membership_plan_other_data')['Services'].' services allowed' );
                //Create new service for company
                Company_service::create([
                    'user_id' => Auth::user()->id,
                    'service_id' => $request->service,
                    'currency_id' => $request->currency,
                    'price' => $request->price,
                    'platform' => $request->platform,
                    'details' => $request->details,
                    'url' => $request->url,
                    'status' => 1
                ]);        
                //Update profile progress by userId
                $this->updateProfile(Auth::user()->id); 
                return redirect()->route('company.services')->with('status', 'success')->with('message', ' Company Service '.Config::get('constants.SUCCESS.CREATE_DONE'));
            }
        }
    }
    /* End Method serviceUpdate */

    /*
    Method Name:    categoryUpdate
    Purpose:        To add and delete categories for company
    Params:         {company_category}
    */
    public function categoryUpdate(Request $request) {
        if ($request->isMethod('get')) {
            //Display all categories of company
            $companies = User::with(['company_detail', 'company_photo' => function ($query) {
                $query->where('photo_type', 'Logo');
            }])->where('id', Auth::user()->id)->first();
            $categories = User::with(['company_category.category'])->where('id', Auth::user()->id)->first();
            return view('company.categories', compact('categories', 'companies'));
        }
        else{
            $validatedData = $request->validate([
                'company_category' => 'required|array|max:'.Session::get('membership_plan_other_data')['Categories'],
            ]);
            //Delete all previous categories
            CompanyCategory::where('user_id', Auth::user()->id)->delete();
            foreach($request->company_category as $category) {
                //create new category for company
                CompanyCategory::create([
                    'user_id' => Auth::user()->id,
                    'category_id' => $category,
                    'status' => 1
                ]);
            }
            //Update profile progress by userId
            $this->updateProfile(Auth::user()->id); 
            return redirect()->back()->with('status', 'success')->with('message', ' Company Categories '.Config::get('constants.SUCCESS.UPDATE_DONE'));
        }
    }
    /* End Method categoryUpdate */
}