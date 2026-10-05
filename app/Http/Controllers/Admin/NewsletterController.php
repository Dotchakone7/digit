<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(): View
    {
        return view('admin.newsletter', [
            'subscribers' => NewsletterSubscriber::query()->latest()->paginate(30),
            'active' => NewsletterSubscriber::query()->whereNull('unsubscribed_at')->count(),
        ]);
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'inscrit_le'], ';');
            NewsletterSubscriber::query()->whereNull('unsubscribed_at')->lazyById()
                ->each(fn ($s) => fputcsv($out, [$s->email, $s->created_at->format('Y-m-d')], ';'));
            fclose($out);
        }, 'newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
