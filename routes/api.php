<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloodRequestController;
use App\Http\Controllers\BloodyAiController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\DonorEventController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RewardController;
use Illuminate\Support\Facades\Route;

// ===== Auth Module (dibatasi rate untuk mencegah brute-force) =====
Route::middleware('throttle:5,1')->post('login', [AuthController::class, 'login']);
Route::middleware('throttle:3,1')->post('register', [AuthController::class, 'register']);

// ===== Protected Routes (Sanctum Token) =====
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    // Profile Module
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::patch('profile/location', [ProfileController::class, 'updateLocation']);
    Route::get('profile/donor-card', [ProfileController::class, 'donorCard']);
    Route::get('profile/track-record', [ProfileController::class, 'trackRecord']);

    // Module Minta Donor (Geo-matching)
    Route::post('blood-requests', [BloodRequestController::class, 'store']);
    Route::get('blood-requests/nearby', [BloodRequestController::class, 'nearby']);
    Route::post('blood-requests/{bloodRequest}/confirm', [BloodRequestController::class, 'confirm'])
        ->middleware('can:confirm,bloodRequest');
    Route::post('blood-requests/{bloodRequest}/cancel', [BloodRequestController::class, 'cancel'])
        ->middleware('can:cancel,bloodRequest');
    Route::post('donations/{donation}/complete', [DonationController::class, 'complete'])
        ->middleware('can:complete,donation');
    Route::post('donations/{donation}/cancel', [DonationController::class, 'cancel'])
        ->middleware('can:cancel,donation');

    // Module Event Donor
    Route::get('events', [DonorEventController::class, 'index']);
    Route::post('events/{donorEvent}/book', [DonorEventController::class, 'book']);

    // Module Gamifikasi & Reward
    Route::get('rewards', [RewardController::class, 'index']);
    Route::post('rewards/{reward}/redeem', [RewardController::class, 'redeem']);

    // Module AI Chatbot "Bloody" & Edukasi
    Route::post('bloody/chat', [BloodyAiController::class, 'chat'])->middleware('throttle:10,1');
    Route::get('bloody/history', [BloodyAiController::class, 'history']);
    Route::get('posts', [PostController::class, 'index']);
});
