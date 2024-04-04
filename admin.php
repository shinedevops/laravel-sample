<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['prevent-back-history', 'auth', 'verified', 'admin.subadmin.user', 'verifydomain', 'allowed-ips'])->namespace('App\Http\Controllers')->group(function () {
    // List Subscription plans
    Route::get('subscriptions', 'Admin\SubscriptionController@index')->name('subscriptions.index');
    // export invoices
    Route::get('user/{userId}/invoice/{id}/export', 'Admin\CompanyController@export')->name('invoices.export');
    Route::middleware(['admin'])->group(function () {
        // Companies
        Route::get('companies', 'Admin\CompanyController@index')->name('companies.index');
        Route::match(['GET',  'POST'], 'companies/{user}/profile', 'Admin\CompanyController@profile')->name('companies.profile');
        Route::get('companies/{user}/settings', 'Admin\CompanyController@paymentSettings')->name('companies.settings');
        Route::get('companies/{user}/servers', 'Admin\CompanyController@serverPlans')->name('companies.servers');
        Route::get('companies/{user}/backups', 'Admin\CompanyController@backupPlans')->name('companies.backups');
        Route::get('companies/{user}/invoices', 'Admin\CompanyController@invoices')->name('companies.invoices');
        Route::get('companies/{user}/subscription/info', 'Admin\CompanyController@subscriptionInfo')->name('companies.subscriptions.info');
        Route::get('companies/{user}/subscription-payments', 'Admin\CompanyController@subscriptionPayments')->name('companies.subscriptions');
        
        // Subadmin Cards for superadmin
        Route::prefix('companies/{user}/cards')->name('companies.cards.')->group(function() {
            Route::get('/', 'Common\CardController@index')->name('index');
            Route::post('store', 'Common\CardController@store')->name('store');
            Route::post('destroy', 'Common\CardController@destroy')->name('delete');
            Route::post('default', 'Common\CardController@default')->name('default');
        });

        // Subscription plan
        Route::get('subscriptions/create', 'Admin\SubscriptionController@create')->name('subscriptions.create');
        Route::post('subscriptions/store', 'Admin\SubscriptionController@store')->name('subscriptions.store');
        Route::get('subscriptions/{subscription}/edit', 'Admin\SubscriptionController@edit')->name('subscriptions.edit');
        Route::post('subscriptions/{subscription}/update', 'Admin\SubscriptionController@update')->name('subscriptions.update');
        Route::post('subscriptions/{subscription}/destroy', 'Admin\SubscriptionController@destroy')->name('subscriptions.destroy');
        Route::get('subscriptions/{subscription}/{status}/status', 'Admin\SubscriptionController@status')->name('subscriptions.status');

        // Server plans
        Route::get('server-plans', 'Admin\ServerPlanController@index')->name('server.plans.index');
        Route::get('server-plans/create', 'Admin\ServerPlanController@create')->name('server.plans.create');
        Route::post('server-plans/store', 'Admin\ServerPlanController@store')->name('server.plans.store');
        Route::get('server-plans/{plan}/view', 'Admin\ServerPlanController@view')->name('server.plans.view');
        Route::get('server-plans/{plan}/edit', 'Admin\ServerPlanController@edit')->name('server.plans.edit');
        Route::post('server-plans/{plan}/update', 'Admin\ServerPlanController@update')->name('server.plans.update');
        Route::get('server-plans/{plan}/{status}/status', 'Admin\ServerPlanController@status')->name('server.plans.status');
        Route::post('server-plans/{plan}/destroy', 'Admin\ServerPlanController@destroy')->name('server.plans.destroy');
    });

    // Buy Subscription Plans
    Route::get('subscriptions/{subscription}/buy', 'SubAdmin\SubscriptionController@buy')->name('subscriptions.buy');
    Route::post('subscriptions/{subscription}/subscribe', 'SubAdmin\SubscriptionController@subscribe')->name('subscriptions.subscribe');
    Route::get('subscriptions/{subscription}/payment/success', 'SubAdmin\SubscriptionController@paymentSuccess')->name('subscriptions.payment.success');
    Route::get('subscriptions/{subscription}/cancel', 'SubAdmin\SubscriptionController@unsubscribe')->name('subscriptions.cancel');
    Route::get('subscriptions/{subscription}/resume', 'SubAdmin\SubscriptionController@resume')->name('subscriptions.resume');
    Route::get('subscriptions/{subscription}/upgrade', 'SubAdmin\SubscriptionController@upgrade')->name('subscriptions.upgrade');
    Route::post('subscriptions/{subscription}/upgrade', 'SubAdmin\SubscriptionController@upgradePayment')->name('subscriptions.upgrade');

    // Buy Server Plans
    Route::get('server-plans/list', 'SubAdmin\ServerPlanController@index')->name('server.plans.list');
    Route::get('server-plans/{plan}/details', 'SubAdmin\ServerPlanController@details')->name('server.plans.details');
    Route::get('server-plans/{plan}/buy', 'SubAdmin\ServerPlanController@view')->name('server.plans.buy');
    Route::post('server-plans/{plan}/buy', 'SubAdmin\ServerPlanController@buy')->name('server.plans.buy');
    Route::get('server-plans/{plan}/success', 'SubAdmin\ServerPlanController@success')->name('server.plans.success');

    // Subadmin server plans
    Route::get('add-comet-server', 'SubAdmin\ServerPlanController@addCometServer')->name('cometserver.add');
    Route::get('my-server-plans', 'SubAdmin\ServerPlanController@myServerPlans')->name('server.plans.mine');
    Route::get('server-plans/{serverPlanUser}/add-host', 'SubAdmin\ServerPlanController@addHost')->name('server.plans.addhost');
    Route::post('server-plans/{serverPlanUser}/save-host', 'SubAdmin\ServerPlanController@saveHost')->name('server.plans.savehost');
    // Route::get('server-plans/{serverPlanUser}/edit-host', 'SubAdmin\ServerPlanController@editHost')->name('server.plans.edithost');
    // Route::post('server-plans/{serverPlanUser}/update-host', 'SubAdmin\ServerPlanController@updateHost')->name('server.plans.updatehost');
    Route::get('server-plans/{serverPlanUser}/cancel', 'SubAdmin\ServerPlanController@cancel')->name('server.plans.cancel');
    // Route::get('server-plans/{serverPlanUser}/resume', 'SubAdmin\ServerPlanController@resume')->name('server.plans.resume');

    Route::middleware(['subscribed'])->group(function () {
        // Comet Devices, Items and Jobs
        Route::get('devices', 'Customer\DeviceController@index')->name('devices.index');
        Route::post('get-devices', 'Customer\DeviceController@getDevices')->name('devices.all');
        Route::post('device/{device}/items', 'Customer\DeviceController@items')->name('devices.items');
        Route::get('device/{device}', 'Customer\DeviceController@show')->name('devices.show');
        Route::delete('device/{device}', 'Customer\DeviceController@delete')->name('devices.revoke');
        Route::get('device/{device}/settings', 'Customer\DeviceController@edit')->name('devices.edit');
        Route::post('device/{device}/settings', 'Customer\DeviceController@update')->name('devices.update');
        Route::post('device/{device}/runningJob', 'Customer\DeviceController@runningJob')->name('devices.running.job');
        Route::get('item/{item}/settings', 'Customer\ItemController@edit')->name('items.edit');
        Route::post('item/{item}/settings', 'Customer\ItemController@update')->name('items.update');
        Route::post('job/{item}/start', 'Customer\BackupJobController@start')->name('jobs.start');
        Route::post('job/{job}/stop', 'Customer\BackupJobController@stop')->name('jobs.stop');
    });

    Route::namespace('Common')->group(function () {
        Route::middleware(['subscribed'])->group(function () {
            Route::get('dashboard', 'AdminController@dashboard')->name('home');
            Route::get('buckets/storage', 'AdminController@bucketsStorage')->name('buckets.storage');
            // Comet Servers
            Route::get('servers', 'ServerController@index')->name('servers.index');
            Route::get('servers/create', 'ServerController@create')->name('servers.create');
            Route::post('servers/store', 'ServerController@store')->name('servers.store');
            Route::get('servers/{server}/edit', 'ServerController@edit')->name('servers.edit')->middleware('check.comet.server');
            Route::post('servers/{server}/update', 'ServerController@update')->name('servers.update')->middleware('check.comet.server');
            Route::post('servers/{server}/destroy', 'ServerController@destroy')->name('servers.destroy')->middleware('check.comet.server');
            Route::post('servers/testConnection', 'ServerController@testConnection')->name('servers.test.connection');

            // Comet Boosters
            // Route::get('boosters', 'BoosterController@index')->name('boosters.index');
            // Route::get('boosters/create', 'BoosterController@create')->name('boosters.create');
            // Route::post('boosters/store', 'BoosterController@store')->name('boosters.store');
            // Route::get('boosters/{booster}/edit', 'BoosterController@edit')->name('boosters.edit')->middleware('check.comet.booster');
            // Route::post('boosters/{booster}/update', 'BoosterController@update')->name('boosters.update')->middleware('check.comet.booster');
            // Route::post('boosters/{booster}/destroy', 'BoosterController@destroy')->name('boosters.destroy')->middleware('check.comet.booster');

            // Comet Vaults
            // Route::get('vaults', 'VaultController@index')->name('vaults.index');
            // Route::get('vaults/create', 'VaultController@create')->name('vaults.create');
            // Route::post('vaults/store', 'VaultController@store')->name('vaults.store');
            // Route::get('vaults/{vault}/edit', 'VaultController@edit')->name('vaults.edit')->middleware('check.comet.vault');
            // Route::post('vaults/{vault}/update', 'VaultController@update')->name('vaults.update')->middleware('check.comet.vault');
            // Route::post('vaults/{vault}/destroy', 'VaultController@destroy')->name('vaults.destroy')->middleware('check.comet.vault');

            Route::middleware(['is.backup.access'])->group(function () {
                // Backup Plans
                Route::get('backups', 'BackupPlanController@index')->name('backups.index');
                Route::get('backups/create', 'BackupPlanController@create')->name('backups.create');
                Route::post('backups/store', 'BackupPlanController@store')->name('backups.store');
                Route::get('backups/{backup}/view', 'BackupPlanController@view')->name('backups.view');
                Route::get('backups/{backup}/edit', 'BackupPlanController@edit')->name('backups.edit');
                Route::get('backups/{backup}/{status}/status', 'BackupPlanController@status')->name('backups.status');
                Route::post('backups/{backup}/update', 'BackupPlanController@update')->name('backups.update');
                Route::post('backups/{backup}/destroy', 'BackupPlanController@destroy')->name('backups.destroy');
                Route::post('backups/info', 'BackupPlanController@info')->name('backups.info');
            });

            // Users
            Route::get('users', 'UserController@index')->name('users.index');
            Route::match(['GET',  'POST'], 'users/{user}/profile', 'UserController@profile')->name('users.profile');
            Route::get('users/{user}/backup-plans', 'UserController@backupPlans')->name('users.backups');
            Route::get('users/{user}/invoices', 'UserController@invoices')->name('users.invoices');
            Route::get('users/create', 'UserController@create')->name('users.create');
            Route::post('users/store', 'UserController@store')->name('users.store');
            Route::get('users/{user}/backups/add', 'UserController@addBackupPlan')->name('users.backups.add');
            Route::post('users/{user}/backups/save', 'UserController@saveBackupPlan')->name('users.backups.save');
            Route::get('users/{user}/backups/{backup}/cancel', 'UserController@cancelBackupPlan')->name('users.backups.cancel');

            // Users Cards
            Route::prefix('users/{user}/cards')->name('users.cards.')->group(function() {
                Route::get('/', 'CardController@index')->name('index');
                Route::post('store', 'CardController@store')->name('store');
                Route::post('destroy', 'CardController@destroy')->name('delete');
                Route::post('default', 'CardController@default')->name('default');
            });

            // Invites
            Route::get('invites', 'InviteUserController@index')->name('invites.index');
            Route::get('invites/create', 'InviteUserController@create')->name('invites.create');
            Route::post('invites/store', 'InviteUserController@store')->name('invites.store');
            Route::get('invites/{invite}/reinvite', 'InviteUserController@reinvite')->name('invites.reinvite');
            Route::match(['GET', 'POST'], 'invites/import', 'InviteUserController@import')->name('invites.import');

            // Email Templates
            Route::get('email/templates', 'EmailTemplateController@index')->name('email.template.index');
            Route::get('email/templates/{emailTemplate}/edit', 'EmailTemplateController@edit')->name('email.template.edit');
            Route::post('email/templates/{emailTemplate}/update', 'EmailTemplateController@update')->name('email.template.update');


            // Refunds
            // Route::get('refunds', 'RefundController@index')->name('refunds.index');
            // Route::get('refunds/{plan}/refundRequest', 'RefundController@refundRequest')->name('refunds.request');
            // Route::post('refunds/{refund}/approve', 'RefundController@refundApprove')->name('refunds.approve');
            // Route::get('refunds/{refund}/cancel', 'RefundController@refundCancel')->name('refunds.cancel');
            // Route::get('refunds/{refund}/decline', 'RefundController@refundDecline')->name('refunds.decline');

            // Orders
            Route::get('orders/subscriptions', 'OrderController@subscriptions')->name('orders.subscriptions');
            Route::get('orders/servers', 'OrderController@servers')->name('orders.servers');
            Route::get('orders/backups', 'OrderController@backups')->name('orders.backups');

            // Payments
            Route::get('payments/subscriptions', 'PaymentController@subscriptions')->name('payments.subscriptions');
            Route::get('payments/servers', 'PaymentController@servers')->name('payments.servers');
            Route::get('payments/backups', 'PaymentController@backups')->name('payments.backups');
            Route::get('reports/download', 'PaymentController@download')->name('reports.download');

            // comet servers
            Route::post('comet/users', 'CometServerController@users')->name('comet.users');
        });

        // test stripe account
        Route::post('stripe/account/test', 'AdminController@testStripeAccount')->name('stripe.test');
        // My Account
        Route::get('profile', 'ProfileController@profile')->name('profile');
        Route::post('profile/update', 'ProfileController@updateProfile')->name('profile.update');
        Route::post('password/update', 'ProfileController@updatePassword')->name('password.update');

        // Subadmin cards in account settings 
        Route::get('cards/create', 'CardController@create')->name('cards.create');
        Route::post('cards/store', 'CardController@store')->name('cards.store');
        Route::post('cards/destroy', 'CardController@destroy')->name('cards.delete');
        Route::post('cards/default', 'CardController@default')->name('cards.default');
    });
});
