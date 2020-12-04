@extends('layouts.app')

@section('content')

<!-- banner-section-start -->
<section class="listing-banner">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="content text-center">
                    <h1>My Account</h1>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- accounts-section-start -->
<section class="contact-section">
    <div class="container">
    
        <div class="flash-message">
            @if(session()->has('status'))
                @if(session()->get('status') == 'error')
                    <div class="alert alert-danger  alert-dismissible">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        {{ session()->get('message') }}
                    </div>
                @endif
                @if(session()->get('status') == 'success')
                    <div class="alert alert-success  alert-dismissible">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        {{ session()->get('message') }}
                    </div>
                @endif
            @endif
        </div> <!-- end .flash-message -->
        <div class="row">
            @include('layouts/sidebar')
            <div class="col-xl-9 col-lg-8 col-sm-12 mb-3">
                <div class="acc-right">
                    <div class="success-msg" style="display: none;"></div>
                    <div class="error-msg" style="display: none;"></div>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active">
                            <div class="container-fluid acc-tab-content" id="show-info">
                                <div class="acc-tab-heading">
                                    <h3>Company Details 
                                    <a href="javascript:void(0);" class="edit-btn"><i class="fa fa-pencil-square-o"></i> </a></h3>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Company Name:</label>
                                            <div class="controls" id="display-companyName">{{$companies->company_detail->company_name}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Company Slug:</label>
                                            <div class="controls" id="display-companySlug">{{$companies->company_detail->slug}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Company Website Url:</label>
                                            <div class="controls" id="display-companyWebsite">{{is_null($companies->company_detail->website_url) ? 'N/A' : $companies->company_detail->website_url}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Company Logo:</label>
                                            <div class="controls"><img  class="img-profile" id="display-companyLogo" src="{{asset('assets/media/'.base64_encode($companies->id).'/logo/'.$companies->company_photo[0]->photo)}}" style="width: 100%;max-width: 200px;height: 100%;max-height: 50px;" data-asset-path="{{asset('assets/media/'.base64_encode($companies->id).'/logo/')}}"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label>About Company:</label>
                                            <div class="controls" id="display-companyAbout">{{$companies->company_detail->about}}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="container-fluid acc-tab-content" id="edit-info" style="display:none">
                                <div class="acc-tab-heading">
                                    <h3>Company Details
                                    <a href="javascript:void(0);" class="edit-btn"><i class="fa fa-times"></i> </a></h3>
                                </div>
                                <form method="post" action="{{route('company.details-update')}}"  id="company-detail-form" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Company Name<span class="required">*</span></label>
                                                <div class="controls"><input type="text" class="form-control" name="company_name" id="company_name" value="{{$companies->company_detail->company_name}}" >
                                                <span class="text-danger company-error" id="company_name-error"></span>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Company Slug<span class="required">*</span></label>
                                                <div class="controls"><input type="text" class="form-control" name="company_slug" id="company_slug" value="{{$companies->company_detail->slug}}" >
                                                <span class="text-danger company-error" id="company_slug-error"></span>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Company Website Url<span class="required">*</span></label>
                                                <div class="controls"><input type="url" class="form-control" name="website_url" id="website_url" value="{{$companies->company_detail->website_url}}" >
                                                <span class="text-danger company-error" id="website_url-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Company Logo:</label>
                                                <div class="controls"><input type="file" class="form-control" name="company_logo" id="company_logo"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>About Company<span class="required">*</span></label>
                                                <div class="controls"><textarea class="form-control" name="about" id="about">{{$companies->company_detail->about}}</textarea>
                                                <span class="text-danger company-error" id="about-error"></span>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-right">
                                            <input type="submit" class="btn btn-primary" name="Update" value="Update" >
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade show active">
                            <div class="container-fluid acc-tab-content" id="show-info">
                                <div class="acc-tab-heading">
                                    <h3>Company Contact Details
                                    <a href="javascript:void(0);" class="edit-btn"><i class="fa fa-pencil-square-o"></i> </a></h3>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Contact Person Email:</label>
                                            <div class="controls" id="display-contactEmail">{{is_null($companies->company_detail->contact_person_email) ? 'N/A' : $companies->company_detail->contact_person_email}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Contact Person Mobile:</label>
                                            <div class="controls" id="display-contactPhone">{{is_null($companies->company_detail->contact_person_mobile) ? 'N/A' : $companies->company_detail->contact_person_mobile}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Toll Free Number:</label>
                                            <div class="controls" id="display-tollfreeNumber">{{is_null($companies->company_detail->toll_free_number) ? 'N/A' : $companies->company_detail->toll_free_number}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Customer Care Number:</label>
                                            <div class="controls" id="display-customercareNumber">{{is_null($companies->company_detail->customer_care_number) ? 'N/A' : $companies->company_detail->customer_care_number}}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="container-fluid acc-tab-content" id="edit-info" style="display:none">
                                <div class="acc-tab-heading">
                                    <h3>Company Contact Details
                                    <a href="javascript:void(0);" class="edit-btn"><i class="fa fa-times"></i> </a></h3>
                                </div>
                                <form method="post" action="{{route('company.contact-update')}}" id="company-contact-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Contact Person Email<span class="required">*</span></label>
                                                <div class="controls"><input type="text" class="form-control" name="contact_person_email" id="contact_person_email" value="{{$companies->company_detail->contact_person_email}}" >
                                                <span class="text-danger company-error" id="contact_person_email-error"></span></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Contact Person Mobile<span class="required">*</span></label>
                                                <div class="controls"><input type="text" class="form-control" name="contact_person_mobile" id="contact_person_mobile" value="{{$companies->company_detail->contact_person_mobile}}" ><span class="text-danger company-error" id="contact_person_mobile-error"></span></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Toll Free Number</label>
                                                <div class="controls"><input type="text" class="form-control" name="toll_free_number" id="toll_free_number" value="{{$companies->company_detail->toll_free_number}}" ></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Customer Care Number</label>
                                                <div class="controls"><input type="text" class="form-control" name="customer_care_number" id="customer_care_number" value="{{$companies->company_detail->customer_care_number}}" ></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-right">
                                            <input type="submit" class="btn btn-primary" name="Update" value="Update" >
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade show active">
                            <div class="container-fluid acc-tab-content" id="show-info">
                                <div class="acc-tab-heading">
                                    <h3>Company Socail Links 
                                        <a href="javascript:void(0);" class="edit-btn"><i class="fa fa-pencil-square-o"></i> </a>
                                    </h3>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Google Plus Link:</label>
                                            <div class="controls" id="display-googleLink">{{($companies->company_detail->google_plus_link == "") ? 'N/A' : $companies->company_detail->google_plus_link}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Facebook Link:</label>
                                            <div class="controls" id="display-facebookLink">{{($companies->company_detail->facebook_link == "") ? 'N/A' : $companies->company_detail->facebook_link}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Twitter Link:</label>
                                            <div class="controls" id="display-twitterLink">{{($companies->company_detail->twitter_link == "") ? 'N/A' : $companies->company_detail->twitter_link}}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Instagram Link:</label>
                                            <div class="controls" id="display-instagramLink">{{($companies->company_detail->instagram_link == "") ? 'N/A' : $companies->company_detail->instagram_link}}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="container-fluid acc-tab-content" id="edit-info" style="display:none">
                                <div class="acc-tab-heading">
                                    <h3>Company Socail Links 
                                        <a href="javascript:void(0);" class="edit-btn"><i class="fa fa-times"></i> </a>
                                    </h3>
                                </div>
                                <form method="post" action="{{route('company.social-update')}}" id="company-social-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Google Plus Link</label>
                                                <div class="controls"><input type="url" class="form-control" name="google_plus_link" id="google_plus_link" value="{{$companies->company_detail->google_plus_link}}" ></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Facebook Link</label>
                                                <div class="controls"><input type="url" class="form-control" name="facebook_link" id="facebook_link" value="{{$companies->company_detail->facebook_link}}" ></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Twitter Link</label>
                                                <div class="controls"><input type="url" class="form-control" name="twitter_link" id="twitter_link" value="{{$companies->company_detail->twitter_link}}" ></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Instagram Link</label>
                                                <div class="controls"><input type="url" class="form-control" name="instagram_link" id="instagram_link" value="{{$companies->company_detail->instagram_link}}" ></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-right">
                                            <input type="submit" class="btn btn-primary" name="Update" value="Update" >
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- accounts-section-ends -->
@endsection

@section('scripts')
<script>
    jQuery(".form-control").on("change", function(event){
        jQuery(this).next('.company-error').html('');
    });
</script>
<script>
//Update company details in data base using ajax
jQuery( document ).ready(function() {
	jQuery("form[id='company-detail-form']").validate({
		// Specify validation rules
		ignore: '',
		submitHandler: function(form) {
            var formData = new FormData(form);
            var formdata = jQuery(form);
            var urls = formdata.prop('action');
            jQuery.ajax({
                type: "POST",
                url: urls,
                data: formData,
                dataType: 'json',
                cache:false,
                contentType: false,
                processData: false,
                success: function (data) { 
                    if (data.success == true) {
                        jQuery(".company-error").html();
                        jQuery(form).parent().parent().find("#edit-info").toggle();
                        jQuery(form).parent().parent().find("#show-info").toggle();
                        jQuery('#display-companyName').html(jQuery('#company_name').val());
                        jQuery('#display-companyName-main').html(jQuery('#company_name').val());
                        if(data.image != 'false'){
                            jQuery('#company_logo').val('');
                            var assetPath = jQuery('#display-companyLogo').attr('data-asset-path');
                            jQuery('#display-companyLogo').attr("src", assetPath +'/'+ data.image);
                            jQuery('#display-companyLogo-main').attr("src", assetPath +'/'+ data.image);
                        }
                        jQuery('#display-companySlug').html(jQuery('#company_slug').val());
                        jQuery('#display-companyWebsite').html(jQuery('#website_url').val());
                        jQuery('#display-companyAbout').html(jQuery('#about').val());
                        swal({
                            title: "Success!",
                            text: data.message,
                            icon: "success",
                        });
                    }
                },
                error: function(xhr, status, error) 
                {
                    jQuery.each(xhr.responseJSON.errors, function (key, item) 
                    {
                        jQuery("#" + key + "-error").html(item);
                    });
                }
            });
		}
	}); 
    //Update company contact details in data base using ajax
	jQuery("form[id='company-contact-form']").validate({
		// Specify validation rules
		ignore: '',
		submitHandler: function(form) {
			jQuery.ajax({ 
                data: jQuery(form).serialize(), 
                type: jQuery(form).attr('method'), 
                url: jQuery(form).attr('action'),
                beforeSend: function() {
                    // setting a timeout
                    // jQuery(this).validate();
                },
                success: function (data) { 
                    if (data.success == true) {
                        jQuery(".user-error").html();
                        jQuery(form).parent().parent().find("#edit-info").toggle();
                        jQuery(form).parent().parent().find("#show-info").toggle();
                        jQuery('#display-contactEmail').html(jQuery('#contact_person_email').val());
                        jQuery('#display-contactPhone').html(jQuery('#contact_person_mobile').val());
                        jQuery('#display-tollfreeNumber').html((jQuery('#toll_free_number').val() != '') ? jQuery('#toll_free_number').val() : "N/A");
                        jQuery('#display-customercareNumber').html((jQuery('#customer_care_number').val() != '') ? jQuery('#customer_care_number').val() : "N/A");
                        jQuery('#display-state').html(jQuery('#state').children("option:selected").html());
                        swal({
                            title: "Success!",
                            text: data.message,
                            icon: "success",
                        });
                    } 
                },
                error: function(xhr, status, error) 
                {
                    jQuery.each(xhr.responseJSON.errors, function (key, item) 
                    {
                        jQuery("#" + key + "-error").html(item);
                    });
                }
            });
		}
    }); 
    
    //Update company social links in data base using ajax
	jQuery("form[id='company-social-form']").validate({
		// Specify validation rules
		ignore: '',
		rules: {
		},
		// Specify validation error messages
		messages: {
		},
		submitHandler: function(form) {
			jQuery.ajax({ 
                data: jQuery(form).serialize(), 
                type: jQuery(form).attr('method'), 
                url: jQuery(form).attr('action'),
                beforeSend: function() {
                    // setting a timeout
                    // jQuery(this).validate();
                },
                success: function (data) { 
                    if (data.success == true) {
                        jQuery(".user-error").html();
                        jQuery(form).parent().parent().find("#edit-info").toggle();
                        jQuery(form).parent().parent().find("#show-info").toggle();
                        jQuery('#display-googleLink').html((jQuery('#google_plus_link').val() != '') ? jQuery('#google_plus_link').val() : "N/A");
                        jQuery('#display-facebookLink').html((jQuery('#facebook_link').val() != '') ? jQuery('#facebook_link').val() : "N/A");
                        jQuery('#display-twitterLink').html((jQuery('#twitter_link').val() != '') ? jQuery('#twitter_link').val() : "N/A");
                        jQuery('#display-instagramLink').html((jQuery('#instagram_link').val() != '') ? jQuery('#instagram_link').val() : "N/A");
                        jQuery('#display-state').html(jQuery('#state').children("option:selected").html());
                        swal({
                            title: "Success!",
                            text: data.message,
                            icon: "success",
                        });
                    } 
                },
                error: function(xhr, status, error) 
                {
                    jQuery.each(xhr.responseJSON.errors, function (key, item) 
                    {
                        jQuery("#" + key + "-error").html(item);
                    });
                }
            });
		}
	}); 
});
  </script>
@stop