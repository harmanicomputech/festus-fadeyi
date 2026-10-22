<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EmailPreferenceController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\RsvpController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------
Route::get('/', [LandingController::class, 'show'])->name('home');
Route::get('/privacy', [LandingController::class, 'privacy'])->name('privacy');
Route::get('/calendar/event.ics', [CalendarController::class, 'ics'])->name('calendar.ics');

Route::get('/i/{code}', [InvitationController::class, 'show'])->middleware('throttle:lookup')->name('invite.show');

Route::post('/rsvp', [RsvpController::class, 'store'])->middleware('throttle:forms')->name('rsvp.store');
Route::middleware('throttle:lookup')->group(function () {
    Route::get('/rsvp/{token}', [RsvpController::class, 'manage'])->name('rsvp.manage');
    Route::put('/rsvp/{token}', [RsvpController::class, 'update'])->middleware('throttle:forms')->name('rsvp.update');
    Route::post('/rsvp/{token}/cancel', [RsvpController::class, 'cancel'])->name('rsvp.cancel');
    Route::get('/qr/{token}.png', [QrController::class, 'show'])->name('qr.show');
    Route::get('/donate/status/{reference}', [DonationController::class, 'status'])->name('donate.status');
});

Route::get('/donate', [DonationController::class, 'show'])->name('donate.show');
Route::post('/donate', [DonationController::class, 'store'])->middleware('throttle:forms')->name('donate.store');
Route::get('/donate/callback', [DonationController::class, 'callback'])->middleware('throttle:lookup')->name('donate.callback');
Route::post('/webhooks/paystack', [DonationController::class, 'webhook'])->name('webhooks.paystack');

Route::get('/email/unsubscribe/{type}/{id}', [EmailPreferenceController::class, 'unsubscribe'])
    ->middleware('signed')->whereNumber('id')->name('email.unsubscribe');

// ---------------------------------------------------------------------
// Administration
// ---------------------------------------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'show'])->middleware('guest')->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login'])->middleware(['guest', 'throttle:login'])->name('login.attempt');
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->middleware('auth')->name('logout');

    // Welcome-desk staff (role: checkin) and administrators
    Route::middleware(['auth', 'role:admin,checkin'])->group(function () {
        Route::get('/check-in', [Admin\CheckInController::class, 'index'])->name('checkin');
        Route::post('/check-in/lookup', [Admin\CheckInController::class, 'lookup'])->middleware('throttle:checkin')->name('checkin.lookup');
        Route::get('/check-in/search', [Admin\CheckInController::class, 'search'])->middleware('throttle:checkin')->name('checkin.search');
        Route::post('/check-in/{rsvp}', [Admin\CheckInController::class, 'admit'])->middleware('throttle:checkin')->name('checkin.admit');
    });

    // Administrators only
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/guests', [Admin\GuestController::class, 'index'])->name('guests');
        Route::get('/guests/export/{format}', [Admin\GuestController::class, 'export'])->whereIn('format', ['csv', 'xlsx'])->name('guests.export');
        Route::get('/guests/{rsvp}', [Admin\GuestController::class, 'show'])->name('guests.show');
        Route::post('/guests/{rsvp}/status', [Admin\GuestController::class, 'status'])->name('guests.status');
        Route::post('/guests/{rsvp}/resend', [Admin\GuestController::class, 'resend'])->name('guests.resend');

        Route::get('/invitations', [Admin\InvitationController::class, 'index'])->name('invitations');
        Route::post('/invitations', [Admin\InvitationController::class, 'store'])->name('invitations.store');
        Route::post('/invitations/import', [Admin\InvitationController::class, 'import'])->name('invitations.import');
        Route::post('/invitations/send', [Admin\InvitationController::class, 'send'])->name('invitations.send');
        Route::post('/invitations/settings', [Admin\InvitationController::class, 'settings'])->name('invitations.settings');
        Route::delete('/invitations/{invitation}', [Admin\InvitationController::class, 'destroy'])->name('invitations.destroy');

        Route::get('/donations', [Admin\DonationController::class, 'index'])->name('donations');
        Route::post('/donations', [Admin\DonationController::class, 'store'])->name('donations.store');
        Route::get('/donations/export/{format}', [Admin\DonationController::class, 'export'])->whereIn('format', ['csv', 'xlsx'])->name('donations.export');
        Route::get('/pledges/export/{format}', [Admin\DonationController::class, 'exportPledges'])->whereIn('format', ['csv', 'xlsx'])->name('pledges.export');
        Route::post('/donations/{donation}/verify', [Admin\DonationController::class, 'verify'])->name('donations.verify');
        Route::post('/donations/{donation}/receipt', [Admin\DonationController::class, 'receipt'])->name('donations.receipt');
        Route::get('/donations/venue-qr.png', [Admin\DonationController::class, 'venueQr'])->name('donations.venue-qr');

        Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages');

        Route::get('/emails', [Admin\EmailController::class, 'index'])->name('emails');
        Route::get('/emails/preview/{template}', [Admin\EmailController::class, 'preview'])->name('emails.preview');
        Route::post('/emails/campaign/{campaign}', [Admin\EmailController::class, 'campaign'])->name('emails.campaign');

        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports');
    });
});
