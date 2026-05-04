<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\Tenant\CampaignMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmailCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MarketingController extends Controller
{
    public function index()
    {
        $campaigns      = EmailCampaign::latest()->paginate(15);
        $customersCount = Customer::whereNotNull('email')->count();

        return Inertia::render('Tenant/Manager/Marketing/Index', [
            'campaigns'      => $campaigns,
            'customersCount' => $customersCount,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'target'  => 'required|in:all,active,inactive',
        ]);

        $campaign = EmailCampaign::create($validated);
        Log::info('Marketing: kampania utworzona', ['campaign_id' => $campaign->id, 'name' => $campaign->name, 'target' => $campaign->target, 'manager_id' => auth('tenant')->id()]);

        return back()->with('success', 'Kampania została zapisana jako szkic.');
    }

    public function update(Request $request, EmailCampaign $campaign)
    {
        if ($campaign->status === 'sent') {
            return back()->withErrors(['error' => 'Nie można edytować wysłanej kampanii.']);
        }

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'target'  => 'required|in:all,active,inactive',
        ]);

        $campaign->update($validated);
        Log::info('Marketing: kampania zaktualizowana', ['campaign_id' => $campaign->id, 'name' => $campaign->name, 'manager_id' => auth('tenant')->id()]);
        return back()->with('success', 'Kampania zaktualizowana.');
    }

    public function send(EmailCampaign $campaign)
    {
        // Atomic guard: only transition from draft→sending once; prevents double-send
        $locked = EmailCampaign::where('id', $campaign->id)
            ->where('status', 'draft')
            ->update(['status' => 'sending']);

        if (!$locked) {
            return back()->withErrors(['error' => 'Ta kampania została już wysłana lub jest w trakcie wysyłki.']);
        }

        $query = Customer::whereNotNull('email')->where('marketing_opt_out', false);

        if ($campaign->target === 'active') {
            $query->whereHas('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(90)));
        } elseif ($campaign->target === 'inactive') {
            $query->whereDoesntHave('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(90)));
        }

        $count = 0;

        $query->chunk(200, function ($customers) use ($campaign, &$count) {
            foreach ($customers as $customer) {
                try {
                    Mail::to($customer->email)->queue(new CampaignMail($campaign, $customer));
                    $count++;
                } catch (\Exception $e) {
                    Log::warning('Campaign email failed for ' . $customer->email . ': ' . $e->getMessage());
                }
            }
        });

        $campaign->update([
            'status'           => 'sent',
            'sent_at'          => now(),
            'recipients_count' => $count,
        ]);

        Log::info('Marketing: kampania wysłana', [
            'campaign_id'   => $campaign->id,
            'name'          => $campaign->name,
            'target'        => $campaign->target,
            'recipients'    => $count,
            'manager_id'    => auth('tenant')->id(),
        ]);

        return back()->with('success', "Kampania wysłana do {$count} klientów.");
    }

    public function destroy(EmailCampaign $campaign)
    {
        if ($campaign->status === 'sent') {
            return back()->withErrors(['error' => 'Nie można usunąć wysłanej kampanii.']);
        }
        $campaign->delete();
        return back()->with('success', 'Kampania usunięta.');
    }

    /**
     * Public unsubscribe endpoint – no auth required.
     * URL: GET /marketing/unsubscribe?email=...&token=...
     */
    public function unsubscribe(\Illuminate\Http\Request $request)
    {
        $email = $request->query('email', '');
        $token = $request->query('token', '');

        $customer = Customer::where('email', $email)->first();

        if (!$customer || !hash_equals($customer->unsubscribeToken(), $token)) {
            return response('<h2>Nieprawidłowy link wypisania.</h2>', 400)
                ->header('Content-Type', 'text/html');
        }

        $customer->update(['marketing_opt_out' => true]);
        Log::info('Marketing: wypisanie z listy', ['customer_id' => $customer->id, 'email' => $customer->email]);

        return response('<h2>Zostałeś wypisany z listy mailingowej.</h2><p>Nie będziesz już otrzymywać emaili marketingowych.</p>', 200)
            ->header('Content-Type', 'text/html');
    }
}
