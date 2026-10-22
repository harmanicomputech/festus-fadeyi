<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin: full dashboard access. checkin: welcome-desk check-in only.
            $table->string('role', 20)->default('admin')->after('password');
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Guests the committee has invited (used for invite-only mode,
        // the invitation email and the non-responder reminder).
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('title', 20)->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 32)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamp('invitation_sent_at')->nullable();
            $table->boolean('email_opt_out')->default(false);
            $table->timestamps();
        });

        Schema::create('rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 20);
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone', 32);
            $table->string('organisation')->nullable();
            $table->string('attendance', 10);          // yes | no | maybe
            $table->unsignedTinyInteger('party_size')->default(1);
            $table->string('status', 20)->index();     // confirmed | waitlisted | maybe | declined
            $table->timestamp('waitlisted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('dietary_needs', 500)->nullable();
            $table->text('message')->nullable();
            $table->string('manage_token_hash', 64)->unique();
            $table->text('manage_token');               // encrypted, so links can be re-sent
            $table->string('qr_token', 64)->unique();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('email_opt_out')->default(false);
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('currency', 3)->default('NGN');
            $table->string('donor_name')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('message')->nullable();
            $table->boolean('acknowledge_publicly')->default(false);
            $table->boolean('show_amount_publicly')->default(false);
            $table->string('method', 20);              // paystack | bank_transfer | cash | other
            $table->string('channel', 40)->nullable(); // e.g. card, bank, ussd (from gateway)
            $table->string('status', 20)->index();     // pending | success | failed | abandoned
            $table->foreignId('pledge_id')->nullable();
            $table->foreignId('rsvp_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gateway_status', 40)->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->timestamps();
        });

        // Pledges are promises, never counted as money received.
        Schema::create('pledges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rsvp_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->unsignedBigInteger('amount_kobo');
            $table->string('status', 20)->default('open'); // open | fulfilled | cancelled
            $table->string('pay_token', 64)->unique();
            $table->foreignId('donation_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('template', 40)->index();
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->string('status', 20);              // sent | logged | failed
            $table->text('error')->nullable();
            // Set for one-per-recipient emails so scheduled campaigns never double-send.
            $table->string('dedupe_key')->nullable()->unique();
            $table->foreignId('rsvp_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('donation_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rsvp_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('result', 20);              // admitted | repeat | invalid | not_confirmed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('pledges');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('rsvps');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('settings');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'last_login_at']);
        });
    }
};
