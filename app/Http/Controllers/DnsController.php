<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DnsRecord;
use App\Models\Domain;
use Illuminate\Support\Facades\Auth;

class DnsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains;
        $dnsRecords = DnsRecord::whereIn('domain_id', $domains->pluck('id'))
                              ->with('domain')
                              ->orderBy('created_at', 'desc')
                              ->paginate(15);
        
        return view('dns.index', compact('dnsRecords', 'domains'));
    }

    public function show(Domain $domain)
    {
        // Ensure user owns this domain
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $dnsRecords = $domain->dnsRecords()->orderBy('type')->orderBy('name')->get();
        return view('dns.show', compact('domain', 'dnsRecords'));
    }

    public function create(Domain $domain)
    {
        // Ensure user owns this domain
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        return view('dns.create', compact('domain'));
    }

    public function store(Request $request, Domain $domain)
    {
        // Ensure user owns this domain
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:A,AAAA,CNAME,MX,TXT,NS,SRV,PTR',
            'value' => 'required|string|max:1000',
            'priority' => 'nullable|integer|between:0,65535',
            'ttl' => 'required|integer|between:1,86400',
        ]);

        $domain->dnsRecords()->create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
            'value' => $request->value,
            'priority' => $request->priority,
            'ttl' => $request->ttl,
        ]);

        return redirect()->route('dns.show', $domain)->with('success', 'DNS record berhasil ditambahkan!');
    }

    public function destroy(Domain $domain, DnsRecord $dnsRecord)
    {
        // Ensure user owns this domain and DNS record
        if ($domain->user_id !== Auth::id() || $dnsRecord->user_id !== Auth::id()) {
            abort(403);
        }

        $dnsRecord->delete();

        return redirect()->route('dns.show', $domain)->with('success', 'DNS record berhasil dihapus!');
    }

    public function toggle(Domain $domain, DnsRecord $dnsRecord)
    {
        // Ensure user owns this domain and DNS record
        if ($domain->user_id !== Auth::id() || $dnsRecord->user_id !== Auth::id()) {
            abort(403);
        }

        $dnsRecord->update(['is_active' => !$dnsRecord->is_active]);

        $status = $dnsRecord->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('dns.show', $domain)->with('success', "DNS record berhasil {$status}!");
    }
}
