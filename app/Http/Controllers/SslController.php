<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SslCertificate;
use App\Models\Domain;
use App\Jobs\GenerateSslCertificateJob;
use Illuminate\Support\Facades\Auth;

class SslController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains;
        $certificates = SslCertificate::whereIn('domain_id', $domains->pluck('id'))
                                    ->with('domain')
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(15);
        
        return view('ssl.index', compact('certificates', 'domains'));
    }

    public function show(SslCertificate $ssl)
    {
        // Ensure user owns this SSL certificate
        if ($ssl->domain->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json($ssl->load('domain'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'type' => 'required|in:lets_encrypt,self_signed,custom',
            'certificate' => 'nullable|string',
            'private_key' => 'nullable|string',
            'ca_bundle' => 'nullable|string',
            'auto_renew' => 'boolean'
        ]);

        // Ensure user owns the domain
        $domain = Domain::findOrFail($request->domain_id);
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $certificate = new SslCertificate();
        $certificate->domain_id = $request->domain_id;
        $certificate->type = $request->type;
        $certificate->issuer = $request->type === 'lets_encrypt' ? 'Let\'s Encrypt' : 'Custom';
        $certificate->status = 'pending';
        $certificate->auto_renew = $request->boolean('auto_renew', false);

        if ($request->type === 'custom') {
            $certificate->certificate = $request->certificate;
            $certificate->private_key = $request->private_key;
            $certificate->ca_bundle = $request->ca_bundle;
            $certificate->status = 'active';
            $certificate->issued_at = now();
            $certificate->expires_at = now()->addYear();
        } else {
            // Dispatch SSL generation job to queue for Let's Encrypt
            GenerateSslCertificateJob::dispatch($certificate);
        }

        $certificate->save();

        $message = $request->type === 'custom' 
            ? 'SSL certificate uploaded successfully.' 
            : 'SSL certificate generation queued successfully.';

        return redirect()->route('ssl.index')->with('success', $message);
    }

    public function destroy(SslCertificate $ssl)
    {
        // Ensure user owns this SSL certificate
        if ($ssl->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $ssl->delete();

        return redirect()->route('ssl.index')->with('success', 'SSL certificate deleted successfully.');
    }

    public function renew(SslCertificate $ssl)
    {
        // Ensure user owns this SSL certificate
        if ($ssl->domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Simulate renewal process
        $ssl->status = 'pending';
        $ssl->save();

        return response()->json(['success' => true, 'message' => 'SSL certificate renewal initiated.']);
    }
}
