<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Mail\Tenant\ContactInquiryConfirmationMail;
use App\Mail\Tenant\ContactInquiryManagerMail;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/Client/Contact');
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $restaurantEmail = Setting::get('restaurant_email');

        if ($restaurantEmail) {
            try {
                // Mail do managera restauracji
                Mail::to($restaurantEmail)->send(
                    new ContactInquiryManagerMail($validated['name'], $validated['email'], $validated['message'])
                );

                // Potwierdzenie do klienta
                Mail::to($validated['email'], $validated['name'])->send(
                    new ContactInquiryConfirmationMail($validated['name'], $validated['message'])
                );
            } catch (\Exception $e) {
                Log::warning('Contact form email failed: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Wiadomość wysłana.']);
    }
}
